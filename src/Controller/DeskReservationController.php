<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use mysqli;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DeskReservationController extends AbstractController
{
    public function deskReservation(Request $request, SessionInterface $session): Response
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
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

        $hiddenInputs = $request->request->all();

        $usersName = $hiddenInputs['usersName'];
        $deskId = $hiddenInputs['deskId'];
        $date = $hiddenInputs['date'];
        $roomName = $hiddenInputs['roomName'];

        $sql = "SELECT * FROM `users` WHERE name = '$usersName'";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $userId = $row['id'];
        }

        // Build the SQL INSERT statement
        $sql = "INSERT INTO reservations (desk_id, reservation_time, user_id) VALUES (?, ?, ?)";

        // Use a prepared statement to execute the query
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $deskId, $date, $userId);

        // Execute the query
        if ($stmt->execute()) {
            // Insert successful, you can redirect or display a success message
        } else {
            // Handle the case where the insert fails
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

        // Close the connection
        $sql = "SELECT name FROM rooms";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row['name'];
            }
        }

        $sql = "SELECT id, name, email FROM `users` WHERE id = '$userId'";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $userId = $row['id'];
            $usersEmail = $row['email'];
            $usersName = $row['name'];
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
                $_SESSION['user_id'] = $userId;
            } else {
                session_destroy();
                session_start();
                $_SESSION['user_id'] = $userId;
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

        $session->getFlashBag()->add('success', 'Your desk reservation was successfully');
        return $this->render('custom_templates/bookingForm.html.twig', [
            'userId' => $userId,
            'usersName' => $usersName,
            'email' => $usersEmail,
            'rooms' => $rooms,
            'reservations' => $reservations
        ]);

        $thisDate = $request->get('date');

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
                // Handle SQL query error for reservations
                echo "Error retrieving reservations count";
            }
        }

        $selectedDate = $thisDate;
        $sqlFreeDeskCount = "SELECT r.id AS room_id, r.name AS room_name, COUNT(d.id) AS free_desk_count
                                FROM rooms r
                                LEFT JOIN desks d ON r.id = d.room_id
                                LEFT JOIN reservations re ON d.id = re.desk_id AND re.reservation_time = '$selectedDate'
                                WHERE re.id IS NULL
                                GROUP BY r.id";
        $resultFreeDeskCount = $conn->query($sqlFreeDeskCount);

        // Fetch the results into an array
        $freeDeskCounts = $resultFreeDeskCount->fetch_all(MYSQLI_ASSOC);

        $today = date("Y-m-d");
        $sql = "DELETE FROM reservations WHERE reservation_time < '$today'";
        $conn->query($sql);

        $userId = $_SESSION['user_id'];
        $sql = "SELECT * FROM `users` WHERE id = '$userId'";
        $stmt = $conn->prepare($sql);
        $result = $conn->query($sql);

        if ($result) {
            $row = $result->fetch_assoc();
            $usersName = $row['name'];
            $usersEmail = $row['email'];
        } else {
            dd('moin1');
            return $this->redirectToRoute('login');
        }

        //Adding array with rooms name to use it in template
        $sql = "SELECT name FROM rooms";
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
            'pikedDate' => $thisDate,
            'noReservationCount' => $noReservationCount,
            'freeDeskCounts' => $freeDeskCounts,
        ]);

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

        $today = date("Y-m-d");
        $sql = "DELETE FROM reservations WHERE reservation_time < '$today'";
        $conn->query($sql);

        return $this->render('custom_templates/bookingForm.html.twig', [
            'userId' => $userId,
            'usersName' => $usersName,
            'email' => $usersEmail,
            'rooms' => $rooms,
        ]);
    }
}