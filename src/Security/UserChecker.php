<?php

namespace App\Security;

use App\Entity\Customer;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Customer) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                'Your email address has not been verified. Please check your email and click the verification link, or request a new verification email.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No post-authentication checks needed
    }
}

