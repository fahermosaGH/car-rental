<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    #[Route('/me', name: 'api_profile_me', methods: ['GET'])]
    public function me(
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return $this->json($this->serializeUserProfile($user));
    }

    #[Route('', name: 'api_profile_update', methods: ['PUT'])]
    public function update(
        Request $request,
        EntityManagerInterface $em,
        #[CurrentUser] ?User $user,
    ): JsonResponse {
        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        $firstName = trim($data['firstName'] ?? '');
        $lastName  = trim($data['lastName'] ?? '');
        $phone     = trim($data['phone'] ?? '');

        if ($firstName === '' || $lastName === '' || $phone === '') {
            return $this->json([
                'message' => 'Nombre, apellido y teléfono son obligatorios.',
            ], 422);
        }

        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPhone($phone !== '' ? $phone : null);
        $user->setDocumentNumber(trim($data['documentNumber'] ?? '') ?: null);
        $user->setAddress(trim($data['address'] ?? '') ?: null);
        $user->setLicenseNumber(trim($data['licenseNumber'] ?? '') ?: null);
        $user->setLicenseCountry(trim($data['licenseCountry'] ?? '') ?: null);

        // Fechas
        $birthDateStr   = $data['birthDate'] ?? null;
        $licenseExpStr  = $data['licenseExpiry'] ?? null;

        $birthDate = null;
        if ($birthDateStr) {
            try {
                $birthDate = new \DateTimeImmutable($birthDateStr);
            } catch (\Throwable $e) {
                return $this->json(['message' => 'Fecha de nacimiento inválida.'], 422);
            }
        }

        $licenseExpiry = null;
        if ($licenseExpStr) {
            try {
                $licenseExpiry = new \DateTimeImmutable($licenseExpStr);
            } catch (\Throwable $e) {
                return $this->json(['message' => 'Fecha de vencimiento de licencia inválida.'], 422);
            }
        }

        $user->setBirthDate($birthDate);
        $user->setLicenseExpiry($licenseExpiry);

        // Perfil completo (regla simple)
        $complete =
            $user->getPhone() &&
            $user->getDocumentNumber() &&
            $user->getBirthDate() &&
            $user->getLicenseNumber() &&
            $user->getLicenseCountry() &&
            $user->getLicenseExpiry();

        $user->setProfileComplete($complete ? true : false);

        $em->flush();

        return $this->json($this->serializeUserProfile($user));
    }

    private function serializeUserProfile(User $user): array
    {
        return [
            'email'          => $user->getEmail(),
            'firstName'      => $user->getFirstName(),
            'lastName'       => $user->getLastName(),
            'createdAt'      => $user->getCreatedAt()->format('Y-m-d H:i:s'),

            'phone'          => $user->getPhone(),
            'documentNumber' => $user->getDocumentNumber(),
            'birthDate'      => $user->getBirthDate()?->format('Y-m-d'),
            'address'        => $user->getAddress(),
            'licenseNumber'  => $user->getLicenseNumber(),
            'licenseCountry' => $user->getLicenseCountry(),
            'licenseExpiry'  => $user->getLicenseExpiry()?->format('Y-m-d'),

            'profileComplete'=> $user->isProfileComplete(),
        ];
    }
}

