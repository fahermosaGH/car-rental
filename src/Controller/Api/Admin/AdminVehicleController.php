<?php

namespace App\Controller\Api\Admin;

use App\Entity\Vehicle;
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
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'api_admin_vehicles_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $includeInactive = $request->query->getBoolean('includeInactive', false);

        // ✅ Ajustá esto según cómo sea tu Vehicle (si tiene isActive)
        if ($includeInactive) {
            $items = $this->vehicles->findBy([], ['id' => 'DESC']);
        } else {
            $items = $this->vehicles->findBy(['isActive' => true], ['id' => 'DESC']);
        }

        return $this->json(array_map([$this, 'toDto'], $items));
    }

    #[Route('', name: 'api_admin_vehicles_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->getJson($request);

        $v = new Vehicle();

        // ✅ AJUSTAR CAMPOS según tu entidad Vehicle
        $this->applyPayload($v, $data);

        // Si tu entidad tiene isActive
        if (method_exists($v, 'setIsActive') && !isset($data['isActive'])) {
            $v->setIsActive(true);
        }

        $this->em->persist($v);
        $this->em->flush();

        return $this->json($this->toDto($v), 201);
    }

    #[Route('/{id}', name: 'api_admin_vehicles_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $v = $this->vehicles->find($id);
        if (!$v) {
            return $this->json(['message' => 'Vehicle not found'], 404);
        }

        $data = $this->getJson($request);

        // ✅ AJUSTAR CAMPOS según tu entidad Vehicle
        $this->applyPayload($v, $data);

        $this->em->flush();

        return $this->json($this->toDto($v));
    }

    #[Route('/{id}', name: 'api_admin_vehicles_deactivate', methods: ['DELETE'])]
    public function deactivate(int $id): JsonResponse
    {
        $v = $this->vehicles->find($id);
        if (!$v) {
            return $this->json(['message' => 'Vehicle not found'], 404);
        }

        // ✅ Soft delete si existe isActive
        if (method_exists($v, 'setIsActive')) {
            $v->setIsActive(false);
        } else {
            // Si no tenés isActive, podés hacer remove (pero NO lo recomiendo)
            // $this->em->remove($v);
        }

        $this->em->flush();

        return $this->json(['ok' => true, 'id' => $id]);
    }

    private function getJson(Request $request): array
    {
        $raw = $request->getContent();
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function applyPayload(Vehicle $v, array $data): void
    {
        // ✅✅✅ AJUSTÁ ESTOS SETTERS A TU Vehicle.php REAL ✅✅✅
        // Ejemplo típico:
        if (isset($data['brand']) && method_exists($v, 'setBrand')) $v->setBrand((string)$data['brand']);
        if (isset($data['model']) && method_exists($v, 'setModel')) $v->setModel((string)$data['model']);
        if (isset($data['year']) && method_exists($v, 'setYear')) $v->setYear((int)$data['year']);

        // precio por día (ajustar nombre)
        if (isset($data['pricePerDay']) && method_exists($v, 'setPricePerDay')) $v->setPricePerDay((float)$data['pricePerDay']);
        if (isset($data['dailyPrice']) && method_exists($v, 'setDailyPrice')) $v->setDailyPrice((float)$data['dailyPrice']);

        // activo
        if (isset($data['isActive']) && method_exists($v, 'setIsActive')) $v->setIsActive((bool)$data['isActive']);

        // imagen (si existe)
        if (isset($data['imageUrl']) && method_exists($v, 'setImageUrl')) $v->setImageUrl((string)$data['imageUrl']);
    }

    private function toDto(Vehicle $v): array
    {
        // ✅✅✅ AJUSTÁ GETTERS A TU Vehicle.php REAL ✅✅✅
        return [
            'id' => method_exists($v, 'getId') ? $v->getId() : null,
            'brand' => method_exists($v, 'getBrand') ? $v->getBrand() : null,
            'model' => method_exists($v, 'getModel') ? $v->getModel() : null,
            'year' => method_exists($v, 'getYear') ? $v->getYear() : null,
            'dailyPrice' => method_exists($v, 'getDailyPrice') ? $v->getDailyPrice() : (method_exists($v, 'getPricePerDay') ? $v->getPricePerDay() : null),
            'isActive' => method_exists($v, 'getIsActive') ? $v->getIsActive() : true,
            'imageUrl' => method_exists($v, 'getImageUrl') ? $v->getImageUrl() : null,
        ];
    }
}
