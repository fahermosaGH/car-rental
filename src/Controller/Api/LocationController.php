<?php

namespace App\Controller\Api;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/locations')]
class LocationController extends AbstractController
{
    #[Route('', name: 'api_locations_list', methods: ['GET'])]
    public function list(LocationRepository $repo): Response
    {
        $locations = $repo->findAll();

        $data = array_map(fn(Location $loc) => [
            'id' => $loc->getId(),
            'name' => $loc->getName(),
            'address' => $loc->getAddress(),
            'city' => $loc->getCity(),
        ], $locations);

        return $this->json($data);
    }
}
