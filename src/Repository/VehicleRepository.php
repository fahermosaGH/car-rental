<?php

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    public function findAvailableWithStockInfo(
    int $pickupLocationId,
    \DateTimeInterface $start,
    \DateTimeInterface $end,
    ?string $categoryName = null
): array {
    $qb = $this->createQueryBuilder('v')
        ->innerJoin(
            \App\Entity\VehicleLocationStock::class,
            'vls',
            'WITH',
            'vls.vehicle = v AND vls.location = :loc'
        )
        ->leftJoin('v.category', 'c')
        ->andWhere('v.isActive = :active')
        ->setParameter('loc', $pickupLocationId)
        ->setParameter('active', true)
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->select([
            'v.id                 AS id',
            'v.brand              AS brand',
            'v.model              AS model',
            'v.year               AS year',
            'v.seats              AS seats',
            'v.transmission       AS transmission',
            'v.dailyPriceOverride AS dailyRate',
            'v.isActive           AS isActive',
            'v.imageUrl           AS imageUrl',   // 🔥 ESTA ES LA CLAVE
            'c.name               AS category',
            'vls.quantity         AS branchStock',

            '(SELECT COUNT(r1.id)
                FROM ' . \App\Entity\Reservation::class . ' r1
                WHERE r1.vehicle = v
                  AND r1.pickupLocation = :loc
                  AND r1.status IN (\'pending\', \'confirmed\')
                  AND (:start < r1.endAt) AND (:end > r1.startAt)
            ) AS taken',
        ])
        ->groupBy('v.id, vls.id, c.id');

    if ($categoryName !== null && $categoryName !== '') {
        $qb->andWhere('c.name = :catName')
           ->setParameter('catName', $categoryName);
    }

    return $qb->getQuery()->getArrayResult();
}
}