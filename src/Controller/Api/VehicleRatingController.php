<?php

namespace App\Controller\Api;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class VehicleRatingController extends AbstractController
{
    #[Route('/api/vehicles/{id}/ratings', name: 'api_vehicle_ratings', methods: ['GET'])]
    public function vehicleRatings(int $id, Request $request, ReservationRepository $reservationRepo): JsonResponse
    {
        $limit = (int)($request->query->get('limit', 12));
        $limit = max(1, min(100, $limit));

        $stats = $reservationRepo->getRatingStatsForVehicle($id);
        $items = $reservationRepo->getRatingsForVehicle($id, $limit);

        return $this->json([
            'vehicleId' => $id,
            'ratingAvg' => $stats['avg'],
            'ratingCount' => $stats['count'],
            'items' => $items,
        ]);
    }
}