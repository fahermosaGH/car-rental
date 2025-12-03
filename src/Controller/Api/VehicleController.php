<?php

namespace App\Controller\Api;

use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/vehicles')]
class VehicleController extends AbstractController
{
    #[Route('', name: 'api_vehicles_list', methods: ['GET'])]
    public function list(VehicleRepository $vehicleRepository): Response
    {
        $vehicles = $vehicleRepository->findAll();

        $data = array_map(function ($v) {
            return [
                'id'           => $v->getId(),
                'brand'        => $v->getBrand(),
                'model'        => $v->getModel(),
                'year'         => $v->getYear(),
                'seats'        => $v->getSeats(),
                'transmission' => $v->getTransmission(),
                'dailyRate'    => $v->getDailyPriceOverride(),
                'isActive'     => $v->isActive(),
                'category'     => $v->getCategory() ? $v->getCategory()->getName() : null,
            ];
        }, $vehicles);

        return $this->json($data, Response::HTTP_OK, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ]);
    }

    #[Route('/available', name: 'api_vehicles_available', methods: ['GET'])]
    public function available(Request $request, VehicleRepository $vehicleRepository): Response
    {
        $pickupStr = $request->query->get('pickupLocationId');
        $startStr  = $request->query->get('startAt');
        $endStr    = $request->query->get('endAt');
        $category  = $request->query->get('category'); // string o null

        if (!$pickupStr || !$startStr || !$endStr) {
            return $this->json(['error' => 'pickupLocationId, startAt y endAt son obligatorios'], 400);
        }

        try {
            $start = new \DateTimeImmutable($startStr);
            $end   = new \DateTimeImmutable($endStr);
        } catch (\Throwable) {
            return $this->json(['error' => 'Formato de fecha inválido'], 400);
        }

        if ($start >= $end) {
            return $this->json(['error' => 'startAt debe ser menor que endAt'], 400);
        }

        // 🔥🔥🔥 IGNORAR SUCURSAL PARA ENCONTRAR VEHÍCULOS, PERO IGUAL MOSTRAR STOCK REAL
if ($category && trim($category) !== '') {

    $normalized = trim($category);

    // 1️⃣ Traigo todos los autos de la categoría
    $vehicles = $vehicleRepository->createQueryBuilder('v')
        ->leftJoin('v.category', 'c')
        ->addSelect('c')
        ->where('c.name = :cat')
        ->setParameter('cat', $normalized)
        ->getQuery()
        ->getResult();

    $data = [];

    foreach ($vehicles as $v) {

        // 2️⃣ Ahora busco stock real de este vehículo EN LA SUCURSAL
        $stockRow = $vehicleRepository->findAvailableWithStockInfo(
            (int)$pickupStr,
            $start,
            $end,
            $v->getCategory()->getName()     // filtramos por la categoría real
        );

        // Busco el stock de este vehículo en particular
        $match = null;
        foreach ($stockRow as $sr) {
            if ((int)$sr['id'] === $v->getId()) {
                $match = $sr;
                break;
            }
        }

        // 3️⃣ Si hay match → uso stock real
        if ($match) {
            $branchStock    = (int)($match['branchStock'] ?? 0);
            $taken          = (int)($match['taken'] ?? 0);
            $unitsAvailable = max($branchStock - $taken, 0);
        } else {
            // 4️⃣ Si NO hay stock → mostrar sin stock
            $unitsAvailable = 0;
            $branchStock    = 0;
        }

        $data[] = [
            'id'             => $v->getId(),
            'brand'          => $v->getBrand(),
            'model'          => $v->getModel(),
            'year'           => $v->getYear(),
            'seats'          => $v->getSeats(),
            'transmission'   => $v->getTransmission(),
            'dailyRate'      => $v->getDailyPriceOverride(),
            'isActive'       => $v->isActive(),
            'category'       => $v->getCategory()->getName(),
            'unitsAvailable' => $unitsAvailable,
            'branchStock'    => $branchStock,
        ];
    }

    return $this->json($data, 200, [], [
        'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
    ]);
}


        // -----------------------------
        // MODO NORMAL (sin categoría)
        // -----------------------------
        $rows = $vehicleRepository->findAvailableWithStockInfo(
            (int)$pickupStr,
            $start,
            $end,
            null
        );

        $data = array_map(static function (array $r) {
            $branchStock    = (int) ($r['branchStock'] ?? 0);
            $taken          = (int) ($r['taken'] ?? 0);
            $unitsAvailable = max($branchStock - $taken, 0);

            return [
                'id'             => (int) $r['id'],
                'brand'          => (string) $r['brand'],
                'model'          => (string) $r['model'],
                'year'           => isset($r['year']) ? (int)$r['year'] : null,
                'seats'          => isset($r['seats']) ? (int)$r['seats'] : null,
                'transmission'   => $r['transmission'] ?? null,
                'dailyRate'      => $r['dailyRate'],
                'isActive'       => (bool)$r['isActive'],
                'category'       => $r['category'] ?? null,
                'unitsAvailable' => $unitsAvailable,
                'branchStock'    => $branchStock,
            ];
        }, $rows);

        return $this->json($data, Response::HTTP_OK, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ]);
    }
}
