<?php

namespace App\Controller\Api\Admin;

use App\Entity\VehicleCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class AdminVehicleCategoryController extends AbstractController
{
    #[Route('', name: 'api_admin_categories_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): JsonResponse
    {
        $items = $em->getRepository(VehicleCategory::class)->findBy([], ['id' => 'DESC']);

        $data = array_map(static fn(VehicleCategory $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'dailyPrice' => $c->getDailyPrice() !== null ? (float)$c->getDailyPrice() : null,
        ], $items);

        return $this->json($data);
    }
}