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
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LocationRepository $locations,
    ) {}

    #[Route('', name: 'api_admin_locations_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // Admin ve activas e inactivas
        $items = $this->locations->findBy([], ['id' => 'DESC']);
        $data = array_map(fn(Location $l) => $this->toArray($l), $items);

        return $this->json($data);
    }

    #[Route('', name: 'api_admin_locations_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = $this->getJson($request);

        $name = trim((string)($payload['name'] ?? ''));
        $address = trim((string)($payload['address'] ?? ''));
        $city = array_key_exists('city', $payload) ? trim((string)$payload['city']) : null;

        if ($name === '') {
            return $this->json(['message' => 'name es requerido'], 400);
        }
        if ($address === '') {
            return $this->json(['message' => 'address es requerido'], 400);
        }

        $loc = new Location();
        $loc->setName($name);
        $loc->setAddress($address);
        $loc->setCity($city ?: null);

        // isActive opcional
        if (array_key_exists('isActive', $payload)) {
            $loc->setIsActive((bool)$payload['isActive']);
        } else {
            $loc->setIsActive(true);
        }

        // lat/lon opcionales
        if (array_key_exists('latitude', $payload)) {
            $loc->setLatitude($payload['latitude'] !== null ? (float)$payload['latitude'] : null);
        }
        if (array_key_exists('longitude', $payload)) {
            $loc->setLongitude($payload['longitude'] !== null ? (float)$payload['longitude'] : null);
        }

        $this->em->persist($loc);
        $this->em->flush();

        // Devolvemos el objeto completo creado (útil para UI)
        return $this->json($this->toArray($loc), 201);
    }

    #[Route('/{id}', name: 'api_admin_locations_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $loc = $this->locations->find($id);
        if (!$loc) {
            return $this->json(['message' => 'Location no encontrada'], 404);
        }

        $payload = $this->getJson($request);

        // Update parcial permitido
        if (array_key_exists('name', $payload)) {
            $name = trim((string)$payload['name']);
            if ($name === '') {
                return $this->json(['message' => 'name no puede ser vacío'], 400);
            }
            $loc->setName($name);
        }

        if (array_key_exists('address', $payload)) {
            $address = trim((string)$payload['address']);
            if ($address === '') {
                return $this->json(['message' => 'address no puede ser vacío'], 400);
            }
            $loc->setAddress($address);
        }

        if (array_key_exists('city', $payload)) {
            $city = $payload['city'];
            $loc->setCity($city !== null ? trim((string)$city) : null);
        }

        if (array_key_exists('isActive', $payload)) {
            $loc->setIsActive((bool)$payload['isActive']);
        }

        if (array_key_exists('latitude', $payload)) {
            $loc->setLatitude($payload['latitude'] !== null ? (float)$payload['latitude'] : null);
        }

        if (array_key_exists('longitude', $payload)) {
            $loc->setLongitude($payload['longitude'] !== null ? (float)$payload['longitude'] : null);
        }

        $this->em->flush();

        // Devolvemos el objeto actualizado
        return $this->json($this->toArray($loc));
    }

    // Soft delete: desactivar
    #[Route('/{id}', name: 'api_admin_locations_deactivate', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deactivate(int $id): JsonResponse
    {
        $loc = $this->locations->find($id);
        if (!$loc) {
            return $this->json(['message' => 'Location no encontrada'], 404);
        }

        $loc->setIsActive(false);
        $this->em->flush();

        return $this->json([
            'ok' => true,
            'id' => $loc->getId(),
            'isActive' => $loc->isActive(),
        ]);
    }

    private function toArray(Location $l): array
    {
        return [
            'id' => $l->getId(),
            'name' => $l->getName(),
            'city' => $l->getCity(),
            'address' => $l->getAddress(),
            'latitude' => $l->getLatitude(),
            'longitude' => $l->getLongitude(),
            'isActive' => $l->isActive(),
        ];
    }

    /**
     * Decodifica JSON de forma segura:
     * - Si viene vacío, devuelve []
     * - Si viene JSON inválido, devuelve []
     */
    private function getJson(Request $request): array
    {
        $raw = $request->getContent();
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
