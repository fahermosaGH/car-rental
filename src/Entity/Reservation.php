<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    // ✅ unidad física asignada (patente)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?VehicleUnit $vehicleUnit = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $pickupLocation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $dropoffLocation = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $startAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $endAt = null;

    #[ORM\Column(length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalPrice = null;

    #[ORM\OneToMany(mappedBy: 'reservation', targetEntity: ReservationExtra::class, cascade: ['persist', 'remove'])]
    private Collection $extras;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $ratingComment = null;

    // ✅ NUEVO: observación de devolución (admin)
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $returnNote = null;

    // ✅ NUEVO: multa/penalidad (opcional)
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $returnPenalty = null;

    public function __construct()
    {
        $this->extras = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCustomer(): ?Customer { return $this->customer; }
    public function setCustomer(?Customer $customer): static { $this->customer = $customer; return $this; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $vehicle): static { $this->vehicle = $vehicle; return $this; }

    public function getVehicleUnit(): ?VehicleUnit { return $this->vehicleUnit; }
    public function setVehicleUnit(?VehicleUnit $vehicleUnit): static { $this->vehicleUnit = $vehicleUnit; return $this; }

    public function getPickupLocation(): ?Location { return $this->pickupLocation; }
    public function setPickupLocation(?Location $pickupLocation): static { $this->pickupLocation = $pickupLocation; return $this; }

    public function getDropoffLocation(): ?Location { return $this->dropoffLocation; }
    public function setDropoffLocation(?Location $dropoffLocation): static { $this->dropoffLocation = $dropoffLocation; return $this; }

    public function getStartAt(): ?\DateTimeImmutable { return $this->startAt; }
    public function setStartAt(\DateTimeImmutable $startAt): static { $this->startAt = $startAt; return $this; }

    public function getEndAt(): ?\DateTimeImmutable { return $this->endAt; }
    public function setEndAt(\DateTimeImmutable $endAt): static { $this->endAt = $endAt; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getTotalPrice(): ?string { return $this->totalPrice; }
    public function setTotalPrice(?string $totalPrice): static { $this->totalPrice = $totalPrice; return $this; }

    public function getExtras(): Collection { return $this->extras; }

    public function addExtra(ReservationExtra $extra): static {
        if (!$this->extras->contains($extra)) {
            $this->extras->add($extra);
            $extra->setReservation($this);
        }
        return $this;
    }

    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $rating): static { $this->rating = $rating; return $this; }

    public function getRatingComment(): ?string { return $this->ratingComment; }
    public function setRatingComment(?string $ratingComment): static { $this->ratingComment = $ratingComment; return $this; }

    // ✅ NUEVO
    public function getReturnNote(): ?string { return $this->returnNote; }
    public function setReturnNote(?string $note): static
    {
        $note = $note !== null ? trim($note) : null;
        $this->returnNote = ($note === '') ? null : $note;
        return $this;
    }

    public function getReturnPenalty(): ?string { return $this->returnPenalty; }
    public function setReturnPenalty(?string $val): static
    {
        $val = $val !== null ? trim($val) : null;
        $this->returnPenalty = ($val === '') ? null : $val;
        return $this;
    }

    public function __toString(): string { return 'Reserva #' . ($this->id ?? 0); }
}