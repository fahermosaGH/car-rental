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
     * Devuelve vehículos activos disponibles en una sucursal para un rango,
     * considerando stock por ubicación y reservas solapadas (fin exclusivo).
     *
     * Regla de solapamiento: (:start < r.endAt) AND (:end > r.startAt)
     */
    public function findAvailableWithStock(
        int $pickupLocationId,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array {
        $qb = $this->createQueryBuilder('v')
            ->innerJoin(
                VehicleLocationStock::class,
                'vls',
                Join::WITH,
                'vls.vehicle = v AND vls.location = :loc'
            )
            ->andWhere('v.isActive = :active')
            ->setParameter('loc', $pickupLocationId)
            ->setParameter('active', true)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        // Cupo: cantidad en sucursal > reservas solapadas en ese rango y sucursal
        $qb->andWhere(
            'vls.quantity > (
                SELECT COUNT(r1.id)
                FROM ' . Reservation::class . ' r1
                WHERE r1.vehicle = v
                  AND r1.pickupLocation = :loc
                  AND r1.status IN (\'pending\', \'confirmed\')
                  AND (:start < r1.endAt) AND (:end > r1.startAt)
            )'
        );

        return $qb->getQuery()->getResult();
    }
}
