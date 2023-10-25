<?php

namespace App\Controller;

use mysqli;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mime\Email;

class LoginController extends AbstractController

{
    public function login(): Response
    {

//        dd($_SESSION['user_id']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            return $this->redirectToRoute('login');
        }

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

//        dd('lo');
        return $this->redirectToRoute('login');
    }

    public function forgotPassword (): Response{
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return $this->render('custom_templates/forgotPassword.twig');
    }

    public function  sendForgotPassword(Request $request, SessionInterface $session, MailerInterface $mailer): Response
    {
        $usersEmail = $request->get('emailForResetPassword');

//        dd($usersEmail);
        $resetToken = uniqid();

        $url = $this->getCurrentURL();
        $newUrl = str_replace("/sendForgotPassword", "", $url);

        $resetLink = $newUrl ."/resetPage?token=" . urlencode($resetToken);
        $subject = "Password Reset";
        $message = "Click the following link to reset your password: <a href=\"$resetLink\">Reset Password</a>";

        $transport = Transport::fromDsn('smtp://johndoegofer@gmail.com:fpidtafftygiqccr@smtp.gmail.com:587');
        // Create a Mailer object
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from('johndoegofer@gmail.com')
            ->to($usersEmail)
            ->subject($subject)
            ->html($message);

        $mailer->send($email);

        $session->getFlashBag()->add('check_email', 'Check your email to reset password');
        return $this->render('custom_templates/resetPage.twig');
    }
    private function getCurrentURL(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];
        return $protocol . $host . $requestUri;
    }

    public function resetPage(): Response
    {
        return $this->render(
            'custom_templates/resetPage.twig'
        );
    }

};
