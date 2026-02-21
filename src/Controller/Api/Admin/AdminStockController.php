<?php

namespace App\Controller\Api\Admin;

use App\Entity\VehicleLocationStock;
use App\Service\StockSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/stock')]
class AdminStockController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $rows = $em->createQueryBuilder()
            ->select('s, v, l')
            ->from(VehicleLocationStock::class, 's')
            ->leftJoin('s.vehicle', 'v')
            ->leftJoin('s.location', 'l')
            ->orderBy('l.name', 'ASC')
            ->addOrderBy('v.brand', 'ASC')
            ->addOrderBy('v.model', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_map(static function (VehicleLocationStock $s) {
            $v = $s->getVehicle();
            $l = $s->getLocation();

            return [
                'id' => $s->getId(),
                'quantity' => $s->getQuantity(),
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

    // ESTE ES EL BOTÓN "SINCRONIZAR" (por fila)
    #[Route('/{id}/sync', methods: ['POST'])]
    public function syncOne(
        int $id,
        EntityManagerInterface $em,
        StockSyncService $sync
    ): JsonResponse {
        $row = $em->getRepository(VehicleLocationStock::class)->find($id);
        if (!$row) {
            return $this->json(['error' => 'Fila de stock no encontrada'], 404);
        }

        $vehicle = $row->getVehicle();
        $location = $row->getLocation();
        if (!$vehicle || !$location) {
            return $this->json(['error' => 'Fila inválida (sin vehículo o sucursal)'], 422);
        }

        $qty = $sync->syncFor($vehicle, $location);

        return $this->json([
            'ok' => true,
            'quantity' => $qty,
        ]);
    }

    // "Recalcular todo (patentes)" (si ya lo tenías, no molesta; si no, ahora existe)
    #[Route('/rebuild-all', methods: ['POST'])]
    public function rebuildAll(StockSyncService $sync): JsonResponse
    {
        $sync->rebuildAll();

        return $this->json(['ok' => true]);
    }
}