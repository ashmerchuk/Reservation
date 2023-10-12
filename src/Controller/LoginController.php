<?php

namespace App\Controller;

use mysqli;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LoginController extends AbstractController

{
    public function logIn(): Response
    {
        return $this->render(
            'custom_templates/login.html.twig'
        );
    }

    public function signUp(Request $request, SessionInterface $session): Response
    {
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

        $createtable = "CREATE TABLE IF NOT EXISTS `reservation` (`id` int AUTO_INCREMENT, `email` varchar(255), `password` varchar(255), `user_id` varchar(255), PRIMARY KEY (`id`))";
        $result = $conn->query($createtable);

        $hashedPassword = password_hash($usersPassword, PASSWORD_DEFAULT);
        $sql = "INSERT INTO reservation (email, password) VALUES ('$usersEmail', '$hashedPassword')";
        $result = $conn->query($sql);
        $conn->close();

        return $this->redirectToRoute('login');
    }

    public function signIn(Request $request): Response
    {
        $usersEmail = $request->get('signInEmail');
        $usersPassword = $request->get('signInPassword');

        $servername = "reservation-mysql";
        $username = "root";
        $password = "test_pass";

// Create connection
        $conn = new mysqli($servername, $username, $password, 'reservation');

// Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT email, password FROM reservation WHERE email = '$usersEmail'";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();

            if ($row) {
                $hashedPassword = $row['password'];
                password_verify($usersPassword, $hashedPassword);
                if (password_verify($usersPassword, $hashedPassword)) {
                    // Password is correct. You can proceed with authentication.

                    $sql = "SELECT id FROM reservation WHERE email = '$usersEmail'";
                    $stmt = $conn->prepare($sql);
                    $result = $conn->query($sql);
                    if ($result) {
                        $row = $result->fetch_assoc();
                        $userId = $row['id'];
                        if (session_status() !== PHP_SESSION_ACTIVE) {
                            session_start();
                            $_SESSION['user_id'] = $userId;
                        }
                    }else{
                        return $this->redirectToRoute('login');
                    }
                    return $this->render('custom_templates/reservationPage.html.twig',[
                        'userId' => $userId,
                        'email' => $usersEmail
                ]);
                }
            }
        }
        return $this->redirectToRoute('login');
    }

    public function logout (): Response{
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return $this->redirectToRoute('login');
    }
};
