<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(): Response
    {
        /** @var UserInterface $user */
        $user = $this->getUser();

        return $this->json([
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $first = trim((string)($data['firstName'] ?? ''));
        $last  = trim((string)($data['lastName'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Email inválido'], 422);
        }
        if (strlen($password) < 6) {
            return $this->json(['error' => 'La contraseña debe tener al menos 6 caracteres'], 422);
        }

        // ¿email existente?
        $exists = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($exists) {
            return $this->json(['error' => 'El email ya está registrado'], 409);
        }

        $u = new User();
        $u->setEmail($email)
          ->setFirstName($first !== '' ? $first : 'Nombre')
          ->setLastName($last !== '' ? $last : 'Apellido')
          ->setRoles(['ROLE_USER']);

        $u->setPassword($hasher->hashPassword($u, $password));

        $em->persist($u);
        $em->flush();

        return $this->json([
            'message' => 'Usuario registrado',
            'id'      => $u->getId(),
            'email'   => $u->getEmail(),
        ], 201);
    }
}
