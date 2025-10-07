<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Devuelve los rangos de fechas ocupadas para un vehículo en una sucursal
     */
    public function findBookedRanges($vehicleId, $locationId): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.startAt', 'r.endAt')
            ->where('r.vehicle = :vehicle')
            ->andWhere('r.pickupLocation = :location')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('vehicle', $vehicleId)
            ->setParameter('location', $locationId)
            ->setParameter('statuses', ['pending', 'confirmed'])
            ->getQuery()
            ->getArrayResult();
    }
}
