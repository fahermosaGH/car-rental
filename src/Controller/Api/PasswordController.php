<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class PasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $email       = $data['email']       ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$email || !$newPassword) {
            return $this->json(
                ['message' => 'Email y nueva contraseña son obligatorios.'],
                400
            );
        }

        // ⚠️ Usamos el EntityManager para conseguir el repo genérico
        $userRepo = $em->getRepository(User::class);
        $user = $userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            // Para no revelar si el email existe o no
            return $this->json([
                'message' => 'Si el email existe, se actualizó la contraseña.'
            ]);
        }

        $hashed = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashed);

        $em->flush();

        return $this->json([
            'message' => 'Contraseña actualizada correctamente.'
        ]);
    }
}

