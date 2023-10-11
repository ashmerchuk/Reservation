<?php

namespace App\Controller;

use mysqli;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends AbstractController

{
    public function logIn(): Response
    {
        return $this->render(
            'custom_templates/login.html.twig'
        );
    }

    public function signUp(Request $request): Response
    {
//        dd($request->get('signUpEmail'));
        $usersEmail = $request->get('signUpEmail');
        $usersPassword = $request->get('signUpPassword');


        $servername = "reservation-mysql";
        $username = "root";
        $password = "test_pass";

// Create connection
        $conn = new mysqli($servername, $username, $password, 'reservation');

// Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "INSERT INTO reservation (email, password) VALUES ('$usersEmail', '$usersPassword')";
        $result = $conn->query($sql);

        $conn->close();

        echo "Connected successfully";
        return $this->render(
            'custom_templates/login.html.twig'
        );
    }
};
