<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $subscriptionPlan = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $subscriptionExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $teamsAccountName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $organizationName = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isOrganizationAdmin = true;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ServiceIntegration::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $serviceIntegrations;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->roles = ['ROLE_USER'];
        $this->serviceIntegrations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getSubscriptionPlan(): ?string
    {
        return $this->subscriptionPlan;
    }

    public function setSubscriptionPlan(?string $subscriptionPlan): static
    {
        $this->subscriptionPlan = $subscriptionPlan;
        return $this;
    }

    public function getSubscriptionExpiresAt(): ?\DateTimeInterface
    {
        return $this->subscriptionExpiresAt;
    }

    public function setSubscriptionExpiresAt(?\DateTimeInterface $subscriptionExpiresAt): static
    {
        $this->subscriptionExpiresAt = $subscriptionExpiresAt;
        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getTeamsAccountName(): ?string
    {
        return $this->teamsAccountName;
    }

    public function setTeamsAccountName(?string $teamsAccountName): static
    {
        $this->teamsAccountName = $teamsAccountName;
        return $this;
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptionPlan && 
               ($this->subscriptionExpiresAt === null || $this->subscriptionExpiresAt > new \DateTime());
    }

    public function getOrganizationName(): ?string
    {
        return $this->organizationName;
    }

    public function setOrganizationName(?string $organizationName): static
    {
        $this->organizationName = $organizationName;
        return $this;
    }

    public function isOrganizationAdmin(): bool
    {
        return $this->isOrganizationAdmin;
    }

    public function setIsOrganizationAdmin(bool $isOrganizationAdmin): static
    {
        $this->isOrganizationAdmin = $isOrganizationAdmin;
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
            $serviceIntegration->setUser($this);
        }

        return $this;
    }

    public function removeServiceIntegration(ServiceIntegration $serviceIntegration): static
    {
        if ($this->serviceIntegrations->removeElement($serviceIntegration)) {
            if ($serviceIntegration->getUser() === $this) {
                $serviceIntegration->setUser(null);
            }
        }

        return $this;
    }
}