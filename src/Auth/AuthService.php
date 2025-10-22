<?php

declare(strict_types=1);

namespace BsoutVendas\Auth;

use BsoutVendas\Repositories\UserRepository;

class AuthService
{
    public function __construct(private UserRepository $users)
    {
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_email'] = $user['email'];
        return true;
    }

    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function logout(): void
    {
        session_destroy();
    }
}
