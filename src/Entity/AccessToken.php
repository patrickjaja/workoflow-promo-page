<?php

namespace App\Entity;

use App\Repository\AccessTokenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
#[ORM\Table(name: 'access_token')]
class AccessToken
{
    public const SERVICE_ATLASSIAN = 'atlassian';
    public const SERVICE_DECIDALO = 'decidalo';
    public const SERVICE_CUSTOM_CONTENT_SEARCH = 'custom_content_search';
    public const SERVICE_SERVICEMAP = 'servicemap';
    public const SERVICE_CUSTOM = 'custom';

    public const AVAILABLE_SERVICES = [
        self::SERVICE_ATLASSIAN,
        self::SERVICE_DECIDALO,
        self::SERVICE_CUSTOM_CONTENT_SEARCH,
        self::SERVICE_SERVICEMAP,
        self::SERVICE_CUSTOM,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ServiceIntegration::class, inversedBy: 'accessTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ServiceIntegration $serviceIntegration = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $token = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $confluenceUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $confluenceUsername = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $confluenceApiToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jiraUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $jiraUsername = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $jiraApiToken = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $apiUrl = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
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

    public function getUser(): ?User
    {
        return $this->serviceIntegration?->getUser();
    }

    public function getService(): ?string
    {
        return $this->serviceIntegration?->getService();
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        $this->updatedAt = new \DateTime();
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getConfluenceUrl(): ?string
    {
        return $this->confluenceUrl;
    }

    public function setConfluenceUrl(?string $confluenceUrl): static
    {
        $this->confluenceUrl = $confluenceUrl;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getConfluenceUsername(): ?string
    {
        return $this->confluenceUsername;
    }

    public function setConfluenceUsername(?string $confluenceUsername): static
    {
        $this->confluenceUsername = $confluenceUsername;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getConfluenceApiToken(): ?string
    {
        return $this->confluenceApiToken;
    }

    public function setConfluenceApiToken(?string $confluenceApiToken): static
    {
        $this->confluenceApiToken = $confluenceApiToken;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getJiraUrl(): ?string
    {
        return $this->jiraUrl;
    }

    public function setJiraUrl(?string $jiraUrl): static
    {
        $this->jiraUrl = $jiraUrl;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getJiraUsername(): ?string
    {
        return $this->jiraUsername;
    }

    public function setJiraUsername(?string $jiraUsername): static
    {
        $this->jiraUsername = $jiraUsername;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getJiraApiToken(): ?string
    {
        return $this->jiraApiToken;
    }

    public function setJiraApiToken(?string $jiraApiToken): static
    {
        $this->jiraApiToken = $jiraApiToken;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getApiUrl(): ?string
    {
        return $this->apiUrl;
    }

    public function setApiUrl(?string $apiUrl): static
    {
        $this->apiUrl = $apiUrl;
        $this->updatedAt = new \DateTime();
        return $this;
    }

}