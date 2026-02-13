<?php

namespace App\Controller\Api;

use App\Repository\ReservationRepository;
use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/vehicles')]
class VehicleController extends AbstractController
{
    #[Route('', name: 'api_vehicles_list', methods: ['GET'])]
    public function list(
        VehicleRepository $vehicleRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $vehicles = $vehicleRepository->findAll();

        // ✅ ratings summary en 1 query (solo completed + rating != null)
        $ids = array_map(static fn($v) => (int)$v->getId(), $vehicles);

        // 🔥 OJO: usamos el método correcto (stats por vehicle_ids)
        $ratingsMap = $reservationRepository->getRatingStatsByVehicleIds($ids);

        $data = array_map(function ($v) use ($ratingsMap) {
            $vid = (int)$v->getId();
            $summary = $ratingsMap[$vid] ?? ['avg' => null, 'count' => 0];

            return [
                'id'           => $vid,
                'brand'        => $v->getBrand(),
                'model'        => $v->getModel(),
                'year'         => $v->getYear(),
                'seats'        => $v->getSeats(),
                'transmission' => $v->getTransmission(),
                'dailyRate'    => $v->getDailyPriceOverride(),
                'isActive'     => $v->isActive(),
                'category'     => $v->getCategory() ? $v->getCategory()->getName() : null,

                // ✅ NUEVO (para UI pro)
                'ratingAvg'    => $summary['avg'],
                'ratingCount'  => $summary['count'],
            ];
        }, $vehicles);

        return $this->json($data, Response::HTTP_OK, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ]);
    }

    #[Route('/available', name: 'api_vehicles_available', methods: ['GET'])]
    public function available(
        Request $request,
        VehicleRepository $vehicleRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $pickupStr = $request->query->get('pickupLocationId');
        $startStr  = $request->query->get('startAt');
        $endStr    = $request->query->get('endAt');
        $category  = $request->query->get('category');

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

        // ------------------------------------------------------------
        // MODO CON CATEGORÍA
        // ------------------------------------------------------------
        if ($category && trim($category) !== '') {
            $normalized = trim($category);

            $vehicles = $vehicleRepository->createQueryBuilder('v')
                ->leftJoin('v.category', 'c')
                ->addSelect('c')
                ->where('c.name = :cat')
                ->setParameter('cat', $normalized)
                ->getQuery()
                ->getResult();

            $rowsWithStock = $vehicleRepository->findAvailableWithStockInfo(
                (int)$pickupStr,
                $start,
                $end,
                $normalized
            );

            $stockById = [];
            foreach ($rowsWithStock as $r) {
                $stockById[(int)$r['id']] = $r;
            }

            // ✅ ratings summary en 1 query
            $ids = array_map(static fn($v) => (int)$v->getId(), $vehicles);
            $ratingsMap = $reservationRepository->getRatingStatsByVehicleIds($ids);

            $data = [];
            foreach ($vehicles as $v) {
                $vid = (int)$v->getId();
                $match = $stockById[$vid] ?? null;

                if ($match) {
                    $branchStock    = (int)($match['branchStock'] ?? 0);
                    $taken          = (int)($match['taken'] ?? 0);
                    $unitsAvailable = max($branchStock - $taken, 0);
                } else {
                    $branchStock = 0;
                    $unitsAvailable = 0;
                }

                $summary = $ratingsMap[$vid] ?? ['avg' => null, 'count' => 0];

                $data[] = [
                    'id'             => $vid,
                    'brand'          => $v->getBrand(),
                    'model'          => $v->getModel(),
                    'year'           => $v->getYear(),
                    'seats'          => $v->getSeats(),
                    'transmission'   => $v->getTransmission(),
                    'dailyRate'      => $v->getDailyPriceOverride(),
                    'isActive'       => $v->isActive(),
                    'category'       => $v->getCategory() ? $v->getCategory()->getName() : null,
                    'unitsAvailable' => $unitsAvailable,
                    'branchStock'    => $branchStock,

                    'ratingAvg'      => $summary['avg'],
                    'ratingCount'    => $summary['count'],
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

        $ids = array_map(static fn($r) => (int)$r['id'], $rows);
        $ratingsMap = $reservationRepository->getRatingStatsByVehicleIds($ids);

        $data = array_map(static function (array $r) use ($ratingsMap) {
            $branchStock    = (int)($r['branchStock'] ?? 0);
            $taken          = (int)($r['taken'] ?? 0);
            $unitsAvailable = max($branchStock - $taken, 0);

            $vid = (int)$r['id'];
            $summary = $ratingsMap[$vid] ?? ['avg' => null, 'count' => 0];

            return [
                'id'             => $vid,
                'brand'          => (string)$r['brand'],
                'model'          => (string)$r['model'],
                'year'           => isset($r['year']) ? (int)$r['year'] : null,
                'seats'          => isset($r['seats']) ? (int)$r['seats'] : null,
                'transmission'   => $r['transmission'] ?? null,
                'dailyRate'      => $r['dailyRate'],
                'isActive'       => (bool)$r['isActive'],
                'category'       => $r['category'] ?? null,
                'unitsAvailable' => $unitsAvailable,
                'branchStock'    => $branchStock,

                'ratingAvg'      => $summary['avg'],
                'ratingCount'    => $summary['count'],
            ];
        }, $rows);

        return $this->json($data, Response::HTTP_OK, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ]);
    }

    #[Route('/{id}/ratings', name: 'api_vehicle_ratings', methods: ['GET'])]
    public function ratings(
        int $id,
        Request $request,
        VehicleRepository $vehicleRepository,
        ReservationRepository $reservationRepository
    ): Response {
        $vehicle = $vehicleRepository->find($id);
        if (!$vehicle) {
            return $this->json(['error' => 'Vehículo no encontrado'], 404);
        }

        $limit = (int)($request->query->get('limit') ?? 12);
        if ($limit <= 0) $limit = 12;
        if ($limit > 100) $limit = 100;

        // ✅ stats e items SOLO completed + rating != null
        $stats = $reservationRepository->getRatingStatsForVehicle($id);
        $items = $reservationRepository->getRatingsForVehicle($id, $limit);

        return $this->json([
            'vehicleId'   => $id,
            'ratingAvg'   => $stats['avg'],
            'ratingCount' => $stats['count'],
            'items'       => $items,
        ], 200, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ]);
    }
}