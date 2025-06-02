<?php

namespace App\Twig;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AvatarExtension extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('proxy_avatar_url', [$this, 'getProxyAvatarUrl']),
        ];
    }

    public function getProxyAvatarUrl(?string $avatarUrl): ?string
    {
        if (!$avatarUrl || !str_contains($avatarUrl, 'googleusercontent.com')) {
            return $avatarUrl;
        }

        $encodedUrl = base64_encode($avatarUrl);
        
        return $this->urlGenerator->generate('proxy_avatar', [
            'encodedUrl' => $encodedUrl
        ]);
    }
}