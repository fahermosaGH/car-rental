<?php

namespace App\Controller\Api\Admin;

use App\Entity\VehicleUnit;
use App\Repository\VehicleUnitRepository;
use App\Repository\VehicleRepository;
use App\Repository\LocationRepository;
use App\Service\StockSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/vehicle-units')]
class AdminVehicleUnitController extends AbstractController
{
    #[Route('', name: 'api_admin_vehicle_units_list', methods: ['GET'])]
    public function list(Request $request, VehicleUnitRepository $repo): JsonResponse
    {
        $vehicleId  = $request->query->getInt('vehicleId', 0);
        $locationId = $request->query->getInt('locationId', 0);
        $status     = $request->query->get('status');

        $qb = $repo->createQueryBuilder('u')
            ->leftJoin('u.vehicle', 'v')->addSelect('v')
            ->leftJoin('u.location', 'l')->addSelect('l')
            ->orderBy('u.id', 'DESC');

        if ($vehicleId > 0) {
            $qb->andWhere('v.id = :vid')->setParameter('vid', $vehicleId);
        }
        if ($locationId > 0) {
            $qb->andWhere('l.id = :lid')->setParameter('lid', $locationId);
        }
        if ($status) {
            $qb->andWhere('u.status = :st')->setParameter('st', $status);
        }

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

    #[Route('', name: 'api_admin_vehicle_units_create', methods: ['POST'])]
    public function create(
        Request $request,
        VehicleRepository $vehicleRepo,
        LocationRepository $locationRepo,
        EntityManagerInterface $em,
        StockSyncService $stockSync
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) return $this->json(['error' => 'JSON inválido'], 400);

        $vehicleId  = (int)($data['vehicleId'] ?? 0);
        $locationId = (int)($data['locationId'] ?? 0);
        $plate      = trim((string)($data['plate'] ?? ''));
        $status     = (string)($data['status'] ?? VehicleUnit::STATUS_AVAILABLE);

        if ($vehicleId <= 0 || $locationId <= 0 || $plate === '') {
            return $this->json(['error' => 'vehicleId, locationId y plate son obligatorios'], 422);
        }

        if (!in_array($status, [
            VehicleUnit::STATUS_AVAILABLE,
            VehicleUnit::STATUS_MAINTENANCE,
            VehicleUnit::STATUS_INACTIVE
        ], true)) {
            return $this->json(['error' => 'Status inválido'], 422);
        }

        $vehicle = $vehicleRepo->find($vehicleId);
        if (!$vehicle) return $this->json(['error' => 'Vehículo no encontrado'], 404);

        $location = $locationRepo->find($locationId);
        if (!$location) return $this->json(['error' => 'Ubicación no encontrada'], 404);

        $unit = new VehicleUnit();
        $unit->setVehicle($vehicle);
        $unit->setLocation($location);
        $unit->setPlate($plate);
        $unit->setStatus($status);

        try {
            $em->persist($unit);
            $em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Patente duplicada o error al guardar'], 409);
        }

        $stockSync->syncFor($vehicle, $location);

        return $this->json([
            'ok' => true,
            'id' => $unit->getId(),
            'plate' => $unit->getPlate(),
            'message' => 'Unidad creada correctamente'
        ], 201);
    }

    #[Route('/{id}', name: 'api_admin_vehicle_units_update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        VehicleUnitRepository $repo,
        VehicleRepository $vehicleRepo,
        LocationRepository $locationRepo,
        EntityManagerInterface $em,
        StockSyncService $stockSync
    ): JsonResponse {
        $unit = $repo->find($id);
        if (!$unit) return $this->json(['error' => 'Unidad no encontrada'], 404);

        $oldVehicle = $unit->getVehicle();
        $oldLocation = $unit->getLocation();

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) return $this->json(['error' => 'JSON inválido'], 400);

        if (isset($data['plate'])) {
            $unit->setPlate((string)$data['plate']);
        }

        if (isset($data['status'])) {
            $unit->setStatus((string)$data['status']);
        }

        if (isset($data['vehicleId'])) {
            $vehicle = $vehicleRepo->find((int)$data['vehicleId']);
            if (!$vehicle) return $this->json(['error' => 'Vehículo no encontrado'], 404);
            $unit->setVehicle($vehicle);
        }

        if (isset($data['locationId'])) {
            $location = $locationRepo->find((int)$data['locationId']);
            if (!$location) return $this->json(['error' => 'Ubicación no encontrada'], 404);
            $unit->setLocation($location);
        }

        try {
            $em->flush();
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Error al actualizar (patente duplicada?)'], 409);
        }

        // Re-sync stock (viejo y nuevo por si cambió)
        if ($oldVehicle && $oldLocation) {
            $stockSync->syncFor($oldVehicle, $oldLocation);
        }
        if ($unit->getVehicle() && $unit->getLocation()) {
            $stockSync->syncFor($unit->getVehicle(), $unit->getLocation());
        }

        return $this->json([
            'ok' => true,
            'id' => $unit->getId(),
            'message' => 'Unidad actualizada'
        ]);
    }

    #[Route('/{id}', name: 'api_admin_vehicle_units_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        VehicleUnitRepository $repo,
        EntityManagerInterface $em,
        StockSyncService $stockSync
    ): JsonResponse {
        $unit = $repo->find($id);
        if (!$unit) return $this->json(['error' => 'Unidad no encontrada'], 404);

        $vehicle = $unit->getVehicle();
        $location = $unit->getLocation();

        $em->remove($unit);
        $em->flush();

        if ($vehicle && $location) {
            $stockSync->syncFor($vehicle, $location);
        }

        return $this->json(['ok' => true, 'message' => 'Unidad eliminada']);
    }
}
