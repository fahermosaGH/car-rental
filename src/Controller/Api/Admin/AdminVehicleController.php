<?php

namespace App\Controller\Api\Admin;

use App\Entity\Vehicle;
use App\Repository\VehicleCategoryRepository;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/vehicles')]
#[IsGranted('ROLE_ADMIN')]
class AdminVehicleController extends AbstractController
{
    public function __construct(
        private readonly VehicleRepository $vehicles,
        private readonly VehicleCategoryRepository $categories,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $includeInactive = $request->query->getBoolean('includeInactive', false);

        $items = $includeInactive
            ? $this->vehicles->findBy([], ['id' => 'DESC'])
            : $this->vehicles->findBy(['isActive' => true], ['id' => 'DESC']);

        return $this->json(array_map([$this, 'toDto'], $items));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $v = new Vehicle();

        $this->applyPayload($v, $data, true);

        $this->em->persist($v);
        $this->em->flush();

        return $this->json($this->toDto($v), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $v = $this->vehicles->find($id);
        if (!$v) return $this->json(['error' => 'Vehículo no encontrado'], 404);

        $data = json_decode($request->getContent(), true) ?? [];
        $this->applyPayload($v, $data, false);

        $this->em->flush();

        return $this->json($this->toDto($v));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function deactivate(int $id): JsonResponse
    {
        $v = $this->vehicles->find($id);
        if (!$v) return $this->json(['error' => 'Vehículo no encontrado'], 404);

        $v->setIsActive(false);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }

    private function applyPayload(Vehicle $v, array $data, bool $isCreate): void
    {
        if (isset($data['brand'])) $v->setBrand(trim($data['brand']));
        if (isset($data['model'])) $v->setModel(trim($data['model']));
        if (isset($data['year'])) $v->setYear((int)$data['year']);
        if (isset($data['seats'])) $v->setSeats((int)$data['seats']);
        if (isset($data['transmission'])) $v->setTransmission($data['transmission']);
        if (isset($data['dailyPrice'])) {
            $v->setDailyPriceOverride($data['dailyPrice'] !== null ? (string)$data['dailyPrice'] : null);
        }
        if (isset($data['isActive'])) $v->setIsActive((bool)$data['isActive']);

        if (isset($data['imageUrl'])) {
            $v->setImageUrl($data['imageUrl']);
        }

        if (isset($data['categoryId'])) {
            $cat = $this->categories->find((int)$data['categoryId']);
            if ($cat) $v->setCategory($cat);
        }
    }

    private function toDto(Vehicle $v): array
    {
        $cat = $v->getCategory();

        return [
            'id' => $v->getId(),
            'brand' => $v->getBrand(),
            'model' => $v->getModel(),
            'year' => $v->getYear(),
            'seats' => $v->getSeats(),
            'transmission' => $v->getTransmission(),
            'dailyPrice' => $v->getDailyPriceOverride() !== null ? (float)$v->getDailyPriceOverride() : null,
            'isActive' => (bool)$v->isActive(),
            'categoryId' => $cat?->getId(),
            'categoryName' => $cat?->getName(),
            'imageUrl' => $v->getImageUrl(),
        ];
    }
}