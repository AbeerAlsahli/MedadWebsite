<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_SESSION['userID'];
    $eventID = $_POST['eventID'] ?? null;

    if ($eventID) {
        try {
            // get all
            $event_sql = "SELECT e.*, 
                         (SELECT COUNT(*) FROM booking WHERE eventID = e.eventID) as current_bookings
                         FROM event e 
                         WHERE e.eventID = ?";
            $stmt = $pdo->prepare($event_sql);
            $stmt->execute([$eventID]);
            $event = $stmt->fetch();
            
            if (!$event) {
                echo 'not_found';
                exit;
            }

            // Check if event has expired
            $eventDate = new DateTime($event['date']);
            $currentDate = new DateTime();
            
            if ($eventDate < $currentDate) {
                echo 'expired';
                exit;
            }

            // Check if event is at capacity
            if ($event['current_bookings'] >= $event['capacity']) {
                echo 'full';
                exit;
            }

            // Check if user already booked this event
            $check_sql = "SELECT * FROM booking WHERE userID = ? AND eventID = ?";
            $stmt = $pdo->prepare($check_sql);
            $stmt->execute([$userID, $eventID]);
            
            if ($stmt->fetch()) {
                echo 'already';
            } else {
                // Insert booking
                $insert_sql = "INSERT INTO booking (userID, eventID, bookingDate, status) VALUES (?, ?, CURDATE(), 'booked')";
                $stmt = $pdo->prepare($insert_sql);
                $stmt->execute([$userID, $eventID]);
                echo 'success';
            }
        } catch (PDOException $e) {
            echo 'error';
        }
    }
}
?>