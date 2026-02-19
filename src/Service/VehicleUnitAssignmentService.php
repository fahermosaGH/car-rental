<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use App\Entity\Location;
use App\Entity\VehicleUnit;
use Doctrine\ORM\EntityManagerInterface;

final class VehicleUnitAssignmentService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Devuelve una unidad disponible (available) para ese vehículo y sucursal,
     * excluyendo unidades ya asignadas a reservas solapadas (pending/confirmed).
     */
    public function pickUnitOrNull(
        Vehicle $vehicle,
        Location $pickupLocation,
        \DateTimeInterface $startAt,
        \DateTimeInterface $endAt
    ): ?VehicleUnit {
        $qb = $this->em->createQueryBuilder();

        // Subquery: unidades que ya están en reservas solapadas
        $sub = $this->em->createQueryBuilder()
            ->select('IDENTITY(r2.vehicleUnit)')
            ->from(Reservation::class, 'r2')
            ->where('r2.vehicleUnit IS NOT NULL')
            ->andWhere('r2.status IN (:st)')
            ->andWhere('(:start < r2.endAt) AND (:end > r2.startAt)');

        $qb->select('u')
            ->from(VehicleUnit::class, 'u')
            ->where('u.vehicle = :v')
            ->andWhere('u.location = :l')
            ->andWhere('u.status = :available')
            ->andWhere($qb->expr()->notIn('u.id', $sub->getDQL()))
            ->setMaxResults(1);

        $qb->setParameter('v', $vehicle);
        $qb->setParameter('l', $pickupLocation);
        $qb->setParameter('available', VehicleUnit::STATUS_AVAILABLE);
        $qb->setParameter('st', ['pending', 'confirmed']);
        $qb->setParameter('start', $startAt);
        $qb->setParameter('end', $endAt);

        // Orden estable (primero el más viejo)
        $qb->orderBy('u.id', 'ASC');

        return $qb->getQuery()->getOneOrNullResult();
    }
}