<?php

namespace App\Controller\Api\Admin;

use App\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/audit')]
#[IsGranted('ROLE_ADMIN')]
class AdminAuditPingController extends AbstractController
{
    #[Route('/ping', name: 'api_admin_audit_ping', methods: ['POST'])]
    public function ping(EntityManagerInterface $em, Security $security): JsonResponse
    {
        $user = $security->getUser();

        $email = null;
        if ($user && method_exists($user, 'getUserIdentifier')) {
            $email = $user->getUserIdentifier();
        } elseif ($user && method_exists($user, 'getEmail')) {
            $email = $user->getEmail();
        }

        $log = new AuditLog(
            'custom',
            'AuditPing',
            null,
            $email,
            ['ping' => ['old' => null, 'new' => 'ok']],
            ['path' => '/api/admin/audit/ping']
        );

        $em->persist($log);
        $em->flush();

        return new JsonResponse(['ok' => true]);
    }
}