<?php

namespace App\Controller\Api;

use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class MyReservationsController extends AbstractController
{
    #[Route('/my-reservations', name: 'api_my_reservations', methods: ['GET'])]
    public function myReservations(
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        // Si no hay usuario autenticado → 401
        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $repo = $em->getRepository(Reservation::class);

        // ✅ Traer reservas del usuario autenticado, ordenadas por fecha de inicio DESC
        $reservations = $repo->findBy(
            ['user' => $user],
            ['startAt' => 'DESC']
        );

        $data = array_map(function (Reservation $r) {
            $vehicle = $r->getVehicle();
            $pickup  = $r->getPickupLocation();
            $dropoff = $r->getDropoffLocation();

            return [
                'id' => $r->getId(),
                'vehicleName' => $vehicle
                    ? trim(($vehicle->getBrand() ?? '') . ' ' . ($vehicle->getModel() ?? ''))
                    : 'Vehículo',
                'pickupLocationName' => $pickup?->getName() ?? 'Sucursal origen',
                'dropoffLocationName' => $dropoff?->getName() ?? 'Sucursal destino',
                'startAt' => $r->getStartAt()?->format('Y-m-d'),
                'endAt'   => $r->getEndAt()?->format('Y-m-d'),
                'totalPrice' => $r->getTotalPrice() !== null
                    ? (float) $r->getTotalPrice()
                    : 0,
                'status' => $r->getStatus() ?? 'confirmed',
            ];
        }, $reservations);

        return $this->json($data);
    }
}

