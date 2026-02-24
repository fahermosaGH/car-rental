<?php

namespace App\Service;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * action: create|update|delete|custom
     * entityClass: App\Entity\VehicleUnit::class, etc.
     */
    public function log(string $action, string $entityClass, ?string $entityId, ?array $changes = null, array $meta = []): void
    {
        $user = $this->security->getUser();
        $roles = $user && method_exists($user, 'getRoles') ? $user->getRoles() : [];

        // SOLO admin
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            return;
        }

        $email = null;
        if ($user && method_exists($user, 'getUserIdentifier')) $email = $user->getUserIdentifier();
        elseif ($user && method_exists($user, 'getEmail')) $email = $user->getEmail();

        $req = $this->requestStack->getCurrentRequest();

        $finalMeta = array_merge([
            'path' => $req?->getPathInfo(),
            'method' => $req?->getMethod(),
            'ip' => $req?->getClientIp(),
        ], $meta);

        $log = new AuditLog(
            $action,
            $entityClass,
            $entityId,
            $email,
            $changes,
            $finalMeta
        );

        $this->em->persist($log);
        $this->em->flush(); // simple y efectivo
    }

    public function custom(string $entityClass, ?string $entityId, string $event, ?array $changes = null, array $meta = []): void
    {
        $meta['event'] = $event;
        $this->log('custom', $entityClass, $entityId, $changes, $meta);
    }
}