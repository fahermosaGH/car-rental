<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customers')]
#[ORM\UniqueConstraint(name: 'UNIQ_CUSTOMERS_EMAIL', columns: ['email'])]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(type:'string', length:100)]
    private string $first_name = '';

    #[ORM\Column(type:'string', length:100)]
    private string $last_name = '';

    #[ORM\Column(type:'string', length:180)]
    private string $email = '';

    #[ORM\Column(type:'string', length:30, nullable:true)]
    private ?string $phone = null;

    #[ORM\Column(type:'string', length:30, nullable:true)]
    private ?string $document_number = null;

    #[ORM\Column(type:'datetime_immutable')]
    private \DateTimeImmutable $created_at;

    public function __construct()
    {
        $tz = new \DateTimeZone($_ENV['APP_TIMEZONE'] ?? 'UTC');
        $this->created_at = new \DateTimeImmutable('now', $tz);
    }

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): string { return $this->first_name; }
    public function setFirstName(string $firstName): self { $this->first_name = $firstName; return $this; }

    public function getLastName(): string { return $this->last_name; }
    public function setLastName(string $lastName): self { $this->last_name = $lastName; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }

    public function getDocumentNumber(): ?string { return $this->document_number; }
    public function setDocumentNumber(?string $doc): self { $this->document_number = $doc; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->created_at; }

    public function getFullName(): string
    {
        $first = trim($this->first_name ?? '');
        $last  = trim($this->last_name ?? '');
        $name  = trim($first . ' ' . $last);

        if ($name !== '') {
            return $name;
        }

        return $this->email !== '' ? $this->email : 'Cliente #'.($this->id ?? '');
    }

    public function __toString(): string
    {
        $name = $this->getFullName();
        return $this->email !== '' ? "{$name} ({$this->email})" : $name;
    }
}
