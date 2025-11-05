<?php

namespace App\Controller\Api;

use App\Entity\Vehicle;
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

        $data = array_map(function (Vehicle $v) {
            return [
                'id'           => $v->getId(),
                'brand'        => $v->getBrand(),
                'model'        => $v->getModel(),
                'year'         => $v->getYear(),
                'seats'        => $v->getSeats(),
                'transmission' => $v->getTransmission(),
                'dailyRate'    => $v->getDailyPriceOverride(), // campo real de tu tabla
                'isActive'     => $v->isActive(),
                'category'     => $v->getCategory() ? $v->getCategory()->getName() : null,
            ];
        }, $vehicles);

        return $this->json($data, Response::HTTP_OK, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ]);
    }

    // 🆕 Disponibilidad real por sucursal + fechas
    #[Route('/available', name: 'api_vehicles_available', methods: ['GET'])]
    public function available(Request $request, VehicleRepository $vehicleRepository): Response
    {
        // Nombres alineados con tu front (resultados.component.ts)
        $pickupStr = $request->query->get('pickupLocationId');
        $startStr  = $request->query->get('startAt'); // 'YYYY-MM-DD' o ISO
        $endStr    = $request->query->get('endAt');

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

        $vehicles = $vehicleRepository->findAvailableWithStock((int)$pickupStr, $start, $end);

        $data = array_map(function (Vehicle $v) {
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
}

