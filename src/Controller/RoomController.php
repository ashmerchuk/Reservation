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

//        $sql = "SELECT id FROM reservations WHERE user_id = ? AND reservation_time = ?";
//        $stmt = $conn->prepare($sql);
//        $stmt->bind_param("ss", $userId, $pickedDate);
//        $stmt->execute();
//        $result = $stmt->get_result();
//
//        // If a reservation is found, redirect with an error message
//        if ($result->num_rows > 0) {
//            dd('moin');
//            header("Location: home.php?error=already_reserved");
//            exit;
//        }else{
//            dd('no moin');
//        }




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

//        dd($desksImage, $desksId);
//        dd($desksId);


        return $this->render('custom_templates/desks.html.twig', [
            'userId' => $userId,
            'usersName' => $usersName,
            'email' => $usersEmail,
            'date' => $pikedDate,
            'roomName' => $roomName,
            'desks' => $desks
        ]);
    }
}