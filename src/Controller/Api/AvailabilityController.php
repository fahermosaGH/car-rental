<?php

namespace App\Controller\Api;

use App\Service\ReservationValidator;
use App\Repository\VehicleRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class AvailabilityController extends AbstractController
{
    public function __construct(
        private ReservationValidator $validator,
        private VehicleRepository $vehicles
    ) {}

    #[Route('/check-availability', name: 'api_check_availability', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $vehicleStr = $request->query->get('vehicle');
        $pickupStr  = $request->query->get('pickup');     // <-- nuevo
        $startStr   = $request->query->get('start');
        $endStr     = $request->query->get('end');
        $excludeStr = $request->query->get('excludeId');  // opcional (edición)

        if (!$vehicleStr || !$pickupStr || !$startStr || !$endStr) {
            return $this->json(['available' => false, 'message' => 'Parámetros faltantes'], 400);
        }

        try {
            $start = new DateTimeImmutable($startStr);
            $end   = new DateTimeImmutable($endStr);
        } catch (\Throwable) {
            return $this->json(['available' => false, 'message' => 'Formato de fecha inválido'], 400);
        }

        if ($start >= $end) {
            return $this->json(['available' => false, 'message' => 'Fin debe ser mayor a inicio'], 400);
        }

        $vehicle = $this->vehicles->find((int)$vehicleStr);
        if (!$vehicle) {
            return $this->json(['available' => false, 'message' => 'Vehículo inexistente'], 422);
        }

        $available = $this->validator->isAvailable(
            (int)$vehicleStr,
            (int)$pickupStr,                 // <-- pasa la sucursal
            $start,
            $end,
            $excludeStr ? (int)$excludeStr : null
        );

        return $this->json([
            'available' => $available,
            'message'   => $available ? 'Disponible' : 'Sin stock para ese rango en esa sucursal'
        ]);
    }
}
