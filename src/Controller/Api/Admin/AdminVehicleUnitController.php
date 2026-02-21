<?php

namespace App\Controller\Api\Admin;

use App\Entity\Location;
use App\Entity\Reservation;
use App\Entity\Vehicle;
use App\Entity\VehicleUnit;
use App\Service\StockSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vehicle-units')]
#[IsGranted('ROLE_ADMIN')]
class AdminVehicleUnitController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StockSyncService $stockSync,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $vehicleId  = (int) $request->query->get('vehicleId', 0);
        $locationId = (int) $request->query->get('locationId', 0);
        $status     = (string) $request->query->get('status', '');

        $qb = $this->em->createQueryBuilder()
            ->select('u, v, l')
            ->from(VehicleUnit::class, 'u')
            ->leftJoin('u.vehicle', 'v')
            ->leftJoin('u.location', 'l')
            ->orderBy('u.id', 'DESC');

        if ($vehicleId > 0) {
            $qb->andWhere('v.id = :vid')->setParameter('vid', $vehicleId);
        }
        if ($locationId > 0) {
            $qb->andWhere('l.id = :lid')->setParameter('lid', $locationId);
        }
        if ($status !== '') {
            $qb->andWhere('u.status = :st')->setParameter('st', $status);
        }

        /** @var VehicleUnit[] $rows */
        $rows = $qb->getQuery()->getResult();

        $data = array_map(static function (VehicleUnit $u) {
            $v = $u->getVehicle();
            $l = $u->getLocation();

            return [
                'id' => $u->getId(),
                'plate' => $u->getPlate(),
                'status' => $u->getStatus(),
                'vehicle' => $v ? [
                    'id' => $v->getId(),
                    'brand' => $v->getBrand(),
                    'model' => $v->getModel(),
                    'year' => $v->getYear(),
                ] : null,
                'location' => $l ? [
                    'id' => $l->getId(),
                    'name' => $l->getName(),
                    'city' => $l->getCity(),
                ] : null,
            ];
        }, $rows);

        return $this->json($data);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $plate = strtoupper(trim((string)($data['plate'] ?? '')));
        $vehicleId = (int)($data['vehicleId'] ?? 0);
        $locationId = (int)($data['locationId'] ?? 0);
        $status = (string)($data['status'] ?? VehicleUnit::STATUS_AVAILABLE);

        if ($plate === '' || $vehicleId <= 0 || $locationId <= 0) {
            return $this->json(['error' => 'Datos inválidos'], 422);
        }

        $exists = $this->em->getRepository(VehicleUnit::class)->findOneBy(['plate' => $plate]);
        if ($exists) {
            return $this->json(['error' => 'Esa patente ya existe'], 409);
        }

        $vehicle = $this->em->getRepository(Vehicle::class)->find($vehicleId);
        $location = $this->em->getRepository(Location::class)->find($locationId);

        if (!$vehicle || !$location) {
            return $this->json(['error' => 'Vehículo o sucursal inválidos'], 422);
        }

        $u = new VehicleUnit();
        $u->setPlate($plate);
        $u->setVehicle($vehicle);
        $u->setLocation($location);
        $u->setStatus($status);

        $this->em->persist($u);
        $this->em->flush();

        // ✅ recalcular stock afectado
        $this->stockSync->syncFor($vehicle, $location);

        return $this->json(['ok' => true], 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var VehicleUnit|null $u */
        $u = $this->em->getRepository(VehicleUnit::class)->find($id);
        if (!$u) {
            return $this->json(['error' => 'Unidad no encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        // Guardar “antes” para sincronizar si cambió algo
        $oldVehicle = $u->getVehicle();
        $oldLocation = $u->getLocation();

        if (array_key_exists('plate', $data)) {
            $plate = strtoupper(trim((string)$data['plate']));
            if ($plate === '') {
                return $this->json(['error' => 'La patente es obligatoria'], 422);
            }

            $exists = $this->em->getRepository(VehicleUnit::class)->findOneBy(['plate' => $plate]);
            if ($exists && $exists->getId() !== $u->getId()) {
                return $this->json(['error' => 'Esa patente ya existe'], 409);
            }

            $u->setPlate($plate);
        }

        if (array_key_exists('status', $data)) {
            $newStatus = (string)$data['status'];

            // (opcional pero recomendado) si la unidad está asignada a una reserva activa,
            // NO permitir marcarla como "available".
            if ($newStatus === VehicleUnit::STATUS_AVAILABLE) {
                $hasActiveReservation = (bool) $this->em->createQueryBuilder()
                    ->select('COUNT(r.id)')
                    ->from(Reservation::class, 'r')
                    ->where('r.vehicleUnit = :u')
                    ->andWhere('r.status <> :cancelled')
                    ->setParameter('u', $u)
                    ->setParameter('cancelled', 'cancelled')
                    ->getQuery()
                    ->getSingleScalarResult();

                if ($hasActiveReservation) {
                    return $this->json(['error' => 'No podés poner Disponible: la unidad está asignada a una reserva'], 409);
                }
            }

            $u->setStatus($newStatus);
        }

        // si tu UI algún día permite cambiar vehicleId/locationId, lo dejamos soportado:
        if (array_key_exists('vehicleId', $data)) {
            $vehicle = $this->em->getRepository(Vehicle::class)->find((int)$data['vehicleId']);
            if ($vehicle) $u->setVehicle($vehicle);
        }
        if (array_key_exists('locationId', $data)) {
            $location = $this->em->getRepository(Location::class)->find((int)$data['locationId']);
            if ($location) $u->setLocation($location);
        }

        $this->em->flush();

        // ✅ recalcular stock viejo y nuevo (por si cambió algo)
        if ($oldVehicle && $oldLocation) {
            $this->stockSync->syncFor($oldVehicle, $oldLocation);
        }
        if ($u->getVehicle() && $u->getLocation()) {
            $this->stockSync->syncFor($u->getVehicle(), $u->getLocation());
        }

        return $this->json(['ok' => true]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        /** @var VehicleUnit|null $u */
        $u = $this->em->getRepository(VehicleUnit::class)->find($id);
        if (!$u) {
            return $this->json(['error' => 'Unidad no encontrada'], 404);
        }

        $v = $u->getVehicle();
        $l = $u->getLocation();

        $this->em->remove($u);
        $this->em->flush();

        // ✅ recalcular stock afectado
        if ($v && $l) {
            $this->stockSync->syncFor($v, $l);
        }

        return $this->json(['ok' => true]);
    }
}