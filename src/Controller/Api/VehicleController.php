<?php

namespace App\Controller\Api;

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

        $data = array_map(function ($v) {
            return [
                'id' => $v->getId(),
                'brand' => $v->getBrand(),
                'model' => $v->getModel(),
                'year' => $v->getYear(),
                'seats' => $v->getSeats(),
                'transmission' => $v->getTransmission(),
                'dailyRate' => $v->getDailyPriceOverride(), // ✅ Campo correcto
                'isActive' => $v->isActive(),
                'category' => $v->getCategory()?->getName() ?? null, // si tu categoría tiene nombre
            ];
        }, $vehicles);

        return $this->json($data);
    }
}
