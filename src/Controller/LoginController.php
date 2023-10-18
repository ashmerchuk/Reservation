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

    public function signIn(Request $request): Response
    {

        dd('mo');
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

        dd($usersEmail);
        $sql = "SELECT email, password FROM `users` WHERE email = '$usersEmail'";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();

            if ($row) {
                $hashedPassword = $row['password'];
                password_verify($usersPassword, $hashedPassword);
                if (password_verify($usersPassword, $hashedPassword)) {
                    // Password is correct. You can proceed with authentication.

                    $sql = "SELECT id, name FROM `users` WHERE email = '$usersEmail'";
                    $stmt = $conn->prepare($sql);
                    $result = $conn->query($sql);
                    if ($result) {
                        $row = $result->fetch_assoc();
                        $userId = $row['id'];
                        $usersName = $row['name'];
                        if (session_status() !== PHP_SESSION_ACTIVE) {
                            session_start();
                            $_SESSION['user_id'] = $userId;
                        }else{
                            session_destroy();
                            session_start();
                            $_SESSION['user_id'] = $userId;
                        }
                    }else{
                        return $this->redirectToRoute('login');
                    }

                    $createtable = "CREATE TABLE IF NOT EXISTS `rooms` (`id` int AUTO_INCREMENT,`name` varchar(255),`image` varchar(255), PRIMARY KEY (id));";
                    $result = $conn->query($createtable);
//                    dd($result);
                    if($result) {
                        $sql = "SELECT * FROM `rooms`";
                        $result = $conn->query($sql);
                        if ($result->num_rows  == 0) {
                            $sql = "INSERT INTO rooms (name) VALUES
                                ('Hafencity'),
                                ('Fischmarkt')";
                            $result = $conn->query($sql);
                        }
                    }

                    //Adding array with rooms name to use it in template
                    $sql = "SELECT name FROM rooms";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $rooms[] = $row['name'];
                        }
                    }

                    return $this->render('custom_templates/bookingForm.html.twig',[
                        'userId' => $userId,
                        'usersName' => $usersName,
                        'email' => $usersEmail,
                        'rooms' => $rooms
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
