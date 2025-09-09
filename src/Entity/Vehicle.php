<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\VehicleLocationStock; // agregado para tipar correctamente la relación

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?VehicleCategory $category = null;

    #[ORM\Column(length: 80)]
    private ?string $brand = null;

    #[ORM\Column(length: 80)]
    private ?string $model = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column]
    private ?int $seats = null;

    #[ORM\Column(length: 20)]
    private ?string $transmission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $dailyPriceOverride = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, VehicleLocationStock>
     */
    #[ORM\OneToMany(
        mappedBy: 'vehicle',
        targetEntity: VehicleLocationStock::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $stocks;

    public function __construct()
    {
        $this->stocks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?VehicleCategory
    {
        return $this->category;
    }

    public function setCategory(?VehicleCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getSeats(): ?int
    {
        return $this->seats;
    }

    public function setSeats(int $seats): static
    {
        $this->seats = $seats;

        return $this;
    }

    public function getTransmission(): ?string
    {
        return $this->transmission;
    }

    public function setTransmission(string $transmission): static
    {
        $this->transmission = $transmission;

        return $this;
    }

    public function getDailyPriceOverride(): ?string
    {
        return $this->dailyPriceOverride;
    }

    public function setDailyPriceOverride(?string $dailyPriceOverride): static
    {
        $this->dailyPriceOverride = $dailyPriceOverride;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, VehicleLocationStock>
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(VehicleLocationStock $stock): static
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks->add($stock);
            $stock->setVehicle($this);
        }

        return $this;
    }

    public function removeStock(VehicleLocationStock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            // set the owning side to null (unless already changed)
            if ($stock->getVehicle() === $this) {
                $stock->setVehicle(null);
            }
        }

        return $this;
    }

    // Opcional: mejora la visualización en selects/tablas del admin
    public function __toString(): string
    {
        $label = trim(($this->brand ?? '') . ' ' . ($this->model ?? ''));
        return $label !== '' ? $label : 'Vehículo #' . ($this->id ?? 0);
    }
}
