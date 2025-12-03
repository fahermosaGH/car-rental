<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'UNIQ_USERS_EMAIL', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email = '';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password = '';

    #[ORM\Column(type: 'string', length: 100)]
    private string $first_name = '';

    #[ORM\Column(type: 'string', length: 100)]
    private string $last_name = '';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $created_at;

    // 👇 Nuevos campos de perfil

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $document_number = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $birth_date = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $license_number = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $license_country = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $license_expiry = null;

    #[ORM\Column(type: 'boolean')]
    private bool $profile_complete = false;

    public function __construct()
    {
        $tz = new \DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'UTC');
        $this->created_at = new \DateTimeImmutable('now', $tz);
    }

    // ===== Identidad / login =====

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @deprecated usar getUserIdentifier() */
    public function getUsername(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // nada por ahora
    }

    // ===== Datos básicos =====

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function setFirstName(string $firstName): self
    {
        $this->first_name = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function setLastName(string $lastName): self
    {
        $this->last_name = $lastName;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    // ===== Perfil extendido =====

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getDocumentNumber(): ?string
    {
        return $this->document_number;
    }

    public function setDocumentNumber(?string $documentNumber): self
    {
        $this->document_number = $documentNumber;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birth_date;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): self
    {
        $this->birth_date = $birthDate;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->license_number;
    }

    public function setLicenseNumber(?string $licenseNumber): self
    {
        $this->license_number = $licenseNumber;
        return $this;
    }

    public function getLicenseCountry(): ?string
    {
        return $this->license_country;
    }

    public function setLicenseCountry(?string $licenseCountry): self
    {
        $this->license_country = $licenseCountry;
        return $this;
    }

    public function getLicenseExpiry(): ?\DateTimeInterface
    {
        return $this->license_expiry;
    }

    public function setLicenseExpiry(?\DateTimeInterface $licenseExpiry): self
    {
        $this->license_expiry = $licenseExpiry;
        return $this;
    }

    public function isProfileComplete(): bool
    {
        return $this->profile_complete;
    }

    public function setProfileComplete(bool $complete): self
    {
        $this->profile_complete = $complete;
        return $this;
    }
}
