<?php

namespace App\Entity;

use App\Repository\CustomServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomServiceRepository::class)]
#[ORM\Table(name: 'custom_service')]
class CustomService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'customService', targetEntity: ServiceIntegration::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?ServiceIntegration $serviceIntegration = null;

    #[ORM\Column(length: 500)]
    private ?string $baseUrl = null;

    #[ORM\OneToMany(mappedBy: 'customService', targetEntity: CustomServiceHeader::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $headers;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->headers = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceIntegration(): ?ServiceIntegration
    {
        return $this->serviceIntegration;
    }

    public function setServiceIntegration(?ServiceIntegration $serviceIntegration): static
    {
        $this->serviceIntegration = $serviceIntegration;
        return $this;
    }

    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * @return Collection<int, CustomServiceHeader>
     */
    public function getHeaders(): Collection
    {
        return $this->headers;
    }

    public function addHeader(CustomServiceHeader $header): static
    {
        if (!$this->headers->contains($header)) {
            $this->headers->add($header);
            $header->setCustomService($this);
        }

        return $this;
    }

    public function removeHeader(CustomServiceHeader $header): static
    {
        if ($this->headers->removeElement($header)) {
            if ($header->getCustomService() === $this) {
                $header->setCustomService(null);
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