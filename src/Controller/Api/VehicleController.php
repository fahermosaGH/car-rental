<?php

namespace App\Controller\Api;

use App\Entity\Vehicle;
use App\Repository\VehicleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
                'id' => $v->getId(),
                'brand' => $v->getBrand(),
                'model' => $v->getModel(),
                'year' => $v->getYear(),
                'seats' => $v->getSeats(),
                'transmission' => $v->getTransmission(),
                'dailyRate' => $v->getDailyPriceOverride(), // ✅ campo real de tu tabla
                'isActive' => $v->isActive(),
                'category' => $v->getCategory() ? $v->getCategory()->getName() : null,
            ];
        }, $vehicles);

        return $this->json($data, Response::HTTP_OK, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ]);
    }
}
