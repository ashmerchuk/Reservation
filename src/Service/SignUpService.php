<?php

namespace App\Service;

class SignUpService
{
    public function addUser(string $sanitiseEmail, string $sanitisePassword): bool
    {
        return $this->repository->addUser($sanitiseEmail, $sanitisePassword);
    }

}