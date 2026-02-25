<?php

namespace App\Controller\Api\Admin;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/reservations')]
class AdminReservationController extends AbstractController
{
    private const ALLOWED_STATUS = ['pending', 'confirmed', 'cancelled'];

    #[Route('', name: 'api_admin_reservations_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $status = $request->query->get('status'); // pending|confirmed|cancelled|null
        $fromStr = $request->query->get('from');  // YYYY-MM-DD|null
        $toStr   = $request->query->get('to');    // YYYY-MM-DD|null

        $qb = $em->getRepository(Reservation::class)->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->leftJoin('r.vehicle', 'v')->addSelect('v')
            ->leftJoin('r.pickupLocation', 'pl')->addSelect('pl')
            ->leftJoin('r.dropoffLocation', 'dl')->addSelect('dl')
            ->orderBy('r.id', 'DESC');

        if ($status && in_array($status, self::ALLOWED_STATUS, true)) {
            $qb->andWhere('r.status = :st')->setParameter('st', $status);
        }

        // Filtro por rango: solape con [from, to]
        $from = null; $to = null;
        try {
            if ($fromStr) $from = new \DateTimeImmutable($fromStr . ' 00:00:00');
            if ($toStr)   $to   = new \DateTimeImmutable($toStr . ' 23:59:59');
        } catch (\Throwable) {
            return $this->json(['error' => 'Formato de fecha inválido (usar YYYY-MM-DD)'], 400);
        }

        if ($from && $to) {
            $qb->andWhere('(:from < r.endAt) AND (:to > r.startAt)')
               ->setParameter('from', $from)
               ->setParameter('to', $to);
        } elseif ($from) {
            $qb->andWhere('r.endAt > :from')->setParameter('from', $from);
        } elseif ($to) {
            $qb->andWhere('r.startAt < :to')->setParameter('to', $to);
        }

        $rows = $qb->getQuery()->getResult();

        $data = array_map(static function (Reservation $r) {
            $v = $r->getVehicle();
            $pl = $r->getPickupLocation();
            $dl = $r->getDropoffLocation();
            $u = $r->getUser();

            return [
                'id' => $r->getId(),
                'status' => $r->getStatus(),
                'startAt' => $r->getStartAt()?->format('Y-m-d'),
                'endAt' => $r->getEndAt()?->format('Y-m-d'),
                'totalPrice' => $r->getTotalPrice() !== null ? (float)$r->getTotalPrice() : 0,
                'userEmail' => $u?->getEmail(),
                'vehicle' => $v ? trim(($v->getBrand() ?? '') . ' ' . ($v->getModel() ?? '')) : null,
                'pickupLocation' => $pl?->getName(),
                'dropoffLocation' => $dl?->getName(),
            ];
        }, $rows);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_admin_reservations_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var Reservation|null $r */
        $r = $em->getRepository(Reservation::class)->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->leftJoin('r.vehicle', 'v')->addSelect('v')
            ->leftJoin('v.category', 'c')->addSelect('c')
            ->leftJoin('r.pickupLocation', 'pl')->addSelect('pl')
            ->leftJoin('r.dropoffLocation', 'dl')->addSelect('dl')
            ->leftJoin('r.extras', 'e')->addSelect('e')
            ->where('r.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$r) return $this->json(['error' => 'Reserva no encontrada'], 404);

        $extras = [];
        foreach ($r->getExtras() as $e) {
            $extras[] = [
                'name' => $e->getName(),
                'price' => $e->getPrice() !== null ? (float)$e->getPrice() : 0,
            ];
        }

        $v = $r->getVehicle();
        return $this->json([
            'id' => $r->getId(),
            'status' => $r->getStatus(),
            'startAt' => $r->getStartAt()?->format('Y-m-d'),
            'endAt' => $r->getEndAt()?->format('Y-m-d'),
            'totalPrice' => $r->getTotalPrice() !== null ? (float)$r->getTotalPrice() : 0,
            'rating' => $r->getRating(),
            'ratingComment' => $r->getRatingComment(),

            'user' => $r->getUser() ? [
                'email' => $r->getUser()->getEmail(),
                'firstName' => $r->getUser()->getFirstName(),
                'lastName' => $r->getUser()->getLastName(),
            ] : null,

            'vehicle' => $v ? [
                'id' => $v->getId(),
                'brand' => $v->getBrand(),
                'model' => $v->getModel(),
                'category' => $v->getCategory()?->getName(),
            ] : null,

            'pickupLocation' => $r->getPickupLocation()?->getName(),
            'dropoffLocation' => $r->getDropoffLocation()?->getName(),
            'extras' => $extras,
        ]);
    }

    #[Route('/{id}/status', name: 'api_admin_reservations_update_status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['error' => 'JSON inválido'], 400);

        $newStatus = (string)($data['status'] ?? '');
        if (!in_array($newStatus, self::ALLOWED_STATUS, true)) {
            return $this->json([
                'error' => 'Status inválido',
                'allowed' => self::ALLOWED_STATUS
            ], 422);
        }

        /** @var Reservation|null $r */
        $r = $em->getRepository(Reservation::class)->find($id);
        if (!$r) return $this->json(['error' => 'Reserva no encontrada'], 404);

        $r->setStatus($newStatus);
        $em->flush();

        return $this->json(['message' => 'Estado actualizado', 'id' => $r->getId(), 'status' => $r->getStatus()]);
    }
}
