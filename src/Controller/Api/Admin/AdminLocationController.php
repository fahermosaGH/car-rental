<?php

namespace App\Controller\Api\Admin;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/locations')]
class AdminLocationController extends AbstractController
{
    #[Route('', name: 'api_admin_locations_list', methods: ['GET'])]
    public function list(LocationRepository $repo): JsonResponse
    {
        $locations = $repo->findBy([], ['id' => 'DESC']);

        $data = array_map(static fn(Location $l) => [
            'id' => $l->getId(),
            'name' => $l->getName(),
            'city' => $l->getCity(),
            'address' => $l->getAddress(),
            'latitude' => $l->getLatitude(),
            'longitude' => $l->getLongitude(),
            'isActive' => $l->isActive(),
        ], $locations);

        return $this->json($data);
    }

    #[Route('', name: 'api_admin_locations_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['error' => 'JSON inválido'], 400);

        $name = trim((string)($data['name'] ?? ''));
        $address = trim((string)($data['address'] ?? ''));

        if ($name === '' || $address === '') {
            return $this->json(['error' => 'name y address son obligatorios'], 422);
        }

        $loc = new Location();
        $loc->setName($name);
        $loc->setAddress($address);
        $loc->setCity(isset($data['city']) ? trim((string)$data['city']) : null);
        $loc->setLatitude(isset($data['latitude']) ? (float)$data['latitude'] : null);
        $loc->setLongitude(isset($data['longitude']) ? (float)$data['longitude'] : null);
        $loc->setIsActive(isset($data['isActive']) ? (bool)$data['isActive'] : true);

        $em->persist($loc);
        $em->flush();

        return $this->json([
            'message' => 'Sucursal creada',
            'id' => $loc->getId(),
        ], 201);
    }

    #[Route('/{id}', name: 'api_admin_locations_update', methods: ['PUT'])]
    public function update(int $id, Request $request, LocationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $loc = $repo->find($id);
        if (!$loc) return $this->json(['error' => 'No encontrada'], 404);

        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['error' => 'JSON inválido'], 400);

        if (array_key_exists('name', $data)) {
            $name = trim((string)$data['name']);
            if ($name === '') return $this->json(['error' => 'name no puede ser vacío'], 422);
            $loc->setName($name);
        }

        if (array_key_exists('address', $data)) {
            $address = trim((string)$data['address']);
            if ($address === '') return $this->json(['error' => 'address no puede ser vacío'], 422);
            $loc->setAddress($address);
        }

        if (array_key_exists('city', $data)) {
            $city = $data['city'] !== null ? trim((string)$data['city']) : null;
            $loc->setCity($city);
        }

        if (array_key_exists('latitude', $data)) {
            $loc->setLatitude($data['latitude'] !== null ? (float)$data['latitude'] : null);
        }

        if (array_key_exists('longitude', $data)) {
            $loc->setLongitude($data['longitude'] !== null ? (float)$data['longitude'] : null);
        }

        if (array_key_exists('isActive', $data)) {
            $loc->setIsActive((bool)$data['isActive']);
        }

        $em->flush();

        return $this->json(['message' => 'Sucursal actualizada']);
    }

    // Soft delete: desactivar
    #[Route('/{id}', name: 'api_admin_locations_delete', methods: ['DELETE'])]
    public function delete(int $id, LocationRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $loc = $repo->find($id);
        if (!$loc) return $this->json(['error' => 'No encontrada'], 404);

        $loc->setIsActive(false);
        $em->flush();

        return $this->json(['message' => 'Sucursal desactivada']);
    }
}
