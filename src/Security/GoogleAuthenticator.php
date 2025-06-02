<?php

namespace App\Security;

use App\Entity\OrganizationMember;
use App\Entity\User;
use App\Repository\OrganizationMemberRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private OrganizationMemberRepository $memberRepository;
    private RouterInterface $router;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        OrganizationMemberRepository $memberRepository,
        RouterInterface $router
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->memberRepository = $memberRepository;
        $this->router = $router;
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $existingUser = $this->userRepository->findByGoogleId($googleUser->getId());

                if ($existingUser) {
                    return $existingUser;
                }

                $existingUserByEmail = $this->userRepository->findOneBy(['email' => $googleUser->getEmail()]);
                if ($existingUserByEmail) {
                    $existingUserByEmail->setGoogleId($googleUser->getId());
                    $existingUserByEmail->setAvatar($googleUser->getAvatar());
                    $this->entityManager->persist($existingUserByEmail);
                    $this->entityManager->flush();
                    return $existingUserByEmail;
                }

                $user = new User();
                $user->setEmail($googleUser->getEmail());
                $user->setName($googleUser->getName());
                $user->setGoogleId($googleUser->getId());
                $user->setAvatar($googleUser->getAvatar());

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $session = $request->getSession();

        // Check if there's a pending member invitation
        $pendingInvitation = $session->get('pending_member_invitation');
        if ($pendingInvitation && strtolower($user->getEmail()) === strtolower($pendingInvitation['email'])) {
            $member = $this->memberRepository->find($pendingInvitation['member_id']);
            
            if ($member && $member->isInvitationValid()) {
                // Link the user to the organization member
                $member->setUser($user);
                $member->setStatus(OrganizationMember::STATUS_ACTIVE);
                
                // Set user organization details if not an admin
                if (!$user->getOrganizationName()) {
                    $user->setOrganizationName($member->getOrganizationName());
                    $user->setIsOrganizationAdmin(false);
                }
                
                $this->entityManager->flush();
                $session->remove('pending_member_invitation');
                
                $session->getFlashBag()->add('success', sprintf(
                    'Welcome to %s! Your invitation has been accepted and you are now a member of the organization.',
                    $member->getOrganizationName()
                ));
                
                return new RedirectResponse($this->router->generate('account_settings'));
            }
        } elseif ($pendingInvitation && strtolower($user->getEmail()) !== strtolower($pendingInvitation['email'])) {
            // Email mismatch - clear session and show error
            $session->remove('pending_member_invitation');
            $session->getFlashBag()->add('error', sprintf(
                'The invitation was sent to %s, but you signed in with %s. Please sign in with the correct account or request a new invitation.',
                $pendingInvitation['email'],
                $user->getEmail()
            ));
        }

        return new RedirectResponse($this->router->generate('index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}