<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['occurred_at'], name: 'idx_audit_occurred_at')]
#[ORM\Index(columns: ['actor_email'], name: 'idx_audit_actor_email')]
#[ORM\Index(columns: ['entity_class', 'entity_id'], name: 'idx_audit_entity')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'occurred_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(name: 'actor_email', length: 180, nullable: true)]
    private ?string $actorEmail = null;

    #[ORM\Column(length: 20)]
    private string $action; // create|update|delete|custom

    #[ORM\Column(name: 'entity_class', length: 255)]
    private string $entityClass;

    #[ORM\Column(name: 'entity_id', length: 64, nullable: true)]
    private ?string $entityId = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $changes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $meta = null;

    public function __construct(
        string $action,
        string $entityClass,
        ?string $entityId,
        ?string $actorEmail,
        ?array $changes = null,
        ?array $meta = null
    ) {
        $this->occurredAt = new \DateTimeImmutable('now');
        $this->action = $action;
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->actorEmail = $actorEmail;
        $this->changes = $changes;
        $this->meta = $meta;
    }

    public function getId(): ?int { return $this->id; }
    public function getOccurredAt(): \DateTimeImmutable { return $this->occurredAt; }
    public function getActorEmail(): ?string { return $this->actorEmail; }
    public function getAction(): string { return $this->action; }
    public function getEntityClass(): string { return $this->entityClass; }
    public function getEntityId(): ?string { return $this->entityId; }
    public function getChanges(): ?array { return $this->changes; }
    public function getMeta(): ?array { return $this->meta; }
}