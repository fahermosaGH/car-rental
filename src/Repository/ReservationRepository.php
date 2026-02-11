<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Devuelve promedio y cantidad de ratings para un vehículo.
     * Usa Reservation.rating + Reservation.vehicle + status 'finished'
     */
    public function getVehicleRatingSummary(int $vehicleId): array
    {
        $row = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS avgRating, COUNT(r.id) AS cnt')
            ->andWhere('r.vehicle = :vid')
            ->setParameter('vid', $vehicleId)
            ->andWhere('r.rating IS NOT NULL')
            ->andWhere('r.status = :st')
            ->setParameter('st', 'finished')
            ->getQuery()
            ->getOneOrNullResult();

        $avg = ($row && $row['avgRating'] !== null) ? (float) $row['avgRating'] : null;
        $cnt = ($row && $row['cnt'] !== null) ? (int) $row['cnt'] : 0;

        return ['avg' => $avg, 'count' => $cnt];
    }

    /**
     * Últimas opiniones (rating + comment) para un vehículo
     */
    public function getVehicleRatings(int $vehicleId, int $limit = 6): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.rating, r.ratingComment, r.endAt')
            ->andWhere('r.vehicle = :vid')
            ->setParameter('vid', $vehicleId)
            ->andWhere('r.rating IS NOT NULL')
            ->andWhere('r.status = :st')
            ->setParameter('st', 'finished')
            ->orderBy('r.endAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Para listado: summary por muchos vehicles en un query (performance)
     */
    public function getRatingsSummaryForVehicleIds(array $vehicleIds): array
    {
        if (!$vehicleIds) return [];

        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.vehicle) AS vehicleId, AVG(r.rating) AS avgRating, COUNT(r.id) AS cnt')
            ->andWhere('r.vehicle IN (:ids)')
            ->setParameter('ids', $vehicleIds)
            ->andWhere('r.rating IS NOT NULL')
            ->andWhere('r.status = :st')
            ->setParameter('st', 'finished')
            ->groupBy('vehicleId')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['vehicleId']] = [
                'avg' => $row['avgRating'] !== null ? (float)$row['avgRating'] : null,
                'count' => (int)$row['cnt'],
            ];
        }

        return $map;
    }
}