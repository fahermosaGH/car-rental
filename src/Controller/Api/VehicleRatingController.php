<?php

namespace App\Controller\Api;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class VehicleRatingController extends AbstractController
{
    #[Route('/api/vehicles/{id}/ratings', name: 'api_vehicle_ratings', methods: ['GET'])]
    public function ratings(int $id, ReservationRepository $reservations): JsonResponse
    {
        $summary = $reservations->getVehicleRatingSummary($id);
        $itemsRaw = $reservations->getVehicleRatings($id, 6);

        $items = array_map(static fn(array $r) => [
            'rating' => (int) $r['rating'],
            'comment' => $r['ratingComment'] ?? null,
            'endAt' => isset($r['endAt']) && $r['endAt'] ? $r['endAt']->format(\DateTimeInterface::ATOM) : null,
        ], $itemsRaw);

        return $this->json([
            'vehicleId' => $id,
            'ratingAvg' => $summary['avg'],
            'ratingCount' => $summary['count'],
            'items' => $items,
        ]);
    }
}