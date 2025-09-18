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
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'string', length:180)]
    private string $email = '';

    #[ORM\Column(type:'json')]
    private array $roles = [];

    #[ORM\Column(type:'string')]
    private string $password = '';

    #[ORM\Column(type:'string', length:100)]
    private string $first_name = '';

    #[ORM\Column(type:'string', length:100)]
    private string $last_name = '';

    #[ORM\Column(type:'datetime_immutable')]
    private \DateTimeImmutable $created_at;

    public function __construct()
    {
        $tz = new \DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'UTC');
        $this->created_at = new \DateTimeImmutable('now', $tz);
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return $this->email; }
    /** @deprecated use getUserIdentifier() */
    public function getUsername(): string { return $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) { $roles[] = 'ROLE_USER'; }
        return array_values(array_unique($roles));
    }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getFirstName(): string { return $this->first_name; }
    public function setFirstName(string $firstName): self { $this->first_name = $firstName; return $this; }

    public function getLastName(): string { return $this->last_name; }
    public function setLastName(string $lastName): self { $this->last_name = $lastName; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->created_at; }
}