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

        $ids = array_map(static fn($v) => (int)$v->getId(), $vehicles);
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

                // ✅ IMAGEN
                'imageUrl'     => method_exists($v, 'getImageUrl') ? $v->getImageUrl() : null,

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

        // Siempre usamos el repo que ya devuelve imageUrl en el SELECT ✅
        $categoryName = ($category && trim($category) !== '') ? trim($category) : null;

        $rows = $vehicleRepository->findAvailableWithStockInfo(
            (int)$pickupStr,
            $start,
            $end,
            $categoryName
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

                // ✅ IMAGEN (sale del query del repo)
                'imageUrl'       => $r['imageUrl'] ?? null,

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
}