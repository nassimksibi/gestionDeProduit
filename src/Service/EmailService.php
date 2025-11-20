<?php

namespace App\Service;

use App\Entity\Customer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    private function getFromEmail(): string
    {
        return $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@example.com';
    }

    private function getFromName(): string
    {
        return $_ENV['MAILER_FROM_NAME'] ?? 'Your App';
    }

    public function sendWelcomeEmail(Customer $customer): void
    {
        $loginUrl = $this->urlGenerator->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->getFromEmail(), $this->getFromName()))
            ->to(new Address($customer->getEmail(), $customer->getName()))
            ->subject('Welcome! Your Account Has Been Created Successfully')
            ->htmlTemplate('emails/welcome.html.twig')
            ->context([
                'customer' => $customer,
                'loginUrl' => $loginUrl,
            ]);

        $this->mailer->send($email);
    }

    public function sendVerificationEmail(Customer $customer, string $verificationToken): void
    {
        $verificationUrl = $this->urlGenerator->generate(
            'app_verify_email',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->getFromEmail(), $this->getFromName()))
            ->to(new Address($customer->getEmail(), $customer->getName()))
            ->subject('Verify Your Email Address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'customer' => $customer,
                'verificationUrl' => $verificationUrl,
                'token' => $verificationToken,
            ]);

        $this->mailer->send($email);
    }
}

