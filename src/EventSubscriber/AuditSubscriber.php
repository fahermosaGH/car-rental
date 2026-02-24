<?php

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditSubscriber implements EventSubscriber
{
    /** Solo auditamos estas entidades (rápido y seguro). */
    private const AUDITED = [
        \App\Entity\Vehicle::class,
        \App\Entity\VehicleUnit::class,
        \App\Entity\Reservation::class,
        \App\Entity\ReservationIncident::class,
        \App\Entity\VehicleCategory::class,
    ];

    /** Campos que JAMÁS deben auditarse. */
    private const BLACKLIST_FIELDS = [
        'password',
    ];

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // Auditoría SOLO si el actor es admin (compatible con JWT)
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            return;
        }

        $actorEmail = null;
        if (method_exists($user, 'getEmail')) {
            $actorEmail = $user->getEmail();
        } elseif (method_exists($user, 'getUserIdentifier')) {
            $actorEmail = $user->getUserIdentifier();
        }

        $meta = $this->buildMeta();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$this->isAudited($entity)) continue;

            $log = new AuditLog(
                'create',
                $entity::class,
                null, // id todavía puede no estar asignado en insert
                $actorEmail,
                $this->extractAllFields($uow, $entity),
                $meta
            );

            $em->persist($log);
            $uow->computeChangeSet($em->getClassMetadata(AuditLog::class), $log);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$this->isAudited($entity)) continue;

            $changes = $this->extractDiff($uow, $entity);
            if ($changes === []) continue;

            $log = new AuditLog(
                'update',
                $entity::class,
                $this->getEntityId($entity),
                $actorEmail,
                $changes,
                $meta
            );

            $em->persist($log);
            $uow->computeChangeSet($em->getClassMetadata(AuditLog::class), $log);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$this->isAudited($entity)) continue;

            $log = new AuditLog(
                'delete',
                $entity::class,
                $this->getEntityId($entity),
                $actorEmail,
                null,
                $meta
            );

            $em->persist($log);
            $uow->computeChangeSet($em->getClassMetadata(AuditLog::class), $log);
        }
    }

    private function isAudited(object $entity): bool
    {
        foreach (self::AUDITED as $class) {
            if ($entity instanceof $class) return true;
        }
        return false;
    }

    private function extractDiff(UnitOfWork $uow, object $entity): array
    {
        $set = $uow->getEntityChangeSet($entity);
        $out = [];

        foreach ($set as $field => [$old, $new]) {
            if (in_array($field, self::BLACKLIST_FIELDS, true)) continue;

            $out[$field] = [
                'old' => $this->normalizeValue($old),
                'new' => $this->normalizeValue($new),
            ];
        }

        return $out;
    }

    private function extractAllFields(UnitOfWork $uow, object $entity): array
    {
        $set = $uow->getEntityChangeSet($entity);
        $out = [];

        foreach ($set as $field => [$old, $new]) {
            if (in_array($field, self::BLACKLIST_FIELDS, true)) continue;

            $out[$field] = [
                'old' => $this->normalizeValue($old),
                'new' => $this->normalizeValue($new),
            ];
        }

        return $out;
    }

    private function getEntityId(object $entity): ?string
    {
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
            return $id !== null ? (string) $id : null;
        }
        return null;
    }

    private function buildMeta(): array
    {
        $req = $this->requestStack->getCurrentRequest();
        if (!$req) return [];

        return [
            'path' => $req->getPathInfo(),
            'method' => $req->getMethod(),
            'ip' => $req->getClientIp(),
            'ua' => $req->headers->get('User-Agent'),
        ];
    }

    private function normalizeValue(mixed $v): mixed
    {
        if (is_object($v)) {
            if (method_exists($v, 'getId')) return (string) $v->getId();
            if (method_exists($v, '__toString')) return (string) $v;
            return get_class($v);
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format(\DateTimeInterface::ATOM);
        }
        return $v;
    }
}