<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use mysqli;
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
            $createTableReservations = "CREATE TABLE IF NOT EXISTS `reservations` (`id` int AUTO_INCREMENT,`desk_id` int,`reservation_time` date, `user_id` int, created_date date, PRIMARY KEY (id));";
            $resultTableReservations = $conn->query($createTableReservations);

            $createTableDesks = "CREATE TABLE IF NOT EXISTS `desks` (`id` int AUTO_INCREMENT,`room_id` int, `name` varchar(255), `description` varchar(255),  PRIMARY KEY (id), FOREIGN KEY (room_id) REFERENCES rooms(id));";
            $resultTableDesks = $conn->query($createTableDesks);
            if($resultTableDesks){
                $sql = "SELECT * FROM `desks`";
                $result = $conn->query($sql);
                if ($result->num_rows == 0) {
                    $sql = "INSERT INTO desks (room_id) VALUES
                                (1), (1), (1), (1), (2), (2), (2), (2)";
                    $result = $conn->query($sql);
                }
            }

            $thisDate = $request->get('date');

            if ($thisDate !== null && $thisDate !== "") {
                // Step 1: Count the number of reservations for the given date
                $sqlReservations = "SELECT COUNT(*) AS reservation_count FROM reservations WHERE reservation_time = '$thisDate'";
                $resultReservations = $conn->query($sqlReservations);
//                dd($resultReservations);


                if ($resultReservations) {
                    $row = $resultReservations->fetch_assoc();
                    $reservationCount = $row['reservation_count'];
//                    dd($reservationCount);
                    // Step 2: Count the number of desks with no reservations for the given date
                    $sqlNoReservations = "SELECT COUNT(*) AS no_reservation_count FROM desks WHERE id NOT IN (SELECT desk_id FROM reservations WHERE reservation_time = '$thisDate')";
                    $resultNoReservations = $conn->query($sqlNoReservations);

                    if ($resultNoReservations) {
                        $row = $resultNoReservations->fetch_assoc();
                        $noReservationCount = $row['no_reservation_count'];

                        // Now you have the counts for reservations and no-reserved desks
//                        dd($noReservationCount);
//                        echo "Reservations on $thisDate: $reservationCount<br>";
//                        echo "No-reserved desks on $thisDate: $noReservationCount";
                    }
//                  else {
//                        // Handle SQL query error for no-reserved desks
//                        echo "Error retrieving no-reserved desks count";
//                    }
                } else {
                    // Handle SQL query error for reservations
                    echo "Error retrieving reservations count";
                }
            }

            $usersEmail = $request->get('signInEmail');
//            if (session_status() !== PHP_SESSION_ACTIVE) {
//                session_start();
////                $_SESSION['user_id'] = $userId;
//            } else {
//                return $this->redirectToRoute('login');
//            }

//            dd($_SESSION['user_id']);
            $userId = $_SESSION['user_id'];
            $sql = "SELECT * FROM `users` WHERE id = '$userId'";
//            $sql = "SELECT * FROM `users` WHERE email = '$usersEmail'";
            $stmt = $conn->prepare($sql);
            $result = $conn->query($sql);

            if ($result) {
                $row = $result->fetch_assoc();
//            dd($row);
                $usersName = $row['name'];
                $usersEmail = $row['email'];
//                if (session_status() !== PHP_SESSION_ACTIVE) {
//                    session_start();
//                    $_SESSION['user_id'] = $userId;
//                }
            } else {
                return $this->redirectToRoute('login');
            }

            $createTableRooms = "CREATE TABLE IF NOT EXISTS `rooms` (`id` int AUTO_INCREMENT,`name` varchar(255),`image` varchar(255), PRIMARY KEY (id));";
            $result = $conn->query($createTableRooms);
//                    dd($result);
            if ($result) {
                $sql = "SELECT * FROM `rooms`";
                $result = $conn->query($sql);
                if ($result->num_rows == 0) {
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

            return $this->render('custom_templates/bookingForm.html.twig', [
                'userId' => $userId,
                'usersName' => $usersName,
                'email' => $usersEmail,
                'rooms' => $rooms,
                'noReservationCount' => $noReservationCount,
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
//            dd($row);
            $usersName = $row['name'];
            $usersEmail = $row['email'];
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
                $_SESSION['user_id'] = $userId;
            }
        } else {
            return $this->redirectToRoute('login');
        }

        $createtable = "CREATE TABLE IF NOT EXISTS `rooms` (`id` int AUTO_INCREMENT,`name` varchar(255),`image` varchar(255), PRIMARY KEY (id));";
        $result = $conn->query($createtable);
//                    dd($result);
        if ($result) {
            $sql = "SELECT * FROM `rooms`";
            $result = $conn->query($sql);
            if ($result->num_rows == 0) {
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

        return $this->render('custom_templates/bookingForm.html.twig', [
            'userId' => $userId,
            'usersName' => $usersName,
            'email' => $usersEmail,
            'rooms' => $rooms,
        ]);
    }
}