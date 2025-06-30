<?php

namespace App\Controller;

use App\Entity\OrganizationMember;
use App\Entity\User;
use App\Repository\OrganizationMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MembersController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrganizationMemberRepository $memberRepository,
        private MailerInterface $mailer
    ) {}

    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isOrganizationAdmin()) {
            throw $this->createAccessDeniedException('Only organization admins can manage members.');
        }

        if (!$user->getOrganizationName()) {
            $this->addFlash('error', 'Please set your organization name in your profile first.');
            return $this->redirectToRoute('account_profile');
        }

        $organizationName = $user->getOrganizationName();
        $activeMembers = $this->memberRepository->findActiveByOrganization($organizationName);
        $pendingMembers = $this->memberRepository->findPendingByOrganization($organizationName);
        $totalMembers = $this->memberRepository->countByOrganization($organizationName);

        return $this->render('account/members.html.twig', [
            'activeMembers' => $activeMembers,
            'pendingMembers' => $pendingMembers,
            'totalMembers' => $totalMembers,
        ]);
    }

    public function invite(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isOrganizationAdmin()) {
            throw $this->createAccessDeniedException('Only organization admins can invite members.');
        }

        if (!$user->getOrganizationName()) {
            $this->addFlash('error', 'Please set your organization name in your profile first.');
            return $this->redirectToRoute('account_profile');
        }

        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $organizationName = $user->getOrganizationName();

        if (empty($name) || empty($email)) {
            $this->addFlash('error', 'Name and email are required.');
            return $this->redirectToRoute('account_members');
        }

        // Check if member already exists
        $existingMember = $this->memberRepository->findByOrganizationAndEmail($organizationName, $email);
        if ($existingMember) {
            $this->addFlash('error', 'A member with this email already exists.');
            return $this->redirectToRoute('account_members');
        }

        // Create new member
        $member = new OrganizationMember();
        $member->setOrganizationName($organizationName);
        $member->setName($name);
        $member->setEmail($email);
        $member->setInvitedBy($user);
        $member->generateInvitationToken();

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        // Send invitation email
        $this->sendInvitationEmail($member, $user);

        $this->addFlash('success', sprintf('Invitation sent to %s.', $email));
        return $this->redirectToRoute('account_members');
    }

    public function resendInvite(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isOrganizationAdmin()) {
            throw $this->createAccessDeniedException('Only organization admins can resend invitations.');
        }

        $member = $this->memberRepository->find($id);
        if (!$member || $member->getOrganizationName() !== $user->getOrganizationName()) {
            throw $this->createNotFoundException('Member not found.');
        }

        // Generate new invitation token
        $member->generateInvitationToken();
        $this->entityManager->flush();

        // Resend invitation email
        $this->sendInvitationEmail($member, $user);

        $this->addFlash('success', sprintf('Invitation resent to %s.', $member->getEmail()));
        return $this->redirectToRoute('account_members');
    }

    public function cancelInvite(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isOrganizationAdmin()) {
            throw $this->createAccessDeniedException('Only organization admins can cancel invitations.');
        }

        $member = $this->memberRepository->find($id);
        if (!$member || $member->getOrganizationName() !== $user->getOrganizationName()) {
            throw $this->createNotFoundException('Member not found.');
        }

        if ($member->getStatus() !== OrganizationMember::STATUS_PENDING) {
            $this->addFlash('error', 'Cannot cancel invitation for active members.');
            return $this->redirectToRoute('account_members');
        }

        $this->entityManager->remove($member);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('Invitation for %s has been cancelled.', $member->getEmail()));
        return $this->redirectToRoute('account_members');
    }

    public function deactivate(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user->isOrganizationAdmin()) {
            throw $this->createAccessDeniedException('Only organization admins can deactivate members.');
        }

        $member = $this->memberRepository->find($id);
        if (!$member || $member->getOrganizationName() !== $user->getOrganizationName()) {
            throw $this->createNotFoundException('Member not found.');
        }

        if ($member->getStatus() !== OrganizationMember::STATUS_ACTIVE) {
            $this->addFlash('error', 'Cannot deactivate non-active members.');
            return $this->redirectToRoute('account_members');
        }

        $member->setStatus(OrganizationMember::STATUS_INACTIVE);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('%s has been deactivated.', $member->getName()));
        return $this->redirectToRoute('account_members');
    }

    public function acceptInvitation(string $token, Request $request): Response
    {
        $member = $this->memberRepository->findByInvitationToken($token);
        
        if (!$member || !$member->isInvitationValid()) {
            $this->addFlash('error', 'Invalid or expired invitation link.');
            return $this->redirectToRoute('connect_google_start');
        }

        // Store the member info in session for after login
        $session = $request->getSession();
        $session->set('pending_member_invitation', [
            'member_id' => $member->getId(),
            'organization_name' => $member->getOrganizationName(),
            'email' => $member->getEmail()
        ]);

        $this->addFlash('info', sprintf('Welcome! Please sign in with Google to complete your invitation to %s.', $member->getOrganizationName()));
        return $this->redirectToRoute('connect_google_start');
    }

    private function sendInvitationEmail(OrganizationMember $member, User $invitedBy): void
    {
        $invitationUrl = $this->generateUrl(
            'member_accept_invitation',
            ['token' => $member->getInvitationToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $emailBody = sprintf(
            "You've been invited to join %s

Hi %s,

%s has invited you to join their organization '%s' on Workoflow.

As a member, you'll be able to:
- Manage your own service integrations (Atlassian, Decidalo, Custom Content Search)
- Access team automation workflows
- Collaborate with your team through Teams AI integration

To accept this invitation and set up your account, click the link below:
%s

This invitation will expire in 7 days.

If you have any questions, please contact %s at %s.

Best regards,
The Workoflow Team",
            $member->getOrganizationName(),
            $member->getName(),
            $invitedBy->getName(),
            $member->getOrganizationName(),
            $invitationUrl,
            $invitedBy->getName(),
            $invitedBy->getEmail()
        );

        $email = (new Email())
            ->from('noreply@workoflow.com')
            ->to($member->getEmail())
            ->replyTo($invitedBy->getEmail())
            ->subject(sprintf('Invitation to join %s on Workoflow', $member->getOrganizationName()))
            ->text($emailBody);

        $this->mailer->send($email);
    }
}