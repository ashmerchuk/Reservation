<?php

namespace App\Controller;

use mysqli;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SignUpController extends AbstractController
{
    public function signUp(Request $request, SessionInterface $session): Response
    {
        if ($request->getMethod() == 'GET') {
            return $this->render('custom_templates/signUp.html.twig');
        }

        $servername = "reservation-mysql";
        $username = "root";
        $password = "test_pass";

        // Create connection
        $conn = new mysqli($servername, $username, $password, 'reservation');

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $createtable = "CREATE TABLE IF NOT EXISTS `users` (`id` int AUTO_INCREMENT, `email` varchar(255), `name` varchar(255), `password` varchar(255), `user_id` varchar(255), PRIMARY KEY (`id`))";
        $result = $conn->query($createtable);

        $usersEmail = $request->get('signUpEmail');
        $usersName = $request->get('signUpName');
        $usersPassword = $request->get('signUpPassword');
        $sql = "SELECT email FROM `users` WHERE email = '$usersEmail'";
        $result = $conn->query($sql);
        if($result){
            $row = $result->fetch_assoc();
            if($result->num_rows == 0){
                $hashedPassword = password_hash($usersPassword, PASSWORD_DEFAULT);
                $sql = "INSERT INTO `users` (email, name, password) VALUES ('$usersEmail', '$usersName', '$hashedPassword')";
                $result = $conn->query($sql);
                $conn->close();
                $session->getFlashBag()->add('success_account_creating', 'You have successfully created account');
                return new RedirectResponse('signIn');
            }
            else{
                $session->getFlashBag()->add('error_account_creating', 'Failed by creating account, email is already exists');
                return new RedirectResponse('login');
            }
        }

        return new RedirectResponse('login');
    }
}
