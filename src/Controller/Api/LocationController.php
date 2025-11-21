<?php

namespace App\Controller\Api;

use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/locations')]
class LocationController extends AbstractController
{
    #[Route('', name: 'api_locations_list', methods: ['GET'])]
    public function list(LocationRepository $locationRepository): Response
    {
        $locations = $locationRepository->findAll();

        $data = array_map(function ($l) {
            return [
                'id'        => $l->getId(),
                'name'      => $l->getName(),
                'address'   => $l->getAddress(),

                // ❗ No existe esta columna, devolvemos string vacío
                'city'      => '',

                // ❗ Tampoco existen en tu entidad ni DB → devolvemos null
                'latitude'  => null,
                'longitude' => null,
            ];
        }, $locations);

        return $this->json($data);
    }
}
