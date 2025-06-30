<?php

namespace App\Entity;

use App\Repository\N8nEnvironmentVariableRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: N8nEnvironmentVariableRepository::class)]
#[ORM\Table(name: 'n8n_environment_variables')]
#[ORM\UniqueConstraint(name: 'unique_user_variable', columns: ['user_id', 'variable_name'])]
class N8nEnvironmentVariable
{
    public const AVAILABLE_VARIABLES = [
        'WORKOFLOW_N8N_WEBHOOK_URL' => 'N8N Webhook URL',
    ];

    public const DEFAULT_VALUES = [
        'WORKOFLOW_N8N_WEBHOOK_URL' => 'https://workflows.vcec.cloud/webhook/016d8b95-d5a5-4ac6-acb5-7f642f',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $variableName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $variableValue = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getVariableName(): string
    {
        return $this->variableName;
    }

    public function setVariableName(string $variableName): static
    {
        $this->variableName = $variableName;
        return $this;
    }

    public function getVariableValue(): ?string
    {
        return $this->variableValue;
    }

    public function setVariableValue(?string $variableValue): static
    {
        $this->variableValue = $variableValue;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDisplayName(): string
    {
        return self::AVAILABLE_VARIABLES[$this->variableName] ?? $this->variableName;
    }
}