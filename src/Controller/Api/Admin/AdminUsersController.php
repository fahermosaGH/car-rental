<?php

namespace App\Controller\Api\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

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
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['error' => 'JSON inválido'], 400);

        $roles = $data['roles'] ?? null;
        if (!is_array($roles)) return $this->json(['error' => 'roles debe ser array'], 422);

        $allowed = ['ROLE_USER', 'ROLE_ADMIN'];
        foreach ($roles as $r) {
            if (!in_array($r, $allowed, true)) {
                return $this->json(['error' => 'Rol inválido', 'allowed' => $allowed], 422);
            }
        }

        /** @var User|null $u */
        $u = $em->getRepository(User::class)->find($id);
        if (!$u) return $this->json(['error' => 'Usuario no encontrado'], 404);

        $u->setRoles(array_values(array_unique($roles)));
        $em->flush();

        return $this->json(['message' => 'Roles actualizados', 'roles' => $u->getRoles()]);
    }

    #[Route('/{id}/active', name: 'api_admin_users_active', methods: ['PUT'])]
    public function setActive(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) return $this->json(['error' => 'JSON inválido'], 400);

        $active = $data['isActive'] ?? null;
        if (!is_bool($active)) return $this->json(['error' => 'isActive debe ser boolean'], 422);

        /** @var User|null $u */
        $u = $em->getRepository(User::class)->find($id);
        if (!$u) return $this->json(['error' => 'Usuario no encontrado'], 404);

        $u->setIsActive($active);
        $em->flush();

        return $this->json(['message' => 'Estado actualizado', 'isActive' => $u->isActive()]);
    }
}
