<?php

namespace App\Entity;

use App\Repository\IncomingConnectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IncomingConnectionRepository::class)]
#[ORM\Table(name: 'incoming_connection')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_CONNECTION_ID', columns: ['user_id', 'connection_id'])]
class IncomingConnection
{
    public const INTERFACE_TYPE_MS_TEAMS = 'ms_teams';
    
    public const AVAILABLE_INTERFACE_TYPES = [
        self::INTERFACE_TYPE_MS_TEAMS => 'Microsoft Teams',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'incomingConnections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    private ?string $interfaceType = null;

    #[ORM\Column(length: 255)]
    private ?string $connectionId = null;

    #[ORM\OneToMany(mappedBy: 'incomingConnection', targetEntity: ServiceIntegration::class)]
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

    public function getInterfaceType(): ?string
    {
        return $this->interfaceType;
    }

    public function setInterfaceType(string $interfaceType): static
    {
        if (!array_key_exists($interfaceType, self::AVAILABLE_INTERFACE_TYPES)) {
            throw new \InvalidArgumentException('Invalid interface type');
        }
        
        $this->interfaceType = $interfaceType;
        return $this;
    }

    public function getConnectionId(): ?string
    {
        return $this->connectionId;
    }

    public function setConnectionId(string $connectionId): static
    {
        $this->connectionId = $connectionId;
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
            $serviceIntegration->setIncomingConnection($this);
        }

        return $this;
    }

    public function removeServiceIntegration(ServiceIntegration $serviceIntegration): static
    {
        if ($this->serviceIntegrations->removeElement($serviceIntegration)) {
            if ($serviceIntegration->getIncomingConnection() === $this) {
                $serviceIntegration->setIncomingConnection(null);
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
    
    public function getInterfaceTypeLabel(): string
    {
        return self::AVAILABLE_INTERFACE_TYPES[$this->interfaceType] ?? 'Unknown';
    }
}