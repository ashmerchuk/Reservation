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

        $sql = "INSERT INTO reservation (email, password) VALUES ('$usersEmail', '$usersPassword')";
        $result = $conn->query($sql);

        $conn->close();

        echo "Connected successfully";
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

// Prepare the SQL query with placeholders
        $sql = "SELECT * FROM reservation WHERE email = ? AND password = ?";

// Create a prepared statement
        $stmt = $conn->prepare($sql);

//        dd($stmt);
        if ($stmt) {
            // Bind the parameters to the placeholders
            $stmt->bind_param("ss", $usersEmail, $usersPassword);

            // Execute the query
            $stmt->execute();

            // Get the result
            $result = $stmt->get_result();
            // Fetch the data
            $row = $result->fetch_assoc();

            if ($row) {
                return $this->render(
                    'custom_templates/reservationPage.html.twig'
                );
            }
        }
        return $this->redirectToRoute('login');
    }
};
