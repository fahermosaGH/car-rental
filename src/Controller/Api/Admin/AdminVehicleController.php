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

    #[Route('', name: 'api_admin_vehicles_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $includeInactive = $request->query->getBoolean('includeInactive', false);

        $items = $includeInactive
            ? $this->vehicles->findBy([], ['id' => 'DESC'])
            : $this->vehicles->findBy(['isActive' => true], ['id' => 'DESC']);

        return $this->json(array_map([$this, 'toDto'], $items));
    }

    #[Route('', name: 'api_admin_vehicles_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->getJson($request);

        $v = new Vehicle();

        $error = $this->applyPayload($v, $data, true);
        if ($error) {
            return $this->json(['error' => $error], 422);
        }

        if (!array_key_exists('isActive', $data) && method_exists($v, 'setIsActive')) {
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
            return $this->json(['error' => 'Vehículo no encontrado'], 404);
        }

        $data = $this->getJson($request);

        $error = $this->applyPayload($v, $data, false);
        if ($error) {
            return $this->json(['error' => $error], 422);
        }

        $this->em->flush();

        return $this->json($this->toDto($v));
    }

    #[Route('/{id}', name: 'api_admin_vehicles_deactivate', methods: ['DELETE'])]
    public function deactivate(int $id): JsonResponse
    {
        $v = $this->vehicles->find($id);
        if (!$v) {
            return $this->json(['error' => 'Vehículo no encontrado'], 404);
        }

        if (method_exists($v, 'setIsActive')) {
            $v->setIsActive(false);
        }

        $this->em->flush();

        return $this->json(['ok' => true, 'id' => $id]);
    }

    private function getJson(Request $request): array
    {
        $data = json_decode((string)$request->getContent(), true);
        return is_array($data) ? $data : [];
    }

    /**
     * @return string|null Mensaje de error (para devolver 422)
     */
    private function applyPayload(Vehicle $v, array $data, bool $isCreate): ?string
    {
        // brand/model
        if (array_key_exists('brand', $data) && method_exists($v, 'setBrand')) {
            $v->setBrand(trim((string)$data['brand']));
        }
        if (array_key_exists('model', $data) && method_exists($v, 'setModel')) {
            $v->setModel(trim((string)$data['model']));
        }

        // year (si en tu DB es NOT NULL, dejalo obligatorio)
        if (array_key_exists('year', $data) && method_exists($v, 'setYear')) {
            $year = $data['year'] !== null ? (int)$data['year'] : null;
            if ($isCreate && ($year === null || $year < 1900)) return 'year es obligatorio';
            if ($year !== null) $v->setYear($year);
        } elseif ($isCreate) {
            return 'year es obligatorio';
        }

        // seats (NOT NULL en DB → obligatorio en create)
        if (array_key_exists('seats', $data) && method_exists($v, 'setSeats')) {
            $seats = $data['seats'] !== null ? (int)$data['seats'] : null;
            if ($isCreate && ($seats === null || $seats <= 0)) return 'seats es obligatorio';
            if ($seats !== null) $v->setSeats($seats);
        } elseif ($isCreate) {
            return 'seats es obligatorio';
        }

        // transmission (NOT NULL en DB → obligatorio en create)
        if (array_key_exists('transmission', $data) && method_exists($v, 'setTransmission')) {
            $tr = trim((string)($data['transmission'] ?? ''));
            if ($isCreate && $tr === '') return 'transmission es obligatorio';
            if ($tr !== '') $v->setTransmission($tr);
        } elseif ($isCreate) {
            return 'transmission es obligatorio';
        }

        // dailyPriceOverride (front lo manda como dailyPrice)
        if (array_key_exists('dailyPrice', $data) && method_exists($v, 'setDailyPriceOverride')) {
            $val = $data['dailyPrice'];
            if ($val === '' || $val === null) {
                $v->setDailyPriceOverride(null);
            } else {
                $v->setDailyPriceOverride((string)$val); // decimal como string
            }
        }

        // isActive
        if (array_key_exists('isActive', $data) && method_exists($v, 'setIsActive')) {
            $v->setIsActive((bool)$data['isActive']);
        }

        // categoryId → setCategory (obligatorio en create)
        if (array_key_exists('categoryId', $data)) {
            $cid = (int)($data['categoryId'] ?? 0);
            if ($isCreate && $cid <= 0) return 'categoryId es obligatorio';

            if ($cid > 0 && method_exists($v, 'setCategory')) {
                $cat = $this->categories->find($cid);
                if (!$cat) return 'Categoría inexistente';
                $v->setCategory($cat);
            }
        } elseif ($isCreate) {
            return 'categoryId es obligatorio';
        }

        return null;
    }

    private function toDto(Vehicle $v): array
    {
        $cat = method_exists($v, 'getCategory') ? $v->getCategory() : null;

        return [
            'id'           => $v->getId(),
            'brand'        => $v->getBrand(),
            'model'        => $v->getModel(),
            'year'         => $v->getYear(),
            'seats'        => $v->getSeats(),
            'transmission' => $v->getTransmission(),
            'dailyPrice'   => $v->getDailyPriceOverride() !== null ? (float)$v->getDailyPriceOverride() : null,
            'isActive'     => (bool)$v->isActive(),
            'categoryId'   => $cat?->getId(),
            'categoryName' => $cat?->getName(),
            'category'     => $cat ? ['id' => $cat->getId(), 'name' => $cat->getName()] : null,
        ];
    }
}
