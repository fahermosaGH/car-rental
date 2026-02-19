<?php

namespace App\Controller\Api\Admin;

use App\Entity\ReservationIncident;
use App\Entity\VehicleUnit;
use App\Service\StockSyncService;
use App\Service\VehicleUnitAssignmentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/incidents')]
class AdminReservationIncidentController extends AbstractController
{
    #[Route('', name: 'api_admin_incidents_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $rows = $em->createQueryBuilder()
            ->select('i, r, vu')
            ->from(ReservationIncident::class, 'i')
            ->leftJoin('i.reservation', 'r')->addSelect('r')
            ->leftJoin('i.vehicleUnit', 'vu')->addSelect('vu')
            ->orderBy('i.id', 'DESC')
            ->getQuery()->getResult();

        $data = array_map(static function (ReservationIncident $i) {
            $r = $i->getReservation();
            return [
                'id' => $i->getId(),
                'status' => $i->getStatus(),
                'createdAt' => $i->getCreatedAt()->format('c'),
                'description' => $i->getDescription(),
                'reservation' => $r ? [
                    'id' => $r->getId(),
                    'status' => $r->getStatus(),
                    'startAt' => $r->getStartAt()?->format('Y-m-d'),
                    'endAt' => $r->getEndAt()?->format('Y-m-d'),
                    'vehicleId' => $r->getVehicle()?->getId(),
                    'pickupLocationId' => $r->getPickupLocation()?->getId(),
                    'unitPlate' => $r->getVehicleUnit()?->getPlate(),
                ] : null,
                'reportedUnitPlate' => $i->getVehicleUnit()?->getPlate(),
            ];
        }, $rows);

        return $this->json($data);
    }

    #[Route('/{id}/resolve', name: 'api_admin_incidents_resolve', methods: ['POST'])]
    public function resolve(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var ReservationIncident|null $incident */
        $incident = $em->getRepository(ReservationIncident::class)->find($id);
        if (!$incident) return $this->json(['error' => 'Incidente no encontrado'], 404);

        if ($incident->getStatus() !== ReservationIncident::STATUS_OPEN) {
            return $this->json(['error' => 'El incidente no está abierto'], 409);
        }

        $incident->setStatus(ReservationIncident::STATUS_RESOLVED);
        $em->flush();

        return $this->json(['ok' => true, 'message' => 'Incidente resuelto']);
    }

    #[Route('/{id}/reassign', name: 'api_admin_incidents_reassign', methods: ['POST'])]
    public function reassign(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        StockSyncService $stockSync,
        VehicleUnitAssignmentService $assigner
    ): JsonResponse {
        /** @var ReservationIncident|null $incident */
        $incident = $em->getRepository(ReservationIncident::class)->find($id);
        if (!$incident) return $this->json(['error' => 'Incidente no encontrado'], 404);

        if ($incident->getStatus() !== ReservationIncident::STATUS_OPEN) {
            return $this->json(['error' => 'El incidente no está abierto'], 409);
        }

        $reservation = $incident->getReservation();
        if (!$reservation) return $this->json(['error' => 'Incidente sin reserva'], 422);

        $data = json_decode($request->getContent(), true);
        $markBrokenAsMaintenance = (bool)($data['markBrokenAsMaintenance'] ?? true);

        // unidad “rota”: la que estaba asignada a la reserva
        $brokenUnit = $reservation->getVehicleUnit();
        if (!$brokenUnit) {
            return $this->json(['error' => 'La reserva no tiene unidad asignada'], 422);
        }

        // 1) marcar la rota como maintenance (impacta stock automáticamente)
        if ($markBrokenAsMaintenance) {
            $brokenUnit->setStatus(VehicleUnit::STATUS_MAINTENANCE);
            $stockSync->syncFor($brokenUnit->getVehicle(), $brokenUnit->getLocation());
        }

        // 2) buscar reemplazo (misma sucursal de pickup y mismo vehículo)
        $newUnit = $assigner->pickUnitOrNull(
            $reservation->getVehicle(),
            $reservation->getPickupLocation(),
            $reservation->getStartAt(),
            $reservation->getEndAt()
        );

        if (!$newUnit) {
            // si no hay reemplazo, dejamos incidente abierto para que admin decida (cancelar, etc.)
            $em->flush();
            return $this->json([
                'error' => 'No hay unidad de reemplazo disponible en esa sucursal.',
                'code' => 'NO_REPLACEMENT_UNIT',
            ], 409);
        }

        // 3) reasignar patente en la reserva
        $reservation->setVehicleUnit($newUnit);

        // 4) guardar la unidad reportada en el incidente (histórico)
        $incident->setVehicleUnit($brokenUnit);

        // 5) opcional: resolver incidente automáticamente al reasignar
        $incident->setStatus(ReservationIncident::STATUS_RESOLVED);

        $em->flush();

        return $this->json([
            'ok' => true,
            'message' => 'Unidad reasignada y unidad rota marcada en mantenimiento',
            'brokenPlate' => $brokenUnit->getPlate(),
            'newPlate' => $newUnit->getPlate(),
            'reservationId' => $reservation->getId(),
        ]);
    }
}
