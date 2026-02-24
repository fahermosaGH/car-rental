<?php

namespace App\Repository;

use App\Entity\VehicleCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VehicleCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleCategory::class);
    }

    public function adminList(?string $search, bool $showInactive): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.vehicles', 'v')
            ->addSelect('COUNT(v.id) AS vehiclesCount')
            ->groupBy('c.id')
            ->orderBy('c.id', 'DESC');

        if (!$showInactive) {
            $qb->andWhere('c.isActive = 1');
        }

        if ($search !== null && trim($search) !== '') {
            $qb->andWhere('LOWER(c.name) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower(trim($search)) . '%');
        }

        $rows = $qb->getQuery()->getResult();

        return array_map(static function ($row) {
            $c = null;

            if ($row instanceof VehicleCategory) {
                $c = $row;
                $count = 0;
            } else {
                // Soportar diferentes estructuras de hidratación
                $c = $row[0] ?? $row['c'] ?? null;
                $count = (int) ($row['vehiclesCount'] ?? 0);
            }

            if (!$c instanceof VehicleCategory) {
                return null;
            }

            return [
                'id' => $c->getId(),
                'name' => $c->getName(),
                'description' => $c->getDescription(),
                'dailyPrice' => $c->getDailyPrice() !== null ? (float) $c->getDailyPrice() : null,
                'isActive' => (bool) $c->isActive(),
                'vehiclesCount' => $count,
            ];
        }, $rows);
    }

    public function vehiclesCount(int $categoryId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(v.id)')
            ->leftJoin('c.vehicles', 'v')
            ->andWhere('c.id = :id')
            ->setParameter('id', $categoryId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
