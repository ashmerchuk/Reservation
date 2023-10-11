<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class SignUpController
{
    public function signUp(Request $request, SessionInterface $session): Response
    {
        $usersEmail = $request->get('signUpEmail');
        $usersPassword = $request->get('signUpPassword');

        return new RedirectResponse('/login');
    }

}