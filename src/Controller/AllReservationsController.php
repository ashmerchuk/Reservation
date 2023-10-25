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
        if(!isset($_SESSION['user_id'])) {
            return $this->redirectToRoute('login');
        }
        $userId = $_SESSION['user_id'];

        $sql = "SELECT r.id AS reservation_id, d.name AS desk_name, d.id AS desk_id, r.reservation_time, r.user_id, ro.name AS room_name
        FROM reservations r
        INNER JOIN desks d ON r.desk_id = d.id
        INNER JOIN rooms ro ON d.room_id = ro.id
        WHERE r.user_id = $userId
        ";

        $result = $conn->query($sql);

        if ($result) {
            $reservations = $result->fetch_all(MYSQLI_ASSOC);
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
    public function deleteReservations(Request $request, SessionInterface $session): Response
    {
//        dd($request->get('email'));
//        dd($request->get('reservation_id'));
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
        $reservationId = $request->get('reservation_id');
        $sql = "DELETE FROM reservations WHERE id = $reservationId";
        $result = $conn->query($sql);

        $userId = $_SESSION['user_id'];

        $sql = "SELECT r.id AS reservation_id, d.name AS desk_name, d.id AS desk_id, r.reservation_time, r.user_id, ro.name AS room_name
        FROM reservations r
        INNER JOIN desks d ON r.desk_id = d.id
        INNER JOIN rooms ro ON d.room_id = ro.id
        WHERE r.user_id = $userId
        ";

        $result = $conn->query($sql);

        $today = date("Y-m-d");
        if ($result) {
            $reservations = $result->fetch_all(MYSQLI_ASSOC);
            foreach ($reservations as $reservation) {
                $reservationTime = $reservation['reservation_time'];

                if ($reservationTime < $today) {
                    $reservationId = $reservation['reservation_id'];
                    // Delete past reservation from the database
                    $sql = "DELETE FROM reservations WHERE id = $reservationId";
                    $conn->query($sql);
                }
            }
        }


        $usersEmail = $request->get('email');
//        dd($usersEmail);
        $sql = "SELECT name, email FROM users WHERE email = '$usersEmail'";
        $result = $conn->query($sql);
        if($result){
            $row = $result->fetch_assoc();
//            dd($row);
            $userName = $row['name'];
            $userEmail = $row['email'];
        }

//        dd('as');
        return $this->render('custom_templates/allReservations.html.twig', [
            'reservations' => $reservations, // Pass the reservations data to the template
            'usersName' => $userName,
            'email' => $userEmail
        ]);

    }
}