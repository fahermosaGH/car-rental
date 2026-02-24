<?php

namespace App\Controller\Api\Admin;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/audit')]
#[IsGranted('ROLE_ADMIN')]
class AdminAuditController extends AbstractController
{
    private const LABELS = [
        \App\Entity\Vehicle::class => 'Vehículo',
        \App\Entity\VehicleUnit::class => 'Unidad',
        \App\Entity\Reservation::class => 'Reserva',
        \App\Entity\ReservationIncident::class => 'Incidente',
        \App\Entity\VehicleCategory::class => 'Categoría',
    ];

    #[Route('', name: 'api_admin_audit_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $entity = $request->query->get('entity');
        $entityId = $request->query->get('entityId');
        $action = $request->query->get('action');
        $actor = $request->query->get('actor');

        $qb = $em->getRepository(AuditLog::class)->createQueryBuilder('a')
            ->orderBy('a.occurredAt', 'DESC')
            ->setMaxResults(200);

        if ($entity)  $qb->andWhere('a.entityClass = :e')->setParameter('e', $entity);
        if ($entityId) $qb->andWhere('a.entityId = :eid')->setParameter('eid', $entityId);
        if ($action)  $qb->andWhere('a.action = :ac')->setParameter('ac', $action);
        if ($actor)   $qb->andWhere('a.actorEmail LIKE :actor')->setParameter('actor', '%' . $actor . '%');

        /** @var AuditLog[] $items */
        $items = $qb->getQuery()->getResult();

        $data = array_map(function (AuditLog $a) {
            $class = $a->getEntityClass();
            $label = self::LABELS[$class] ?? $this->shortClass($class);

            return [
                'id' => $a->getId(),
                'occurredAt' => $a->getOccurredAt()->format('Y-m-d H:i:s'),
                'actorEmail' => $a->getActorEmail(),
                'action' => $a->getAction(),
                'entityClass' => $class,
                'entityLabel' => $label,
                'entityId' => $a->getEntityId(),
                'changes' => $a->getChanges(),
                'meta' => $a->getMeta(),
            ];
        }, $items);

        return $this->json($data);
    }

    private function shortClass(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');
        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }
}