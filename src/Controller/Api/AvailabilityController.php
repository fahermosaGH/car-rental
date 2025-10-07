<?php

namespace App\Controller\Api;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class AvailabilityController extends AbstractController
{
    public function __construct(private ReservationRepository $reservations) {}

    #[Route('/booked-dates', name: 'api_booked_dates', methods: ['GET'])]
    public function bookedDates(Request $request): JsonResponse
    {
        $vehicleId = $request->query->get('vehicleId');
        $locationId = $request->query->get('locationId');

        if (!$vehicleId || !$locationId) {
            return $this->json(['booked' => []]);
        }

        $reservations = $this->reservations->findBookedRanges($vehicleId, $locationId);

        $booked = array_map(fn($r) => [
            'start' => $r['startAt']->format('Y-m-d'),
            'end'   => $r['endAt']->format('Y-m-d'),
        ], $reservations);

        return $this->json(['booked' => $booked]);
    }
}
