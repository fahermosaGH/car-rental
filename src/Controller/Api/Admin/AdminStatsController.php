<?php

namespace App\Controller\Api\Admin;

use App\Entity\Location;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleUnit;
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
        $todayStart = new \DateTimeImmutable('today');
        $tomorrowStart = $todayStart->modify('+1 day');

        $monthStart = $todayStart->modify('first day of this month');
        $nextMonthStart = $monthStart->modify('first day of next month');

        // Usuarios
        $users = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u')
            ->getQuery()
            ->getSingleScalarResult();

        // Vehículos activos
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

        // Reservas totales
        $reservationsTotal = (int) $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Reservation::class, 'r')
            ->getQuery()
            ->getSingleScalarResult();

        // Activas hoy
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

        // Cancelaciones del mes
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

        // Ingresos del mes
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

        $incomeThisMonth = (float) $incomeRaw;

        // Unidades por estado
        $unitsAvailable = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(VehicleUnit::class, 'u')
            ->where('u.status = :st')
            ->setParameter('st', VehicleUnit::STATUS_AVAILABLE)
            ->getQuery()
            ->getSingleScalarResult();

        $unitsMaintenance = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(VehicleUnit::class, 'u')
            ->where('u.status = :st')
            ->setParameter('st', VehicleUnit::STATUS_MAINTENANCE)
            ->getQuery()
            ->getSingleScalarResult();

        $unitsInactive = (int) $em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(VehicleUnit::class, 'u')
            ->where('u.status = :st')
            ->setParameter('st', VehicleUnit::STATUS_INACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        // =========================
        // Rankings del mes (confirmadas)
        // =========================
        $rankLimit = 5;

        // 1) Vehículo más requerido
        $topVehiclesRaw = $em->createQueryBuilder()
            ->select("CONCAT(v.brand, ' ', v.model) AS label, COUNT(r.id) AS value")
            ->from(Reservation::class, 'r')
            ->join('r.vehicle', 'v')
            ->where('r.status = :confirmed')
            ->andWhere('r.startAt >= :monthStart')
            ->andWhere('r.startAt < :nextMonthStart')
            ->groupBy('v.id')
            ->orderBy('value', 'DESC')
            ->setMaxResults($rankLimit)
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('nextMonthStart', $nextMonthStart)
            ->getQuery()
            ->getArrayResult();

        // 2) Ubicación más alquilada (pickup)
        $topPickupLocationsRaw = $em->createQueryBuilder()
            ->select('l.name AS label, COUNT(r.id) AS value')
            ->from(Reservation::class, 'r')
            ->join('r.pickupLocation', 'l')
            ->where('r.status = :confirmed')
            ->andWhere('r.startAt >= :monthStart')
            ->andWhere('r.startAt < :nextMonthStart')
            ->groupBy('l.id')
            ->orderBy('value', 'DESC')
            ->setMaxResults($rankLimit)
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('nextMonthStart', $nextMonthStart)
            ->getQuery()
            ->getArrayResult();

        // 3) Categoría más vendida
        $topCategoriesRaw = $em->createQueryBuilder()
            ->select('c.name AS label, COUNT(r.id) AS value')
            ->from(Reservation::class, 'r')
            ->join('r.vehicle', 'v')
            ->join('v.category', 'c')
            ->where('r.status = :confirmed')
            ->andWhere('r.startAt >= :monthStart')
            ->andWhere('r.startAt < :nextMonthStart')
            ->groupBy('c.id')
            ->orderBy('value', 'DESC')
            ->setMaxResults($rankLimit)
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('nextMonthStart', $nextMonthStart)
            ->getQuery()
            ->getArrayResult();

        // 4) Ingresos por ubicación (pickup)
        $incomeByLocationRaw = $em->createQueryBuilder()
            ->select('l.name AS label, COALESCE(SUM(r.totalPrice), 0) AS value')
            ->from(Reservation::class, 'r')
            ->join('r.pickupLocation', 'l')
            ->where('r.status = :confirmed')
            ->andWhere('r.startAt >= :monthStart')
            ->andWhere('r.startAt < :nextMonthStart')
            ->groupBy('l.id')
            ->orderBy('value', 'DESC')
            ->setMaxResults($rankLimit)
            ->setParameter('confirmed', 'confirmed')
            ->setParameter('monthStart', $monthStart)
            ->setParameter('nextMonthStart', $nextMonthStart)
            ->getQuery()
            ->getArrayResult();

        // Normalización a tipos simples
        $topVehicles = array_map(fn($x) => ['label' => (string) $x['label'], 'value' => (int) $x['value']], $topVehiclesRaw);
        $topPickupLocations = array_map(fn($x) => ['label' => (string) $x['label'], 'value' => (int) $x['value']], $topPickupLocationsRaw);
        $topCategories = array_map(fn($x) => ['label' => (string) $x['label'], 'value' => (int) $x['value']], $topCategoriesRaw);
        $incomeByLocation = array_map(fn($x) => ['label' => (string) $x['label'], 'value' => (float) $x['value']], $incomeByLocationRaw);

        return $this->json([
            'users' => $users,
            'vehicles' => $vehicles,
            'locations' => $locations,
            'reservationsTotal' => $reservationsTotal,
            'reservationsActiveToday' => $reservationsActiveToday,
            'cancellationsThisMonth' => $cancellationsThisMonth,
            'incomeThisMonth' => $incomeThisMonth,

            'unitsAvailable' => $unitsAvailable,
            'unitsMaintenance' => $unitsMaintenance,
            'unitsInactive' => $unitsInactive,

            // Rankings
            'topVehicles' => $topVehicles,
            'topPickupLocations' => $topPickupLocations,
            'topCategories' => $topCategories,
            'incomeByLocation' => $incomeByLocation,
        ]);
    }
}