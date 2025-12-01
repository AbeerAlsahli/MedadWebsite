<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_SESSION['userID'];
    $clubID = $_POST['clubID'] ?? null;

    if ($clubID) {
        try {
            // Check if already a member
            $check_sql = "SELECT * FROM club_memberships WHERE userID = ? AND clubID = ?";
            $stmt = $pdo->prepare($check_sql);
            $stmt->execute([$userID, $clubID]);
            
            if ($stmt->fetch()) {
                echo 'already';
            } else {
                // Insert club membership
                $insert_sql = "INSERT INTO club_memberships (userID, clubID) VALUES (?, ?)";
                $stmt = $pdo->prepare($insert_sql);
                $stmt->execute([$userID, $clubID]);
                echo 'success';
            }
        } catch (PDOException $e) {
            echo 'error';
        }
    }
}
?>