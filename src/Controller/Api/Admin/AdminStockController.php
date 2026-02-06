<?php

namespace App\Controller\Api\Admin;

use App\Entity\VehicleLocationStock;
use App\Repository\LocationRepository;
use App\Repository\VehicleRepository;
use App\Repository\VehicleLocationStockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/stock')]
class AdminStockController extends AbstractController
{
    #[Route('', name: 'api_admin_stock_list', methods: ['GET'])]
    public function list(Request $request, VehicleLocationStockRepository $repo): JsonResponse
    {
        $locationId = $request->query->getInt('locationId', 0);
        $vehicleId  = $request->query->getInt('vehicleId', 0);

        $qb = $repo->createQueryBuilder('s')
            ->leftJoin('s.vehicle', 'v')->addSelect('v')
            ->leftJoin('s.location', 'l')->addSelect('l')
            ->orderBy('l.id', 'DESC')
            ->addOrderBy('v.id', 'DESC')
            ->addOrderBy('s.id', 'DESC');

        if ($locationId > 0) {
            $qb->andWhere('l.id = :lid')->setParameter('lid', $locationId);
        }
        if ($vehicleId > 0) {
            $qb->andWhere('v.id = :vid')->setParameter('vid', $vehicleId);
        }

        /** @var VehicleLocationStock[] $rows */
        $rows = $qb->getQuery()->getResult();

        $data = array_map(static function (VehicleLocationStock $s) {
            $v = $s->getVehicle();
            $l = $s->getLocation();

            return [
                'id' => $s->getId(),
                'quantity' => $s->getQuantity(),

                'vehicle' => $v ? [
                    'id' => $v->getId(),
                    'brand' => method_exists($v, 'getBrand') ? $v->getBrand() : null,
                    'model' => method_exists($v, 'getModel') ? $v->getModel() : null,
                    'year'  => method_exists($v, 'getYear') ? $v->getYear() : null,
                    'isActive' => method_exists($v, 'isActive') ? $v->isActive() : true,
                ] : null,

                'location' => $l ? [
                    'id' => $l->getId(),
                    'name' => $l->getName(),
                    'city' => $l->getCity(),
                    'isActive' => $l->isActive(),
                ] : null,
            ];
        }, $rows);

        return $this->json($data);
    }

    // Upsert por (vehicleId, locationId) (respeta unique(vehicle, location))
    #[Route('', name: 'api_admin_stock_upsert', methods: ['POST'])]
    public function upsert(
        Request $request,
        VehicleLocationStockRepository $stockRepo,
        VehicleRepository $vehicleRepo,
        LocationRepository $locationRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        $vehicleId = (int)($data['vehicleId'] ?? 0);
        $locationId = (int)($data['locationId'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 0);

        if ($vehicleId <= 0 || $locationId <= 0) {
            return $this->json(['error' => 'vehicleId y locationId son obligatorios'], 422);
        }
        if ($quantity < 0) {
            return $this->json(['error' => 'quantity no puede ser negativo'], 422);
        }

        $vehicle = $vehicleRepo->find($vehicleId);
        if (!$vehicle) return $this->json(['error' => 'Vehículo no encontrado'], 404);

        $location = $locationRepo->find($locationId);
        if (!$location) return $this->json(['error' => 'Ubicación no encontrada'], 404);

        $existing = $stockRepo->findOneBy(['vehicle' => $vehicle, 'location' => $location]);

        if ($existing) {
            $existing->setQuantity($quantity);
            $em->flush();

            return $this->json([
                'ok' => true,
                'id' => $existing->getId(),
                'quantity' => $existing->getQuantity(),
                'message' => 'Stock actualizado',
            ]);
        }

        $stock = new VehicleLocationStock();
        $stock->setVehicle($vehicle);
        $stock->setLocation($location);
        $stock->setQuantity($quantity);

        $em->persist($stock);
        $em->flush();

        return $this->json([
            'ok' => true,
            'id' => $stock->getId(),
            'quantity' => $stock->getQuantity(),
            'message' => 'Stock creado',
        ], 201);
    }

    #[Route('/{id}', name: 'api_admin_stock_update', methods: ['PUT'])]
    public function update(int $id, Request $request, VehicleLocationStockRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $stock = $repo->find($id);
        if (!$stock) return $this->json(['error' => 'Stock no encontrado'], 404);

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) return $this->json(['error' => 'JSON inválido'], 400);

        if (!array_key_exists('quantity', $data)) {
            return $this->json(['error' => 'quantity es obligatorio'], 422);
        }

        $quantity = (int)$data['quantity'];
        if ($quantity < 0) return $this->json(['error' => 'quantity no puede ser negativo'], 422);

        $stock->setQuantity($quantity);
        $em->flush();

        return $this->json([
            'ok' => true,
            'id' => $stock->getId(),
            'quantity' => $stock->getQuantity(),
        ]);
    }
}