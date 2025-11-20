<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\RegistrationFormType;
use App\Repository\CustomerRepository;
use App\Service\EmailService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class CustomerRegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        CustomerRepository $customerRepository,
        EmailService $emailService
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $customer = new Customer();
        $form = $this->createForm(RegistrationFormType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email already exists
            $existingCustomer = $customerRepository->findOneBy(['email' => $customer->getEmail()]);
            if ($existingCustomer) {
                $this->addFlash('error', 'This email address is already registered. Please use a different email or try to login.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Encode the plain password
            $customer->setPassword(
                $passwordHasher->hashPassword(
                    $customer,
                    $form->get('password')->getData()
                )
            );

            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            $customer->setVerificationToken($verificationToken);
            $customer->setTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
            $customer->setIsVerified(false);

            try {
                $entityManager->persist($customer);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'This email address is already registered. Please use a different email or try to login.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Send verification email
            try {
                $emailService->sendVerificationEmail($customer, $verificationToken);
                $this->addFlash('success', 'Registration successful! Please check your email to verify your account before logging in.');
            } catch (\Exception $e) {
                // Log the error but don't prevent registration
                error_log('Verification email sending failed: ' . $e->getMessage());
                $this->addFlash('warning', 'Registration successful, but we couldn\'t send the verification email. Please use the resend verification link.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

