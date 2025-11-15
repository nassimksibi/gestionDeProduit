<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\RegistrationFormType;
use App\Repository\CustomerRepository;
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
        CustomerRepository $customerRepository
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

            try {
                $entityManager->persist($customer);
                $entityManager->flush();
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'This email address is already registered. Please use a different email or try to login.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $this->addFlash('success', 'Registration successful! You can now login.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}

