<?php

namespace App\Controller\Api;

use App\Entity\Reservation;
use App\Entity\ReservationExtra;
use App\Repository\VehicleRepository;
use App\Repository\LocationRepository;
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
        LocationRepository $locationRepo
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        if (!isset($data['vehicleId'], $data['pickupLocationId'], $data['dropoffLocationId'], $data['startAt'], $data['endAt'])) {
            return $this->json(['error' => 'Faltan campos obligatorios'], 422);
        }

        $vehicle = $vehicleRepo->find($data['vehicleId']);
        $pickup = $locationRepo->find($data['pickupLocationId']);
        $dropoff = $locationRepo->find($data['dropoffLocationId']);

        if (!$vehicle || !$pickup || !$dropoff) {
            return $this->json(['error' => 'Datos de vehículo o sucursal inválidos'], 422);
        }

        // 🕒 Parsear fechas (sin horas)
        try {
            $startAt = new \DateTimeImmutable($data['startAt']);
            $endAt = new \DateTimeImmutable($data['endAt']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Formato de fecha inválido'], 400);
        }

        // ✅ Validar rango
        if ($endAt < $startAt) {
            return $this->json(['error' => 'La fecha de fin debe ser igual o posterior a la de inicio'], 422);
        }

        // 🔍 Comprobar solapamiento (por días)
        $conn = $em->getConnection();
        $sql = "
            SELECT COUNT(*) AS count
            FROM reservation r
            WHERE r.vehicle_id = :vehicleId
              AND r.status != 'cancelled'
              AND (
                    r.start_at <= :endAt
                    AND r.end_at >= :startAt
              )
        ";

        $result = $conn->executeQuery($sql, [
            'vehicleId' => $vehicle->getId(),
            'startAt' => $startAt->format('Y-m-d'),
            'endAt' => $endAt->format('Y-m-d'),
        ])->fetchAssociative();

        if ($result && $result['count'] > 0) {
            return $this->json(['error' => 'El vehículo no está disponible en las fechas seleccionadas'], 409);
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
