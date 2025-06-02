<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class EnterpriseController extends AbstractController
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    #[Route('/enterprise/contact', name: 'enterprise_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('enterprise/contact.html.twig');
    }

    #[Route('/enterprise/contact/submit', name: 'enterprise_contact_submit', methods: ['POST'])]
    public function submitContact(Request $request): Response
    {
        $companyName = $request->request->get('company_name');
        $companySize = $request->request->get('company_size');
        $contactName = $request->request->get('contact_name');
        $contactEmail = $request->request->get('contact_email');
        $phone = $request->request->get('phone', 'Not provided');
        $country = $request->request->get('country');
        $currentTools = $request->request->get('current_tools');
        $useCases = $request->request->get('use_cases');
        $requirements = $request->request->all('requirements');
        $timeline = $request->request->get('timeline');
        $additionalInfo = $request->request->get('additional_info', 'None');

        $requirementsText = empty($requirements) ? 'None specified' : implode(', ', $requirements);

        $emailBody = sprintf(
            "New Enterprise Teams AI Integration Request

Company Information:
-------------------
Company Name: %s
Company Size: %s
Country: %s

Contact Information:
-------------------
Name: %s
Email: %s
Phone: %s

Integration Details:
-------------------
Current Tools & Systems:
%s

Primary Use Cases:
%s

Key Requirements:
%s

Implementation Timeline: %s

Additional Information:
%s

-------------------
This is an enterprise inquiry. Please follow up within 1 business day.

Review the requirements and prepare:
- Customized Teams bot deployment proposal
- n8n workflow architecture for their use cases
- GDPR compliance documentation
- Valantic infrastructure scaling plan
- Pricing based on company size and requirements",
            $companyName,
            $companySize,
            $country,
            $contactName,
            $contactEmail,
            $phone,
            $currentTools,
            $useCases,
            $requirementsText,
            $timeline,
            $additionalInfo
        );

        $email = (new Email())
            ->from('noreply@workoflow.com')
            ->to('admin@workoflow.com')
            ->replyTo($contactEmail)
            ->subject(sprintf('Enterprise Inquiry: %s (%s employees)', $companyName, $companySize))
            ->text($emailBody)
            ->priority(Email::PRIORITY_HIGH);

        try {
            $this->mailer->send($email);
            
            $this->addFlash('success', 'Thank you for your enterprise inquiry! Our sales team will contact you within 1 business day to discuss your Teams AI integration requirements.');
            
            return $this->redirectToRoute('index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'There was an error submitting your inquiry. Please try again or contact us directly at admin@workoflow.com');
            
            return $this->redirectToRoute('enterprise_contact');
        }
    }
}