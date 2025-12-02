<?php

namespace App\Controller\Api;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/locations')]
class LocationController extends AbstractController
{
    #[Route('', name: 'api_locations_list', methods: ['GET'])]
    public function list(LocationRepository $repo): JsonResponse
    {
        $locations = $repo->findBy(['isActive' => true]);

        $data = array_map(fn(Location $l) => [
            'id'        => $l->getId(),
            'name'      => $l->getName(),
            'address'   => $l->getAddress(),
            'city'      => $l->getCity(),
            'latitude'  => $l->getLatitude(),
            'longitude' => $l->getLongitude(),
        ], $locations);

        return $this->json($data);
    }
}
