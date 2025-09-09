<?php

namespace App\Entity;

use App\Repository\VehicleLocationStockRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VehicleLocationStockRepository::class)]
#[ORM\Table(name: 'vehicle_location_stock')]
#[ORM\UniqueConstraint(name: 'uniq_vehicle_location', columns: ['vehicle_id', 'location_id'])]
#[UniqueEntity(fields: ['vehicle', 'location'], message: 'Ya existe stock cargado para este vehículo en esa ubicación.')]
class VehicleLocationStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Location $location = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero(message: 'La cantidad no puede ser negativa.')]
    private int $quantity = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }
}
