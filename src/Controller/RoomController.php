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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $userId = $_SESSION['user_id'];
        $roomId = $request->get('room_id');
        $pikedDate = $request->request->get('pikedDate');

        $servername = "reservation-mysql";
        $username = "root";
        $password = "test_pass";

        // Create connection
        $conn = new mysqli($servername, $username, $password, 'reservation');

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM `users` WHERE id = '$userId'";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);

        if ($result) {
            $row = $result->fetch_assoc();
            $usersName = $row['name'];
            $usersEmail = $row['email'];
        } else {
            return $this->redirectToRoute('login');
        }

        $sql = "SELECT name from rooms where id = '$roomId'";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);

        if ($result) {
            $row = $result->fetch_assoc();
            $roomName = $row['name'];
        } else {
            return $this->redirectToRoute('login');
        }

        $sql = "SELECT d.id AS desk_id, d.image, name
                FROM desks d
                LEFT JOIN reservations r ON d.id = r.desk_id AND r.reservation_time = '$pikedDate'
                WHERE d.room_id = '$roomId'
                AND r.id IS NULL";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);

        $desksId = array(); // Initialize an array to store desk IDs

        if ($result) {
            $desks = []; // Create an empty array to store desks
            while ($row = $result->fetch_assoc()) {
                $desk = [
                    'id' => $row['desk_id'],
                    'image' => $row['image'],
                    'name' => $row['name']
                ];

                $desks[] = $desk; // Add the desk to the desks array
            }
        } else {
            return $this->redirectToRoute('login');
        }


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

        return $this->render('custom_templates/desks.html.twig', [
            'userId' => $userId,
            'usersName' => $usersName,
            'email' => $usersEmail,
            'date' => $pikedDate,
            'roomName' => $roomName,
            'desks' => $desks,
            'reservations' => $reservations
        ]);
    }
}