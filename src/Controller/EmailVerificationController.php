<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify/email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
        CustomerRepository $customerRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $customer = $customerRepository->findOneBy(['verificationToken' => $token]);

        if (!$customer) {
            $this->addFlash('error', 'Invalid verification token.');
            return $this->redirectToRoute('app_login');
        }

        if ($customer->isVerified()) {
            $this->addFlash('info', 'Your email is already verified. You can log in.');
            return $this->redirectToRoute('app_login');
        }

        if ($customer->isTokenExpired()) {
            $this->addFlash('error', 'This verification link has expired. Please request a new one.');
            return $this->redirectToRoute('app_resend_verification');
        }

        // Verify the email
        $customer->setIsVerified(true);
        $customer->setVerificationToken(null);
        $customer->setTokenExpiresAt(null);
        $entityManager->flush();

        $this->addFlash('success', 'Your email has been verified successfully! You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_resend_verification')]
    public function resendVerification(
        Request $request,
        CustomerRepository $customerRepository,
        EntityManagerInterface $entityManager,
        EmailService $emailService
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            if (!$email) {
                $this->addFlash('error', 'Please provide your email address.');
                return $this->render('registration/resend_verification.html.twig');
            }

            $customer = $customerRepository->findOneBy(['email' => $email]);

            if (!$customer) {
                // Don't reveal if email exists or not for security
                $this->addFlash('success', 'If an account with that email exists and is not verified, a verification email has been sent.');
                return $this->render('registration/resend_verification.html.twig');
            }

            if ($customer->isVerified()) {
                $this->addFlash('info', 'Your email is already verified. You can log in.');
                return $this->redirectToRoute('app_login');
            }

            // Generate new token
            $token = bin2hex(random_bytes(32));
            $customer->setVerificationToken($token);
            $customer->setTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
            $entityManager->flush();

            try {
                $emailService->sendVerificationEmail($customer, $token);
                $this->addFlash('success', 'A new verification email has been sent to your email address.');
            } catch (\Exception $e) {
                error_log('Verification email sending failed: ' . $e->getMessage());
                $this->addFlash('error', 'Failed to send verification email. Please try again later.');
            }

            return $this->render('registration/resend_verification.html.twig');
        }

        return $this->render('registration/resend_verification.html.twig');
    }
}

