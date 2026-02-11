<?php

namespace App\Controller\Api;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/reservations')]
class AdminReservationController extends AbstractController
{
    #[Route('', name: 'api_admin_reservations_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // ✅ Opcional: si querés forzar ROLE_ADMIN de verdad
        // if (!$this->isGranted('ROLE_ADMIN')) return $this->json(['error' => 'Forbidden'], 403);

        $status = $request->query->get('status');
        $from   = $request->query->get('from');
        $to     = $request->query->get('to');

        $qb = $em->getRepository(Reservation::class)->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->leftJoin('r.vehicle', 'v')
            ->leftJoin('r.pickupLocation', 'pl')
            ->leftJoin('r.dropoffLocation', 'dl')
            ->addSelect('u', 'v', 'pl', 'dl')
            ->orderBy('r.id', 'DESC');

        if ($status) {
            $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        if ($from) {
            try {
                $fromDate = new \DateTimeImmutable($from . ' 00:00:00');
                $qb->andWhere('r.startAt >= :from')->setParameter('from', $fromDate);
            } catch (\Exception $e) {}
        }

        if ($to) {
            try {
                $toDate = new \DateTimeImmutable($to . ' 23:59:59');
                $qb->andWhere('r.endAt <= :to')->setParameter('to', $toDate);
            } catch (\Exception $e) {}
        }

        $rows = $qb->getQuery()->getResult();

        $data = array_map(function (Reservation $r) {
            $user = $r->getUser();
            $vehicle = $r->getVehicle();
            $pickup = $r->getPickupLocation();
            $dropoff = $r->getDropoffLocation();

            return [
                'id' => $r->getId(),
                'userEmail' => $user ? $user->getEmail() : null,
                'vehicle' => $vehicle ? trim(($vehicle->getBrand() ?? '') . ' ' . ($vehicle->getModel() ?? '')) : null,
                'pickupLocation' => $pickup ? $pickup->getName() : null,
                'dropoffLocation' => $dropoff ? $dropoff->getName() : null,
                'startAt' => $r->getStartAt()?->format('Y-m-d'),
                'endAt' => $r->getEndAt()?->format('Y-m-d'),
                'totalPrice' => $r->getTotalPrice(),
                'status' => $r->getStatus(),
            ];
        }, $rows);

        return $this->json($data);
    }

    #[Route('/{id}/status', name: 'api_admin_reservations_update_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        // ✅ Opcional: si querés forzar ROLE_ADMIN de verdad
        // if (!$this->isGranted('ROLE_ADMIN')) return $this->json(['error' => 'Forbidden'], 403);

        $reservation = $em->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            return $this->json(['error' => 'Reserva no encontrada'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!$newStatus) {
            return $this->json(['error' => 'Estado requerido'], 422);
        }

        $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            return $this->json(['error' => 'Estado inválido'], 422);
        }

        $reservation->setStatus($newStatus);
        $em->flush();

        return $this->json([
            'message' => 'Estado actualizado',
            'id' => $reservation->getId(),
            'status' => $reservation->getStatus(),
        ]);
    }
}