<?php

namespace App\Service;

use App\Entity\Vehicle;
use App\Entity\Location;
use App\Entity\VehicleLocationStock;
use App\Entity\VehicleUnit;
use Doctrine\ORM\EntityManagerInterface;

final class StockSyncService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function syncFor(Vehicle $vehicle, Location $location): int
    {
        $count = (int) $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(VehicleUnit::class, 'u')
            ->where('u.vehicle = :v')
            ->andWhere('u.location = :l')
            ->andWhere('u.status = :st') // solo disponibles
            ->setParameter('v', $vehicle)
            ->setParameter('l', $location)
            ->setParameter('st', VehicleUnit::STATUS_AVAILABLE)
            ->getQuery()
            ->getSingleScalarResult();

        $repo = $this->em->getRepository(VehicleLocationStock::class);
        $stock = $repo->findOneBy(['vehicle' => $vehicle, 'location' => $location]);

        if (!$stock) {
            $stock = new VehicleLocationStock();
            $stock->setVehicle($vehicle);
            $stock->setLocation($location);
            $this->em->persist($stock);
        }

        $stock->setQuantity($count);
        $this->em->flush();

        return $count;
    }

    public function rebuildAll(): void
    {
        // Recorre combinaciones reales existentes en unidades
        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(u.vehicle) AS vid, IDENTITY(u.location) AS lid')
            ->from(VehicleUnit::class, 'u')
            ->groupBy('vid, lid')
            ->getQuery()
            ->getArrayResult();

        $vRepo = $this->em->getRepository(Vehicle::class);
        $lRepo = $this->em->getRepository(Location::class);

        foreach ($rows as $r) {
            $v = $vRepo->find((int)$r['vid']);
            $l = $lRepo->find((int)$r['lid']);
            if ($v && $l) {
                $this->syncFor($v, $l);
            }
        }
    }
}
