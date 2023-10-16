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

        $sql = "SELECT d.id AS desk_id
                FROM desks d
                LEFT JOIN reservations r ON d.id = r.desk_id AND r.reservation_time = '$pikedDate'
                WHERE d.room_id = '$roomId'
                AND r.id IS NULL";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);

        $desksId = array(); // Initialize an array to store desk IDs

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $desksId[] = $row['desk_id']; // Add each desk_id to the array
            }
        } else {
            return $this->redirectToRoute('login');
        }

//        dd($desksId);


        return $this->render('custom_templates/desks.html.twig', [
            'userId' => $userId,
            'usersName' => $usersName,
            'email' => $usersEmail,
            'date' => $pikedDate,
            'roomName' => $roomName,
            'desksId' => $desksId
        ]);
    }
}