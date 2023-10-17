<?php

namespace App\Controller;

use mysqli;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class SignInController extends AbstractController
{
    /**
     * @Route("/signIn", name="sign_in")
     */
    public function signIn(): Response
    {
        return $this->render('custom_templates/signIn.html.twig'); // Render your sign-in template
    }
}