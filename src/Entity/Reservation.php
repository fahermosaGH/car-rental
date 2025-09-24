<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Cliente que realiza la reserva
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    // Vehículo reservado
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    // Retiro
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $pickupLocation = null;

    // Devolución
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $dropoffLocation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'La fecha/hora de inicio es obligatoria.')]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'La fecha/hora de fin es obligatoria.')]
    #[Assert\GreaterThan(propertyPath: 'startAt', message: 'La fecha/hora de fin debe ser posterior a la de inicio.')]
    private ?\DateTimeImmutable $endAt = null;


    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['pending', 'confirmed', 'cancelled'], message: 'Estado inválido.')]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalPrice = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;
        return $this;
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

    public function getPickupLocation(): ?Location
    {
        return $this->pickupLocation;
    }

    public function setPickupLocation(?Location $pickupLocation): static
    {
        $this->pickupLocation = $pickupLocation;
        return $this;
    }

    public function getDropoffLocation(): ?Location
    {
        return $this->dropoffLocation;
    }

    public function setDropoffLocation(?Location $dropoffLocation): static
    {
        $this->dropoffLocation = $dropoffLocation;
        return $this;
    }

    public function getStartAt(): ?\DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): ?\DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function __toString(): string
    {
        return 'Reserva #' . ($this->id ?? 0);
    }
}
