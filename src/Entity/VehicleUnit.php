<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'vehicle_unit')]
#[ORM\UniqueConstraint(name: 'uniq_vehicle_unit_plate', columns: ['plate'])]
#[UniqueEntity(fields: ['plate'], message: 'Ya existe una unidad con esa patente.')]
class VehicleUnit
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_INACTIVE = 'inactive';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Location $location = null;

    #[ORM\Column(length: 16, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    private ?string $plate = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [
        self::STATUS_AVAILABLE,
        self::STATUS_MAINTENANCE,
        self::STATUS_INACTIVE
    ])]
    private string $status = self::STATUS_AVAILABLE;

    public function getId(): ?int { return $this->id; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $vehicle): static { $this->vehicle = $vehicle; return $this; }

    public function getLocation(): ?Location { return $this->location; }
    public function setLocation(?Location $location): static { $this->location = $location; return $this; }

    public function getPlate(): ?string { return $this->plate; }
    public function setPlate(string $plate): static {
        $this->plate = strtoupper(trim($plate));
        return $this;
    }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function __toString(): string
    {
        return ($this->plate ?? 'SIN-PATENTE') . ' - ' . (string) $this->vehicle;
    }
}
