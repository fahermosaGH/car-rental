<?php

namespace App\Controller\Api;

use App\Service\ReservationValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AvailabilityController extends AbstractController
{
    #[Route('/api/check-availability', name: 'api_check_availability')]
    public function checkAvailability(Request $request, ReservationValidator $validator): JsonResponse
    {
        $vehicleId  = (int) $request->query->get('vehicleId');
        $startAtStr = (string) $request->query->get('startAt');
        $endAtStr   = (string) $request->query->get('endAt');

        $startAt = $this->parseDateTimeFlexible($startAtStr);
        $endAt   = $this->parseDateTimeFlexible($endAtStr);

        if (!$vehicleId || !$startAt || !$endAt) {
            return $this->json(['available' => false, 'error' => 'missing_parameters']);
        }

        $available = $validator->isVehicleAvailable($vehicleId, $startAt, $endAt);

        return $this->json([
            'available' => $available,
            'message' => $available
                ? '✅ Vehículo disponible.'
                : '🚫 Vehículo NO disponible en ese rango.'
        ]);
    }

    private function parseDateTimeFlexible(?string $value): ?\DateTimeImmutable
    {
        if (!$value) return null;

        if (str_contains($value, 'T')) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);
            if ($dt instanceof \DateTimeImmutable) return $dt;
        }

        if (preg_match('#^\d{2}/\d{2}/\d{4} \d{2}:\d{2}$#', $value)) {
            $dt = \DateTimeImmutable::createFromFormat('d/m/Y H:i', $value);
            if ($dt instanceof \DateTimeImmutable) return $dt;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
