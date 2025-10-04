<?php

namespace App\Service;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeInterface;

class ReservationValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    public function isVehicleAvailable(int $vehicleId, DateTimeInterface $startAt, DateTimeInterface $endAt): bool
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('COUNT(r.id)')
            ->from(Reservation::class, 'r')
            ->where('r.vehicle = :vehicle')
            ->andWhere('r.startAt <= :endAt')
            ->andWhere('r.endAt >= :startAt')
            ->andWhere('r.status != :cancelled');

        // ✅ Crear parámetros como ArrayCollection en lugar de array
        $params = new ArrayCollection([
            new \Doctrine\ORM\Query\Parameter('vehicle', $vehicleId),
            new \Doctrine\ORM\Query\Parameter('endAt', $endAt),
            new \Doctrine\ORM\Query\Parameter('startAt', $startAt),
            new \Doctrine\ORM\Query\Parameter('cancelled', 'cancelled'),
        ]);

        $qb->setParameters($params);

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $count === 0;
    }
}
