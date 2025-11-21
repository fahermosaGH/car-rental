<?php

namespace App\Repository;

use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     *
     * Devuelve filas escalares (array) con:
     *  - stock en la sucursal (branchStock)
     *  - reservas que pisan el rango (taken)
     *
     * El cálculo de unitsAvailable lo hace el controlador:
     *    unitsAvailable = max(branchStock - taken, 0)
     *
     * @return array<int, array{
     *   id:int, brand:string, model:string, year:int|null, seats:int|null,
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
                // reservas que pisán el rango en esa sucursal
                '(SELECT COUNT(r1.id) FROM ' . \App\Entity\Reservation::class . ' r1
                    WHERE r1.vehicle = v
                      AND r1.pickupLocation = :loc
                      AND r1.status IN (\'pending\', \'confirmed\')
                      AND (:start < r1.endAt) AND (:end > r1.startAt)
                 ) AS taken',
            ])
            ->groupBy('v.id, vls.id, c.id');

        // Filtro opcional por nombre exacto de categoría
        if ($categoryName !== null && $categoryName !== '') {
            $qb->andWhere('c.name = :catName')
               ->setParameter('catName', $categoryName);
        }

        // 👇 IMPORTANTE:
        // NO usamos HAVING branchStock > taken,
        // así también devolvemos autos con unitsAvailable = 0
        // y el front puede mostrar "Sin stock".

        return $qb->getQuery()->getArrayResult();
    }
}
