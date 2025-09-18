<?php

namespace App\Service;

use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;

class AvailabilityService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Devuelve vehículos disponibles en una ubicación (quantity > 0),
     * con la cantidad total disponible por modelo.
     *
     * @return array<int, array{vehicle: Vehicle, quantity: int}>
     */
    public function availableVehiclesByLocation(int $locationId): array
    {
        // IMPORTANTE: esto asume que en Vehicle tenés la relación inversa
        // OneToMany llamada "stocks" hacia VehicleLocationStock (tu compa la agregó).
        // VehicleLocationStock tiene ManyToOne a location (propiedad "location") y a vehicle ("vehicle").

        $qb = $this->em->createQueryBuilder();

        $qb->select('v AS vehicle', 'SUM(s.quantity) AS quantity')
            ->from(Vehicle::class, 'v')          // <-- Vehicle como entidad raíz
            ->join('v.stocks', 's')              // <-- relación inversa en Vehicle
            ->join('s.location', 'l')
            ->where('l.id = :loc')
            ->andWhere('s.quantity > 0')
            ->groupBy('v.id')
            ->orderBy('v.id', 'ASC')
            ->setParameter('loc', $locationId);

        // getResult() devuelve array con claves 'vehicle' (objeto) y 'quantity' (string/int)
        $rows = $qb->getQuery()->getResult();

        return array_map(static function (array $row): array {
            return [
                'vehicle'  => $row['vehicle'],
                'quantity' => (int) $row['quantity'],
            ];
        }, $rows);
    }
}