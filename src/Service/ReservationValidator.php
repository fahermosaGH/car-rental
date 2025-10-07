<?php

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;

final class ReservationValidator
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * CONFIG — usando tu entidad real de stock por sucursal.
     * Si tu campo de cantidad no se llama "quantity", cambiá la constante STOCK_QUANTITY_FIELD.
     */
    private const STOCK_ENTITY_FQCN       = \App\Entity\VehicleLocationStock::class; // <-- tu entidad
    private const STOCK_VEHICLE_FIELD     = 'vehicle';   // relación al Vehicle
    private const STOCK_LOCATION_FIELD    = 'location';  // relación a Location
    private const STOCK_QUANTITY_FIELD    = 'quantity';  // entero con unidades disponibles

    /**
     * Devuelve true si hay stock disponible en la sucursal para el rango dado.
     * Regla temporal: solapa si (start < endAt) AND (end > startAt) — fin exclusivo.
     */
    public function isAvailable(
        int $vehicleId,
        ?int $pickupLocationId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $excludeId = null
    ): bool {
        $overlaps = $this->countOverlaps($vehicleId, $pickupLocationId, $start, $end, $excludeId);
        $stock    = $this->getStockFor($vehicleId, $pickupLocationId);
        if ($stock <= 0) { $stock = 1; } // fallback razonable
        return $overlaps < $stock;
    }

    /**
     * Cuenta cuántas reservas solapan para (vehículo, sucursal) y rango.
     * Considera estados 'pending' y 'confirmed' como bloqueantes.
     */
    public function countOverlaps(
        int $vehicleId,
        ?int $pickupLocationId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $excludeId = null
    ): int {
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Reservation::class, 'r')
            ->where('r.vehicle = :vehicleId')
            ->andWhere('r.status IN (:statuses)')
            ->andWhere('(:start < r.endAt) AND (:end > r.startAt)');

        $qb->setParameter('vehicleId', $vehicleId);
        $qb->setParameter('statuses', ['pending', 'confirmed']);
        $qb->setParameter('start', $start);
        $qb->setParameter('end', $end);

        if ($pickupLocationId !== null) {
            $qb->andWhere('r.pickupLocation = :loc')->setParameter('loc', $pickupLocationId);
        }
        if ($excludeId !== null) {
            $qb->andWhere('r.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Stock disponible para (vehículo, sucursal):
     *  - Suma registros en VehicleLocationStock (vehicle, location, quantity).
     *  - Si no existiera esa entidad, intenta Vehicle::getStock().
     */
    public function getStockFor(int $vehicleId, ?int $pickupLocationId): int
    {
        // A) Stock por ubicación (tu caso)
        if (class_exists(self::STOCK_ENTITY_FQCN)) {
            $repo  = $this->em->getRepository(self::STOCK_ENTITY_FQCN);
            $alias = 's';

            $qb = $repo->createQueryBuilder($alias)
                ->select('COALESCE(SUM(' . $alias . '.' . self::STOCK_QUANTITY_FIELD . '), 0)')
                ->where($alias . '.' . self::STOCK_VEHICLE_FIELD . ' = :v')
                ->setParameter('v', $vehicleId);

            if ($pickupLocationId !== null) {
                $qb->andWhere($alias . '.' . self::STOCK_LOCATION_FIELD . ' = :l')
                   ->setParameter('l', $pickupLocationId);
            }

            return (int)$qb->getQuery()->getSingleScalarResult();
        }

        // B) Stock global en Vehicle (fallback)
        $vehicle = $this->em->getRepository(Vehicle::class)->find($vehicleId);
        if ($vehicle && method_exists($vehicle, 'getStock')) {
            return (int)$vehicle->getStock();
        }

        return 1;
    }
}



