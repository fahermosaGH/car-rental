<?php

namespace App\Controller\Api;

use App\Entity\Reservation;
use App\Entity\ReservationIncident;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/incidents')]
class ReservationIncidentController extends AbstractController
{
    #[Route('', name: 'api_incidents_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user = null
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        $reservationId = (int)($data['reservationId'] ?? 0);
        $description   = trim((string)($data['description'] ?? ''));

        if ($reservationId <= 0 || $description === '') {
            return $this->json(['error' => 'reservationId y description son obligatorios'], 422);
        }

        /** @var Reservation|null $reservation */
        $reservation = $em->getRepository(Reservation::class)->find($reservationId);
        if (!$reservation) {
            return $this->json(['error' => 'Reserva no encontrada'], 404);
        }

        if ($reservation->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        // No permitir incidentes en reservas canceladas
        if ($reservation->getStatus() === 'cancelled') {
            return $this->json(['error' => 'No se puede reportar incidente en una reserva cancelada'], 422);
        }

        // Evitar duplicados abiertos para la misma reserva
        $openExists = (int)$em->createQueryBuilder()
            ->select('COUNT(i.id)')
            ->from(ReservationIncident::class, 'i')
            ->where('i.reservation = :r')
            ->andWhere('i.status = :st')
            ->setParameter('r', $reservation)
            ->setParameter('st', ReservationIncident::STATUS_OPEN)
            ->getQuery()->getSingleScalarResult();

        if ($openExists > 0) {
            return $this->json(['error' => 'Ya existe un incidente abierto para esta reserva'], 409);
        }

        $incident = new ReservationIncident();
        $incident->setReservation($reservation);
        $incident->setVehicleUnit($reservation->getVehicleUnit()); // la unidad asignada al momento
        $incident->setDescription($description);
        $incident->setStatus(ReservationIncident::STATUS_OPEN);

        $em->persist($incident);
        $em->flush();

        return $this->json([
            'ok' => true,
            'id' => $incident->getId(),
            'message' => 'Incidente creado',
        ], 201);
    }

    #[Route('/my', name: 'api_incidents_my', methods: ['GET'])]
    public function myIncidents(
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user = null
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $rows = $em->createQueryBuilder()
            ->select('i, r, u')
            ->from(ReservationIncident::class, 'i')
            ->leftJoin('i.reservation', 'r')->addSelect('r')
            ->leftJoin('i.vehicleUnit', 'u')->addSelect('u')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.id', 'DESC')
            ->getQuery()->getResult();

        $data = array_map(static function (ReservationIncident $i) {
            $r = $i->getReservation();
            return [
                'id' => $i->getId(),
                'status' => $i->getStatus(),
                'createdAt' => $i->getCreatedAt()->format('c'),
                'description' => $i->getDescription(),
                'reservationId' => $r?->getId(),
                'unitPlate' => $i->getVehicleUnit()?->getPlate(),
            ];
        }, $rows);

        return new JsonResponse($data);
    }
}
