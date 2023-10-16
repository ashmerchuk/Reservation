<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use mysqli;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class RoomController extends AbstractController
{
    public function room(Request $request, SessionInterface $session): Response
    {
        dd($request);
    }
}