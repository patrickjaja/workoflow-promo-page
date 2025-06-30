<?php

namespace App\Entity;

use App\Repository\TeamsIdRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamsIdRepository::class)]
#[ORM\Table(name: 'teams_id')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_TEAMS_ID', columns: ['user_id', 'teams_id'])]
class TeamsId
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'teamsIds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $teamsId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPrimary = false;

    #[ORM\OneToMany(mappedBy: 'teamsId', targetEntity: ServiceIntegration::class)]
    private Collection $serviceIntegrations;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->serviceIntegrations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTeamsId(): ?string
    {
        return $this->teamsId;
    }

    public function setTeamsId(string $teamsId): static
    {
        $this->teamsId = $teamsId;
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): static
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    /**
     * @return Collection<int, ServiceIntegration>
     */
    public function getServiceIntegrations(): Collection
    {
        return $this->serviceIntegrations;
    }

    public function addServiceIntegration(ServiceIntegration $serviceIntegration): static
    {
        if (!$this->serviceIntegrations->contains($serviceIntegration)) {
            $this->serviceIntegrations->add($serviceIntegration);
            $serviceIntegration->setTeamsId($this);
        }

        return $this;
    }

    public function removeServiceIntegration(ServiceIntegration $serviceIntegration): static
    {
        if ($this->serviceIntegrations->removeElement($serviceIntegration)) {
            if ($serviceIntegration->getTeamsId() === $this) {
                $serviceIntegration->setTeamsId(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}