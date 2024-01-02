<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use mysqli;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HomeController extends AbstractController
{
    public function home(Request $request, SessionInterface $session): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $servername = "reservation-mysql";
        $username = "root";
        $password = "test_pass";
        // Create connection
        $conn = new mysqli($servername, $username, $password, 'reservation');

        // Check if the 'reservation' database exists
        $query = "CREATE DATABASE IF NOT EXISTS reservation";
        $conn->query($query);

        // Select the 'reservation' database
        $conn->select_db("reservation");

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $usersEmail = $request->get('signInEmail');
        if ($usersEmail === null && !isset($_SESSION['user_id'])) {
            return $this->redirectToRoute('login');
        }
        if ($usersEmail !== null) {
            $sql = "SELECT * FROM `users` WHERE email = '$usersEmail'";
            $stmt = $conn->prepare($sql);
            $result = $conn->query($sql);
        } else {
            $userId = $_SESSION['user_id'];
            $sql = "SELECT * FROM `users` WHERE id = $userId";
            $stmt = $conn->prepare($sql);
            $result = $conn->query($sql);
        }

        if ($result) {
            $row = $result->fetch_assoc();
            $usersName = $row['name'];
            $usersEmail = $row['email'];
            $userId = $row['id'];
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
                $_SESSION['user_id'] = $userId;
            } else {
                $_SESSION['user_id'] = $userId;
            }
        } else {
            return $this->redirectToRoute('login');
        }

        $createtable = "CREATE TABLE IF NOT EXISTS `rooms` (`id` int AUTO_INCREMENT, `name` varchar(255), `image` varchar(255), PRIMARY KEY (id));";
        $result = $conn->query($createtable);

        if ($result) {
            $sql = "SELECT * FROM `rooms`";
            $result = $conn->query($sql);

            if ($result->num_rows == 0) {
                $roomData = [
                    ['name' => 'Hafencity', 'image' => 'hafencity.jpg'],
                    ['name' => 'Fischmarkt', 'image' => 'fischmarkt.jpeg'],
                    ['name' => 'Stadtpark', 'image' => 'stadtpark.jpeg'],
                    ['name' => 'Altona', 'image' => 'altona.jpeg'],
                ];

                $insertValues = [];
                foreach ($roomData as $room) {
                    $roomName = $room['name'];
                    $roomImage = $room['image'];
                    $insertValues[] = "('$roomName', '$roomImage')";
                }

                $valuesString = implode(',', $insertValues);

                $sql = "INSERT INTO rooms (name, image) VALUES $valuesString";
                $result = $conn->query($sql);
            }
        }

        //Adding array with rooms name to use it in template
        $sql = "SELECT name, image FROM rooms";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
        }
        $createTableReservations = "CREATE TABLE IF NOT EXISTS `reservations` (`id` int AUTO_INCREMENT,`desk_id` int,`reservation_time` date, `user_id` int, created_date date, PRIMARY KEY (id));";

        $result = $conn->query($createTableReservations);

        if ($result) {
            $sql = "SELECT r.id AS reservation_id, d.name AS desk_name, d.id AS desk_id, r.reservation_time, r.user_id, ro.name AS room_name
        FROM reservations r
        INNER JOIN desks d ON r.desk_id = d.id
        INNER JOIN rooms ro ON d.room_id = ro.id
        WHERE r.user_id = $userId
        ";
            $createTableDesks = "CREATE TABLE IF NOT EXISTS `desks` (`id` int AUTO_INCREMENT,`room_id` int, `name` varchar(255), `image` varchar(255),PRIMARY KEY (id), FOREIGN KEY (room_id) REFERENCES rooms(id));";
            $resultTableDesks = $conn->query($createTableDesks);
            if ($resultTableDesks) {
                $result = $conn->query($sql);
            }
        }
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

        return $this->render('custom_templates/bookingForm.html.twig', [
            'userId' => $userId,
            'usersName' => $usersName,
            'email' => $usersEmail,
            'rooms' => $rooms,
            'reservations' => $reservations
        ]);
    }
}
