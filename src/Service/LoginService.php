<?php

namespace App\Service;

use App\Repository\LoginRepository;

class LoginService
{
    public function __construct(
        private readonly LoginRepository $repository
    ) {
    }

    public function checkUser(string $sanitiseEmail, string $sanitisePassword): ?int
    {
        return $this->repository->checkUser($sanitiseEmail, $sanitisePassword);
    }

}