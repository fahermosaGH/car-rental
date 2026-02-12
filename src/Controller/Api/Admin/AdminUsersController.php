<?php

namespace App\Controller\Api\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/api/admin/users')]
class AdminUsersController extends AbstractController
{
    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $showInactive = $request->query->get('showInactive') === '1';

        $qb = $em->getRepository(User::class)->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if (!$showInactive) {
            $qb->andWhere('u.isActive = true');
        }

        /** @var User[] $users */
        $users = $qb->getQuery()->getResult();

        $data = array_map(static function (User $u) {
            return [
                'id'              => $u->getId(),
                'email'           => $u->getEmail(),
                'roles'           => $u->getRoles(),
                'firstName'       => $u->getFirstName(),
                'lastName'        => $u->getLastName(),
                'createdAt'       => $u->getCreatedAt()->format('Y-m-d H:i'),
                'isActive'        => $u->isActive(),
                'profileComplete' => $u->isProfileComplete(),
            ];
        }, $users);

        return new JsonResponse($data);
    }

    #[Route('/{id}/roles', name: 'api_admin_users_roles', methods: ['PUT'])]
    public function updateRoles(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        $roles = $payload['roles'] ?? null;
        if (!is_array($roles)) {
            return $this->json(['error' => 'roles debe ser array'], 422);
        }

        $allowed = ['ROLE_USER', 'ROLE_ADMIN'];
        foreach ($roles as $r) {
            if (!in_array($r, $allowed, true)) {
                return $this->json(['error' => 'Rol inválido', 'allowed' => $allowed], 422);
            }
        }

        /** @var User|null $u */
        $u = $em->getRepository(User::class)->find($id);
        if (!$u) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Siempre mantener ROLE_USER
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }
        $roles = array_values(array_unique($roles));

        /** @var User|null $current */
        $current = $this->getUser();
        $currentId = ($current instanceof User) ? $current->getId() : null;

        // 🔥 Protección 1: no permitir auto-quitarse ROLE_ADMIN
        if ($currentId && $u->getId() === $currentId) {
            $hadAdmin = in_array('ROLE_ADMIN', $u->getRoles(), true);
            $willHaveAdmin = in_array('ROLE_ADMIN', $roles, true);

            if ($hadAdmin && !$willHaveAdmin) {
                return $this->json([
                    'error' => 'No podés quitarte ROLE_ADMIN a vos mismo desde el panel.',
                ], 409);
            }
        }

        // 🔥 Protección 2 (recomendado): no dejar el sistema sin admins activos
        $hadAdmin = in_array('ROLE_ADMIN', $u->getRoles(), true);
        $willHaveAdmin = in_array('ROLE_ADMIN', $roles, true);

        if ($hadAdmin && !$willHaveAdmin) {
            $activeAdminCount = (int) $em->createQueryBuilder()
                ->select('COUNT(u2.id)')
                ->from(User::class, 'u2')
                ->where('u2.isActive = true')
                ->andWhere('JSON_CONTAINS(u2.roles, :role) = 1')
                ->setParameter('role', '"ROLE_ADMIN"')
                ->getQuery()
                ->getSingleScalarResult();

            // Si el usuario al que le sacás admin está activo y es el único admin activo -> bloquear
            if ($u->isActive() && $activeAdminCount <= 1) {
                return $this->json([
                    'error' => 'No se puede quitar ROLE_ADMIN: es el último administrador activo.',
                ], 409);
            }
        }

        $u->setRoles($roles);
        $em->flush();

        return $this->json([
            'message' => 'Roles actualizados',
            'roles' => $u->getRoles(),
        ]);
    }

    #[Route('/{id}/active', name: 'api_admin_users_active', methods: ['PUT'])]
    public function setActive(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'JSON inválido'], 400);
        }

        $active = $payload['isActive'] ?? null;
        if (!is_bool($active)) {
            return $this->json(['error' => 'isActive debe ser boolean'], 422);
        }

        /** @var User|null $u */
        $u = $em->getRepository(User::class)->find($id);
        if (!$u) {
            return $this->json(['error' => 'Usuario no encontrado'], 404);
        }

        /** @var User|null $current */
        $current = $this->getUser();
        $currentId = ($current instanceof User) ? $current->getId() : null;

        // 🔥 Protección 1: no permitir auto-desactivarse (UserChecker te deja afuera)
        if ($currentId && $u->getId() === $currentId && $active === false) {
            return $this->json([
                'error' => 'No podés desactivarte a vos mismo.',
            ], 409);
        }

        // 🔥 Protección 2 (recomendado): no desactivar el último admin activo
        if ($active === false && in_array('ROLE_ADMIN', $u->getRoles(), true)) {
            $activeAdminCount = (int) $em->createQueryBuilder()
                ->select('COUNT(u2.id)')
                ->from(User::class, 'u2')
                ->where('u2.isActive = true')
                ->andWhere('JSON_CONTAINS(u2.roles, :role) = 1')
                ->setParameter('role', '"ROLE_ADMIN"')
                ->getQuery()
                ->getSingleScalarResult();

            if ($u->isActive() && $activeAdminCount <= 1) {
                return $this->json([
                    'error' => 'No se puede desactivar: es el último administrador activo.',
                ], 409);
            }
        }

        $u->setIsActive($active);
        $em->flush();

        return $this->json([
            'message' => 'Estado actualizado',
            'isActive' => $u->isActive(),
        ]);
    }
}