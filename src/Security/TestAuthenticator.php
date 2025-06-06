<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TestAuthenticator extends AbstractAuthenticator
{
    private const HEADER_NAME = 'X-Test-Auth-Email';
    
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(self::HEADER_NAME);
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->headers->get(self::HEADER_NAME);
        
        if (empty($email)) {
            throw new CustomUserMessageAuthenticationException('Test auth email header is empty');
        }
        
        // Only allow test users (puppeteer.test*)
        if (!str_starts_with($email, 'puppeteer.test')) {
            throw new CustomUserMessageAuthenticationException('Only test users are allowed for header authentication');
        }
        
        return new SelfValidatingPassport(
            new UserBadge($email, function ($userIdentifier) {
                $user = $this->userRepository->findOneBy(['email' => $userIdentifier]);
                
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Test user not found');
                }
                
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Continue to the original request
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}