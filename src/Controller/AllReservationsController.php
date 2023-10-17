<?php

namespace App\Controller;

use mysqli;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class AllReservationsController extends AbstractController
{
    public function allReservations(Request $request, SessionInterface $session): Response
    {
//        dd('moin');
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
//            dd('moin');
        $servername = "reservation-mysql";
        $username = "root";
        $password = "test_pass";

// Create connection
        $conn = new mysqli($servername, $username, $password, 'reservation');

// Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $userId = $_SESSION['user_id'];

        $sql = "SELECT d.name AS desk_name, d.id as desk_id, r.reservation_time, r.user_id, ro.name AS room_name
        FROM reservations r
        INNER JOIN desks d ON r.desk_id = d.id
        INNER JOIN rooms ro ON d.room_id = ro.id
        WHERE r.user_id = $userId";

        $result = $conn->query($sql);

        if ($result) {
            $reservations = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            // Handle the query error
        }

        $sql = "SELECT name, email FROM users WHERE id = $userId";
        $result = $conn->query($sql);
        if($result){
            $row = $result->fetch_assoc();
            $userName = $row['name'];
            $userEmail = $row['email'];
        }

        return $this->render('custom_templates/allReservations.html.twig', [
            'reservations' => $reservations, // Pass the reservations data to the template
            'usersName' => $userName,
            'email' => $userEmail
        ]);
    }
}