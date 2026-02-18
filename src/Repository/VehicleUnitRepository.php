<?php

namespace App\Repository;

use App\Entity\VehicleUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleUnit>
 */
class VehicleUnitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleUnit::class);
    }

    /**
     * Conteo de unidades por (vehículo, ubicación, status).
     * Útil si después querés evitar QueryBuilder repetido en services.
     */
    public function countByVehicleLocationAndStatus(int $vehicleId, int $locationId, string $status): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->leftJoin('u.vehicle', 'v')
            ->leftJoin('u.location', 'l')
            ->andWhere('v.id = :vid')
            ->andWhere('l.id = :lid')
            ->andWhere('u.status = :st')
            ->setParameter('vid', $vehicleId)
            ->setParameter('lid', $locationId)
            ->setParameter('st', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
