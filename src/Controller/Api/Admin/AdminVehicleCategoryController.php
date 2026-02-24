<?php

namespace App\Controller\Api\Admin;

use App\Entity\VehicleCategory;
use App\Repository\VehicleCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class AdminVehicleCategoryController extends AbstractController
{
    #[Route('', name: 'api_admin_categories_list', methods: ['GET'])]
    public function list(Request $request, VehicleCategoryRepository $repo): JsonResponse
    {
        $showInactive = $request->query->get('showInactive') === '1';
        $search = $request->query->get('search');

        return $this->json($repo->adminList($search, $showInactive));
    }

    #[Route('', name: 'api_admin_categories_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = $request->toArray();

        $name = isset($payload['name']) ? trim((string)$payload['name']) : '';
        if ($name === '') {
            return $this->json(['message' => 'El nombre es obligatorio.'], 422);
        }

        $c = new VehicleCategory();
        $c->setName($name);
        $c->setDescription(isset($payload['description']) ? (string)$payload['description'] : null);

        if (array_key_exists('dailyPrice', $payload)) {
            $c->setDailyPrice((string)$payload['dailyPrice']);
        } else {
            // si querés forzar obligatorio, cambiamos esto
            $c->setDailyPrice('0.00');
        }

        if (array_key_exists('isActive', $payload)) {
            $c->setIsActive((bool)$payload['isActive']);
        }

        $em->persist($c);
        $em->flush();

        return $this->json([
            'id' => $c->getId(),
            'name' => $c->getName(),
            'description' => $c->getDescription(),
            'dailyPrice' => (float)$c->getDailyPrice(),
            'isActive' => (bool)$c->isActive(),
            'vehiclesCount' => 0,
        ], 201);
    }

    #[Route('/{id<\d+>}', name: 'api_admin_categories_update', methods: ['PUT'])]
    public function update(int $id, Request $request, VehicleCategoryRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $c = $repo->find($id);
        if (!$c) {
            return $this->json(['message' => 'Categoría no encontrada.'], 404);
        }

        $payload = $request->toArray();

        if (array_key_exists('name', $payload)) {
            $name = trim((string)$payload['name']);
            if ($name === '') {
                return $this->json(['message' => 'El nombre no puede estar vacío.'], 422);
            }
            $c->setName($name);
        }

        if (array_key_exists('description', $payload)) {
            $c->setDescription($payload['description'] !== null ? (string)$payload['description'] : null);
        }

        if (array_key_exists('dailyPrice', $payload)) {
            $c->setDailyPrice((string)$payload['dailyPrice']);
        }

        if (array_key_exists('isActive', $payload)) {
            $c->setIsActive((bool)$payload['isActive']);
        }

        $em->flush();

        return $this->json([
            'id' => $c->getId(),
            'name' => $c->getName(),
            'description' => $c->getDescription(),
            'dailyPrice' => $c->getDailyPrice() !== null ? (float)$c->getDailyPrice() : null,
            'isActive' => (bool)$c->isActive(),
            'vehiclesCount' => $repo->vehiclesCount($c->getId()),
        ]);
    }

    #[Route('/{id<\d+>}/toggle', name: 'api_admin_categories_toggle', methods: ['PATCH'])]
    public function toggle(int $id, VehicleCategoryRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $c = $repo->find($id);
        if (!$c) {
            return $this->json(['message' => 'Categoría no encontrada.'], 404);
        }

        $c->setIsActive(!$c->isActive());
        $em->flush();

        return $this->json([
            'id' => $c->getId(),
            'isActive' => (bool)$c->isActive(),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'api_admin_categories_delete', methods: ['DELETE'])]
    public function delete(int $id, VehicleCategoryRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $c = $repo->find($id);
        if (!$c) {
            return $this->json(['message' => 'Categoría no encontrada.'], 404);
        }

        $count = $repo->vehiclesCount($id);
        if ($count > 0) {
            return $this->json([
                'message' => 'No se puede eliminar: la categoría tiene vehículos asociados.',
                'vehiclesCount' => $count,
            ], 409);
        }

        $em->remove($c);
        $em->flush();

        return $this->json(['ok' => true]);
    }
}