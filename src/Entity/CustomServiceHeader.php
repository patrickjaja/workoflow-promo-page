<?php

namespace App\Entity;

use App\Repository\CustomServiceHeaderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomServiceHeaderRepository::class)]
#[ORM\Table(name: 'custom_service_header')]
class CustomServiceHeader
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CustomService::class, inversedBy: 'headers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CustomService $customService = null;

    #[ORM\Column(length: 255)]
    private ?string $headerName = null;

    #[ORM\Column(type: 'text')]
    private ?string $headerValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomService(): ?CustomService
    {
        return $this->customService;
    }

    public function setCustomService(?CustomService $customService): static
    {
        $this->customService = $customService;
        return $this;
    }

    public function getHeaderName(): ?string
    {
        return $this->headerName;
    }

    public function setHeaderName(string $headerName): static
    {
        $this->headerName = $headerName;
        return $this;
    }

    public function getHeaderValue(): ?string
    {
        return $this->headerValue;
    }

    public function setHeaderValue(string $headerValue): static
    {
        $this->headerValue = $headerValue;
        return $this;
    }
}