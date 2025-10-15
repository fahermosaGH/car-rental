<?php

namespace App\Controller\Api;

use App\Repository\ReservationRepository;
use DateInterval;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class OccupancyController extends AbstractController
{
    public function __construct(private ReservationRepository $reservations) {}

    // Devuelve días ocupados (día completo) para un vehículo en un rango visible del calendario.
    #[Route('/vehicle/{id}/occupancy', name: 'api_vehicle_occupancy', methods: ['GET'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $fromStr = $request->query->get('from');
        $toStr   = $request->query->get('to');

        if (!$fromStr || !$toStr) {
            return $this->json(['days' => []]);
        }

        try {
            $from = new DateTimeImmutable($fromStr.' 00:00:00');
            $to   = new DateTimeImmutable($toStr.' 23:59:59');
        } catch (\Throwable) {
            return $this->json(['days' => []]);
        }

        // Traemos reservas que solapen el rango visible del calendario
        $qb = $this->reservations->createQueryBuilder('r')
            ->select('r.startAt AS s, r.endAt AS e')
            ->where('r.vehicle = :vid')
            ->andWhere('(:from < r.endAt) AND (:to > r.startAt)')
            // Si solo "confirmed" bloquea, descomentá:
            //->andWhere('r.status = :confirmed')->setParameter('confirmed', 'confirmed')
            ->setParameter('vid', $id)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $rows = $qb->getQuery()->getArrayResult();

        // Expandimos a días (fin exclusivo): bloquea desde s (incl) hasta (e - 1 día)
        $busyDays = [];
        foreach ($rows as $row) {
            /** @var \DateTimeInterface $s */
            /** @var \DateTimeInterface $e */
            $s = $row['s'];
            $e = $row['e'];

            $cur = new DateTimeImmutable($s->format('Y-m-d'));
            $end = new DateTimeImmutable($e->format('Y-m-d')); // fin-exclusivo
            while ($cur < $end) {
                $busyDays[$cur->format('Y-m-d')] = true;
                $cur = $cur->add(new DateInterval('P1D'));
            }
        }

        return $this->json(['days' => array_keys($busyDays)]);
    }
}
