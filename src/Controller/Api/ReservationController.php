<?php

namespace App\Controller\Api;

use App\Entity\Reservation;
use App\Entity\ReservationExtra;
use App\Repository\VehicleRepository;
use App\Repository\LocationRepository;
use App\Service\ReservationValidator; // ✅ usar el validador unificado
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/reservations')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'api_reservations_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        VehicleRepository $vehicleRepo,
        LocationRepository $locationRepo,
        ReservationValidator $validator // ✅ inyectado
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        if (!isset($data['vehicleId'], $data['pickupLocationId'], $data['dropoffLocationId'], $data['startAt'], $data['endAt'])) {
            return $this->json(['error' => 'Faltan campos obligatorios'], 422);
        }

        $vehicle = $vehicleRepo->find($data['vehicleId']);
        $pickup  = $locationRepo->find($data['pickupLocationId']);
        $dropoff = $locationRepo->find($data['dropoffLocationId']);

        if (!$vehicle || !$pickup || !$dropoff) {
            return $this->json(['error' => 'Datos de vehículo o sucursal inválidos'], 422);
        }

        // 🕒 Parsear fechas (día completo / ISO)
        try {
            $startAt = new \DateTimeImmutable($data['startAt']);
            $endAt   = new \DateTimeImmutable($data['endAt']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Formato de fecha inválido'], 400);
        }

        // ✅ Rango válido (fin EXCLUSIVO, consistente con Availability)
        if ($startAt >= $endAt) {
            return $this->json(['error' => 'La fecha de fin debe ser posterior a la de inicio'], 422);
        }

        // 🔍 Revalidar disponibilidad con el mismo criterio del sistema (stock por sucursal + solape fin exclusivo)
        $available = $validator->isAvailable(
            $vehicle->getId(),
            $pickup->getId(),
            $startAt,
            $endAt,
            null // alta (sin excluir id)
        );

        if (!$available) {
            return $this->json(['error' => 'El vehículo no está disponible en las fechas seleccionadas (sucursal/stock).'], 409);
        }

        // 🚗 Crear reserva
        $reservation = new Reservation();
        $reservation->setVehicle($vehicle);
        $reservation->setPickupLocation($pickup);
        $reservation->setDropoffLocation($dropoff);
        $reservation->setStartAt($startAt);
        $reservation->setEndAt($endAt);
        $reservation->setStatus('confirmed');
        $reservation->setTotalPrice($data['totalPrice'] ?? 0);

        // 🧾 Extras
        if (!empty($data['extras']) && is_array($data['extras'])) {
            foreach ($data['extras'] as $extraData) {
                if (!empty($extraData['name']) && isset($extraData['price'])) {
                    $extra = new ReservationExtra();
                    $extra->setName($extraData['name']);
                    $extra->setPrice($extraData['price']);
                    $reservation->addExtra($extra);
                }
            }
        }

        $em->persist($reservation);
        $em->flush();

        return $this->json([
            'message' => '✅ Reserva creada correctamente',
            'id' => $reservation->getId(),
        ], 201);
    }

    #[Route('', name: 'api_reservations_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em): Response
    {
        $reservas = $em->getRepository(Reservation::class)->findAll();

        $data = array_map(fn($r) => [
            'id' => $r->getId(),
            'vehicle' => (string)$r->getVehicle(),
            'pickupLocation' => (string)$r->getPickupLocation(),
            'dropoffLocation' => (string)$r->getDropoffLocation(),
            'startAt' => $r->getStartAt()->format('Y-m-d'),
            'endAt' => $r->getEndAt()->format('Y-m-d'),
            'totalPrice' => $r->getTotalPrice(),
            'status' => $r->getStatus(),
        ], $reservas);

        return $this->json($data);
    }
}

