<?php

namespace App\Controller\Api\Admin;

use App\Entity\Location;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/stats')]
class AdminStatsController extends AbstractController
{
    #[Route('/general', name: 'api_admin_stats_general', methods: ['GET'])]
    public function general(EntityManagerInterface $em): JsonResponse
    {
        // Rango "hoy"
        $todayStart = new \DateTimeImmutable('today');                 // 00:00 local (según timezone PHP)
        $tomorrowStart = $todayStart->modify('+1 day');               // 00:00 mañana

        // Rango "mes actual"
        $monthStart = $todayStart->modify('first day of this month');  // 00:00 día 1
        $nextMonthStart = $monthStart->modify('first day of next month');

        // Usuarios
        $users = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->getQuery()
            ->getSingleScalarResult();

        // Vehículos en flota (activos)
        $vehicles = (int) $em->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(Vehicle::class, 'v')
            ->where('v.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        // Sucursales activas
        $locations = (int) $em->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from(Location::class, 'l')
            ->where('l.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        // Reservas totales (históricas)
        $reservationsTotal = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Reservation::class, 'r')
            ->getQuery()
            ->getSingleScalarResult();

        // Activas hoy: status IN (pending, confirmed) y solapan el día de hoy
        // Solape: startAt < tomorrowStart AND endAt > todayStart
        $reservationsActiveToday = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Reservation::class, 'r')
            ->where('r.status IN (:st)')
            ->andWhere('r.startAt < :tomorrowStart')
            ->andWhere('r.endAt > :todayStart')
            ->setParameter('st', ['pending', 'confirmed'])
            ->setParameter('todayStart', $todayStart)
            ->setParameter('tomorrowStart', $tomorrowStart)
            ->getQuery()
            ->getSingleScalarResult();

        // Cancelaciones del mes (usamos startAt como fecha de referencia porque Reservation no tiene createdAt)
        $cancellationsThisMonth = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Reservation::class, 'r')
            ->where('r.status = :cancelled')
            ->andWhere('r.startAt >= :monthStart')
            ->andWhere('r.startAt < :nextMonthStart')
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('nextMonthStart', $nextMonthStart)
            ->getQuery()
            ->getSingleScalarResult();

        // Ingresos del mes: SUM(totalPrice) de confirmed del mes (también por startAt)
        $incomeRaw = $em->createQueryBuilder()
            ->select('COALESCE(SUM(r.totalPrice), 0)')
            ->from(Reservation::class, 'r')
            ->where('r.status = :confirmed')
            ->andWhere('r.startAt >= :monthStart')
            ->andWhere('r.startAt < :nextMonthStart')
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('nextMonthStart', $nextMonthStart)
            ->getQuery()
            ->getSingleScalarResult();

        // Doctrine devuelve DECIMAL como string
        $incomeThisMonth = (float) $incomeRaw;

        return $this->json([
            'users' => $users,
            'vehicles' => $vehicles,
            'locations' => $locations,
            'reservationsTotal' => $reservationsTotal,
            'reservationsActiveToday' => $reservationsActiveToday,
            'cancellationsThisMonth' => $cancellationsThisMonth,
            'incomeThisMonth' => $incomeThisMonth,
        ]);
    }
}