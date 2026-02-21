<?php

namespace App\Controller\Api\Admin;

use App\Entity\ReservationIncident;
use App\Entity\VehicleUnit;
use App\Service\StockSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/incidents')]
class AdminReservationIncidentController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $rows = $em->createQueryBuilder()
            ->select('i, r, bu, ru, bv, rv')
            ->from(ReservationIncident::class, 'i')
            ->leftJoin('i.reservation', 'r')
            ->leftJoin('i.vehicleUnit', 'bu')          // unidad rota (la original)
            ->leftJoin('i.replacementUnit', 'ru')      // unidad reemplazo (nueva)
            ->leftJoin('bu.vehicle', 'bv')
            ->leftJoin('ru.vehicle', 'rv')
            ->orderBy('i.id', 'DESC')
            ->getQuery()->getResult();

        $data = array_map(function (ReservationIncident $i) {
            $r = $i->getReservation();
            $broken = $i->getVehicleUnit();
            $repl = $i->getReplacementUnit();

            return [
                'id' => $i->getId(),
                'status' => $i->getStatus(),
                'description' => $i->getDescription(),
                'resolvedAt' => $i->getResolvedAt()?->format('c'),

                'reservation' => $r ? [
                    'id' => $r->getId(),
                    'vehicleId' => $r->getVehicle()?->getId(),
                    'pickupLocationId' => $r->getPickupLocation()?->getId(),
                    'startAt' => $r->getStartAt()?->format('Y-m-d'),
                    'endAt' => $r->getEndAt()?->format('Y-m-d'),
                ] : null,

                'brokenUnit' => $broken ? [
                    'id' => $broken->getId(),
                    'plate' => $broken->getPlate(),
                    'status' => $broken->getStatus(),
                    'vehicleName' => (string) $broken->getVehicle(),
                ] : null,

                'replacementUnit' => $repl ? [
                    'id' => $repl->getId(),
                    'plate' => $repl->getPlate(),
                    'status' => $repl->getStatus(),
                    'vehicleName' => (string) $repl->getVehicle(),
                ] : null,
            ];
        }, $rows);

        return $this->json($data);
    }

    /**
     * Devuelve unidades disponibles por:
     * - Misma sucursal (pickupLocation)
     * - Disponibles por FECHAS (no solapadas con reservas)
     * - Estado AVAILABLE
     */
    #[Route('/{id}/available-units', methods: ['GET'])]
    public function availableUnits(int $id, EntityManagerInterface $em): JsonResponse
    {
        $incident = $em->getRepository(ReservationIncident::class)->find($id);
        if (!$incident) {
            return $this->json(['error' => 'Incidente no encontrado'], 404);
        }

        $reservation = $incident->getReservation();
        if (!$reservation) {
            return $this->json([]);
        }

        $start = $reservation->getStartAt();
        $end   = $reservation->getEndAt();
        $loc   = $reservation->getPickupLocation();

        if (!$start || !$end || !$loc) {
            return $this->json([]);
        }

        $qb = $em->createQueryBuilder();
        $qb->select('u, v')
            ->from(VehicleUnit::class, 'u')
            ->leftJoin('u.vehicle', 'v')
            ->where('u.location = :l')
            ->andWhere('u.status = :st')
            ->setParameter('l', $loc)
            ->setParameter('st', VehicleUnit::STATUS_AVAILABLE);

        // Excluir unidades con reservas que se solapen con [start,end]
        $qb->andWhere(
            'NOT EXISTS (
                SELECT r2.id
                FROM App\Entity\Reservation r2
                WHERE r2.vehicleUnit = u
                  AND r2.status <> :cancelled
                  AND r2.startAt <= :end
                  AND r2.endAt >= :start
            )'
        )
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->setParameter('cancelled', 'cancelled');

        $units = $qb->getQuery()->getResult();

        $data = array_map(static fn(VehicleUnit $u) => [
            'id' => $u->getId(),
            'plate' => $u->getPlate(),
            'vehicleName' => (string) $u->getVehicle(),
        ], $units);

        return $this->json($data);
    }

    #[Route('/{id}/reassign', methods: ['POST'])]
    public function reassign(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        StockSyncService $stockSync
    ): JsonResponse
    {
        $incident = $em->getRepository(ReservationIncident::class)->find($id);
        if (!$incident) {
            return $this->json(['error' => 'Incidente no encontrado'], 404);
        }

        if ($incident->getStatus() === ReservationIncident::STATUS_RESOLVED) {
            return $this->json(['error' => 'El incidente ya está resuelto'], 409);
        }

        $reservation = $incident->getReservation();
        if (!$reservation) {
            return $this->json(['error' => 'Sin reserva'], 422);
        }

        $payload = json_decode((string) $request->getContent(), true) ?? [];
        $newUnitId = (int)($payload['newUnitId'] ?? 0);

        if ($newUnitId <= 0) {
            return $this->json(['error' => 'newUnitId requerido'], 422);
        }

        $newUnit = $em->getRepository(VehicleUnit::class)->find($newUnitId);
        if (!$newUnit) {
            return $this->json(['error' => 'Unidad no encontrada'], 404);
        }

        if ($newUnit->getStatus() !== VehicleUnit::STATUS_AVAILABLE) {
            return $this->json(['error' => 'La unidad seleccionada no está disponible'], 409);
        }

        $broken = $reservation->getVehicleUnit(); // unidad actual de la reserva (rota)

        $em->beginTransaction();
        try {
            // 1) cambiar unidad en reserva
            $reservation->setVehicleUnit($newUnit);

            // 2) incidente: set reemplazo + resolver
            $incident->setReplacementUnit($newUnit);
            $incident->setStatus(ReservationIncident::STATUS_RESOLVED);
            $incident->setResolvedAt(new \DateTimeImmutable('now'));

            // 3) estados
            if ($broken) {
                $broken->setStatus(VehicleUnit::STATUS_MAINTENANCE);
                $em->persist($broken);
            }

            // IMPORTANTÍSIMO: la nueva unidad queda ocupada -> no debe contar como stock disponible
            $newUnit->setStatus(VehicleUnit::STATUS_INACTIVE);

            $em->persist($reservation);
            $em->persist($incident);
            $em->persist($newUnit);

            $em->flush();
            $em->commit();

            // sincronización stock (después del commit)
            if ($broken) {
                $stockSync->syncFor($broken->getVehicle(), $broken->getLocation());
            }
            $stockSync->syncFor($newUnit->getVehicle(), $newUnit->getLocation());

            return $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            $em->rollback();
            return $this->json(['error' => 'No se pudo reasignar: '.$e->getMessage()], 500);
        }
    }
}