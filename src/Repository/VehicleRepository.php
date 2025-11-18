<?php

namespace App\Repository;

use App\Entity\Vehicle;
use App\Entity\Reservation;
use App\Entity\VehicleLocationStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @extends ServiceEntityRepository<Vehicle>
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /**
     * Disponibles con info de stock y reservas tomadas, opcionalmente filtrando por categoría.
     * Devuelve escalares (array) para evitar problemas de hidratación mixta.
     *
     * @return array<int, array{
     *   id:int, brand:string, model:string, year:int, seats:int|null,
     *   transmission:string|null, dailyRate:string|null, isActive:bool,
     *   category:string|null, branchStock:int, taken:int
     * }>
     */
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
                'v.id                   AS id',
                'v.brand                AS brand',
                'v.model                AS model',
                'v.year                 AS year',
                'v.seats                AS seats',
                'v.transmission         AS transmission',
                'v.dailyPriceOverride   AS dailyRate',
                'v.isActive             AS isActive',
                'c.name                 AS category',
                'vls.quantity           AS branchStock',
                // reservas que pisan el rango en esa sucursal
                '(SELECT COUNT(r1.id) FROM ' . \App\Entity\Reservation::class . ' r1
                    WHERE r1.vehicle = v
                      AND r1.pickupLocation = :loc
                      AND r1.status IN (\'pending\', \'confirmed\')
                      AND (:start < r1.endAt) AND (:end > r1.startAt)
                 ) AS taken',
            ])
            // importante para poder usar HAVING con escalares
            ->groupBy('v.id, vls.id, c.id')
            // mantener solo los que tienen cupo: stock - tomadas > 0
            ->having('branchStock > taken');

        // Filtro opcional por nombre exacto de categoría
        if ($categoryName !== null && $categoryName !== '') {
            $qb->andWhere('c.name = :catName')
               ->setParameter('catName', $categoryName);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
