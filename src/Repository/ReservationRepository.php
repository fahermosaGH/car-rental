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
     * Devuelve stats (avg, count) para muchos vehículos en una sola query.
     * Solo considera reservas: status=completed y rating no null.
     *
     * Retorna un mapa:
     * [
     *   vehicleId => ['avg' => float|null, 'count' => int],
     *   ...
     * ]
     */
    public function getRatingStatsByVehicleIds(array $vehicleIds): array
    {
        $vehicleIds = array_values(array_filter(array_map('intval', $vehicleIds)));
        if (count($vehicleIds) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.vehicle) AS vehicleId')
            ->addSelect('AVG(r.rating) AS ratingAvg')
            ->addSelect('COUNT(r.rating) AS ratingCount')
            ->andWhere('r.status = :status')
            ->andWhere('r.rating IS NOT NULL')
            ->andWhere('IDENTITY(r.vehicle) IN (:ids)')
            ->setParameter('status', 'completed')
            ->setParameter('ids', $vehicleIds)
            ->groupBy('vehicleId');

        $rows = $qb->getQuery()->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $vid = (int)($row['vehicleId'] ?? 0);
            if ($vid <= 0) continue;

            $avg = $row['ratingAvg'];
            $out[$vid] = [
                'avg' => $avg !== null ? (float)$avg : null,
                'count' => (int)($row['ratingCount'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Stats de un vehículo.
     */
    public function getRatingStatsForVehicle(int $vehicleId): array
    {
        $vehicleId = (int)$vehicleId;

        $qb = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) AS ratingAvg')
            ->addSelect('COUNT(r.rating) AS ratingCount')
            ->andWhere('r.status = :status')
            ->andWhere('r.rating IS NOT NULL')
            ->andWhere('IDENTITY(r.vehicle) = :vid')
            ->setParameter('status', 'completed')
            ->setParameter('vid', $vehicleId);

        $row = $qb->getQuery()->getOneOrNullResult();

        return [
            'avg' => ($row && $row['ratingAvg'] !== null) ? (float)$row['ratingAvg'] : null,
            'count' => ($row && $row['ratingCount'] !== null) ? (int)$row['ratingCount'] : 0,
        ];
    }

    /**
     * Lista de reseñas (items) para un vehículo.
     * Solo reservas completed con rating no null.
     */
    public function getRatingsForVehicle(int $vehicleId, int $limit = 12): array
    {
        $vehicleId = (int)$vehicleId;
        $limit = max(1, min(100, (int)$limit));

        // join user para mostrar email (opcional, pero suma)
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->andWhere('r.status = :status')
            ->andWhere('r.rating IS NOT NULL')
            ->andWhere('IDENTITY(r.vehicle) = :vid')
            ->setParameter('status', 'completed')
            ->setParameter('vid', $vehicleId)
            ->orderBy('r.id', 'DESC')
            ->setMaxResults($limit);

        /** @var Reservation[] $rows */
        $rows = $qb->getQuery()->getResult();

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'reservationId' => $r->getId(),
                'rating' => $r->getRating(),
                'comment' => $r->getRatingComment(),
                'userEmail' => $r->getUser() ? $r->getUser()->getEmail() : null,
                'endAt' => $r->getEndAt()?->format('Y-m-d'),
            ];
        }

        return $items;
    }
}