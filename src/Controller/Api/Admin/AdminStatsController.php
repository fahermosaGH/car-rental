<?php

namespace App\Controller\Api\Admin;

use App\Entity\Location;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/admin/stats')]
class AdminStatsController extends AbstractController
{
    #[Route('/general', name: 'api_admin_stats_general', methods: ['GET'])]
    public function general(EntityManagerInterface $em): Response
    {
        // Seguridad extra (igual ya está el access_control ^/api/admin ROLE_ADMIN)
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $usersCount     = $em->getRepository(User::class)->count([]);
        $vehiclesCount  = $em->getRepository(Vehicle::class)->count([]);
        $locationsCount = $em->getRepository(Location::class)->count([]);
        $reservasTotal  = $em->getRepository(Reservation::class)->count([]);

        // Reservas activas HOY (pisan el día) con estado pending/confirmed
        $todayStart = new \DateTimeImmutable('today');
        $todayEnd   = $todayStart->modify('+1 day');

        $reservasActivasHoy = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Reservation::class, 'r')
            ->where('r.status IN (:st)')
            ->andWhere(':todayStart < r.endAt')
            ->andWhere(':todayEnd > r.startAt')
            ->setParameter('st', ['pending', 'confirmed'])
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd)
            ->getQuery()
            ->getSingleScalarResult();

        // Cancelaciones del mes (si no usás "cancelled", queda 0 y no rompe)
        $monthStart = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0, 0);
        $monthEnd   = $monthStart->modify('+1 month');

        $cancelacionesMes = (int) $em->createQueryBuilder()
            ->select('COUNT(r2.id)')
            ->from(Reservation::class, 'r2')
            ->where('r2.status = :cancelled')
            ->andWhere('r2.startAt >= :mStart')
            ->andWhere('r2.startAt < :mEnd')
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('mStart', $monthStart)
            ->setParameter('mEnd', $monthEnd)
            ->getQuery()
            ->getSingleScalarResult();

        // Ingresos (simulado): suma totalPrice de confirmed en el mes (si null -> 0)
        $ingresosMes = (float) $em->createQueryBuilder()
            ->select('COALESCE(SUM(r3.totalPrice), 0)')
            ->from(Reservation::class, 'r3')
            ->where('r3.status = :confirmed')
            ->andWhere('r3.startAt >= :mStart')
            ->andWhere('r3.startAt < :mEnd')
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('mStart', $monthStart)
            ->setParameter('mEnd', $monthEnd)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->json([
            'users' => $usersCount,
            'vehicles' => $vehiclesCount,
            'locations' => $locationsCount,
            'reservationsTotal' => $reservasTotal,
            'reservationsActiveToday' => $reservasActivasHoy,
            'cancellationsThisMonth' => $cancelacionesMes,
            'incomeThisMonth' => $ingresosMes,
        ], Response::HTTP_OK, [], [
            'json_encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ]);
    }
}
