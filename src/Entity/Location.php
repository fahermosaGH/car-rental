<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\VehicleLocationStock;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    // 🔥 NUEVOS CAMPOS PARA EL MAPA
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;

    /**
     * @var Collection<int, VehicleLocationStock>
     */
    #[ORM\OneToMany(
        targetEntity: VehicleLocationStock::class,
        mappedBy: 'location',
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
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

    // 🔥 GETTERS Y SETTERS DE LAT/LON
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;
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
            $stock->setLocation($this);
        }
        return $this;
    }

    public function removeStock(VehicleLocationStock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            if ($stock->getLocation() === $this) {
                $stock->setLocation(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? 'Ubicación #' . ($this->id ?? 0);
    }
}