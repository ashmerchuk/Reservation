<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use mysqli;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BookingController extends AbstractController
{
    public function booking(Request $request, SessionInterface $session): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if ($request->getMethod() == 'GET') {

            $servername = "reservation-mysql";
            $username = "root";
            $password = "test_pass";

            // Create connection
            $conn = new mysqli($servername, $username, $password, 'reservation');

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
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

            $createTableReservations = "CREATE TABLE IF NOT EXISTS `reservations` (`id` int AUTO_INCREMENT,`desk_id` int,`reservation_time` date, `user_id` int, created_date date, PRIMARY KEY (id));";
            $resultTableReservations = $conn->query($createTableReservations);

            $createTableDesks = "CREATE TABLE IF NOT EXISTS `desks` (`id` int AUTO_INCREMENT,`room_id` int, `name` varchar(255), `image` varchar(255),PRIMARY KEY (id), FOREIGN KEY (room_id) REFERENCES rooms(id));";
            $resultTableDesks = $conn->query($createTableDesks);
            if($resultTableDesks){
                $sql = "SELECT * FROM `desks`";
                $result = $conn->query($sql);
                if ($result->num_rows == 0) {
                    $sql = "INSERT INTO desks (room_id, name, image) VALUES
                                (1, '1 monitor 24 inches, English keyboard, Mouse', '1monitor.jpeg'), (1, '2 monitors 24 inches, German keyboard, Mouse', '2monitors.jpeg'), (1, '1 monitor 24 inches, German keyboard, Mouse','1monitor.jpeg'), (1, '2 monitors 24 inches, English keyboard, Mouse', '2monitors.jpeg'),
                                (2, '1 monitor 24 inches, English keyboard, Mouse','1monitor.jpeg'), (2, '2 monitors 24 inches, English keyboard, Mouse', '2monitors.jpeg'), (2, '1 monitor 24 inches, German keyboard, Mouse', '1monitor.jpeg'), (2, '2 monitors 24 inches, German keyboard, Mouse', '2monitors.jpeg'),
                                (3, '1 monitor 24 inches, English keyboard, Mouse','1monitor.jpeg'), (3, '2 monitors 24 inches, English keyboard, Mouse','2monitors.jpeg'),
                                (4, '1 monitor 24 inches, English keyboard, Mouse','1monitor.jpeg'), (4, '2 monitors 24 inches, German keyboard, Mouse','2monitors.jpeg'), (4, '1 monitor 24 inches, German keyboard, Mouse', '1monitor.jpeg'), (4, '2 monitors 24 inches, German keyboard, Mouse', '2monitors.jpeg'),(4, '1 monitor 24 inches, German keyboard, Mouse', '1monitor.jpeg'), (4,'2 monitors 24 inches, German keyboard, Mouse', '2monitors.jpeg')";
                    $result = $conn->query($sql);
                }
            }

            $thisDate = $request->get('date');
            $userId = $_SESSION['user_id'];

            // Check if there's already a reservation for the user on the picked date
            $sql = "SELECT id FROM reservations WHERE user_id = ? AND reservation_time = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $userId, $thisDate);
            $stmt->execute();
            $result = $stmt->get_result();

            // If a reservation is found, redirect with an error message
            if ($result->num_rows > 0) {
                $session->getFlashBag()->add('error', 'You have already reservation on this day');
                return new RedirectResponse('/');
            }

            if ($thisDate !== null && $thisDate !== "") {
                // Step 1: Count the number of reservations for the given date
                $sqlReservations = "SELECT COUNT(*) AS reservation_count FROM reservations WHERE reservation_time = '$thisDate'";
                $resultReservations = $conn->query($sqlReservations);


                if ($resultReservations) {
                    $row = $resultReservations->fetch_assoc();
                    $reservationCount = $row['reservation_count'];
                    // Step 2: Count the number of desks with no reservations for the given date
                    $sqlNoReservations = "SELECT COUNT(*) AS no_reservation_count FROM desks WHERE id NOT IN (SELECT desk_id FROM reservations WHERE reservation_time = '$thisDate')";
                    $resultNoReservations = $conn->query($sqlNoReservations);

                    if ($resultNoReservations) {
                        $row = $resultNoReservations->fetch_assoc();
                        $noReservationCount = $row['no_reservation_count'];
                    }
                } else {
                    echo "Error retrieving reservations count";
                }
            }

            $selectedDate = $thisDate;
            $sqlFreeDeskCount = "SELECT r.id AS room_id, r.name AS room_name, r.image AS room_image, COUNT(d.id) AS free_desk_count
                                FROM rooms r
                                LEFT JOIN desks d ON r.id = d.room_id
                                LEFT JOIN reservations re ON d.id = re.desk_id AND re.reservation_time = '$selectedDate'
                                WHERE re.id IS NULL
                                GROUP BY r.id";
            $resultFreeDeskCount = $conn->query($sqlFreeDeskCount);

            // Fetch the results into an array
            $freeDeskCounts = $resultFreeDeskCount->fetch_all(MYSQLI_ASSOC);

            $userId = $_SESSION['user_id'];
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

            //Adding array with rooms name to use it in template
            $sql = "SELECT name, image FROM rooms";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $rooms[] = $row;
                }
            }
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

            return $this->render('custom_templates/bookingForm.html.twig', [
                'userId' => $userId,
                'usersName' => $usersName,
                'email' => $usersEmail,
                'rooms' => $rooms,
                'pikedDate' => $thisDate,
                'noReservationCount' => $noReservationCount,
                'freeDeskCounts' => $freeDeskCounts,
                'reservations' => $reservations
            ]);
        }

        echo $request->get('dateOfReservation');


        $userId = $request->get('userId');
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
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
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
        $sql = "SELECT * FROM rooms";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row['name'];
            }
        }

        return $this->render('custom_templates/bookingForm.html.twig', [
            'userId' => $userId,
            'usersName' => $usersName,
            'email' => $usersEmail,
            'rooms' => $rooms,
        ]);
    }
}