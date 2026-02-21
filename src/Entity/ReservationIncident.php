<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reservation_incident')]
class ReservationIncident
{
    public const STATUS_OPEN = 'open';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Reservation $reservation = null;

    // unidad rota (la que estaba asignada cuando reportaron)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?VehicleUnit $vehicleUnit = null;

    // unidad reemplazo elegida por admin
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'replacement_unit_id', nullable: true, onDelete: 'SET NULL')]
    private ?VehicleUnit $replacementUnit = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $description = '';

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int { return $this->id; }

    public function getReservation(): ?Reservation { return $this->reservation; }
    public function setReservation(?Reservation $reservation): static { $this->reservation = $reservation; return $this; }

    public function getVehicleUnit(): ?VehicleUnit { return $this->vehicleUnit; }
    public function setVehicleUnit(?VehicleUnit $vehicleUnit): static { $this->vehicleUnit = $vehicleUnit; return $this; }

    public function getReplacementUnit(): ?VehicleUnit { return $this->replacementUnit; }
    public function setReplacementUnit(?VehicleUnit $replacementUnit): static { $this->replacementUnit = $replacementUnit; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = trim($description); return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getResolvedAt(): ?\DateTimeImmutable { return $this->resolvedAt; }
    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static { $this->resolvedAt = $resolvedAt; return $this; }
}