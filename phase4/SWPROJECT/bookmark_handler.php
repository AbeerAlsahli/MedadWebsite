<?php
session_start();
require_once 'config.php';

$userID = $_SESSION['userID'];
$itemID = $_POST['itemID'];
$itemType = $_POST['itemType'];

try {
    // Check if already bookmarked
    $check_sql = "SELECT * FROM bookmark WHERE userID = ? AND itemType = ? AND itemID = ?";
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute([$userID, $itemType, $itemID]);
    
    if ($stmt->fetch()) {
        // Remove bookmark
        $delete_sql = "DELETE FROM bookmark WHERE userID = ? AND itemType = ? AND itemID = ?";
        $stmt = $pdo->prepare($delete_sql);
        $stmt->execute([$userID, $itemType, $itemID]);
        echo 'removed';
    } else {
        // Add bookmark
        $insert_sql = "INSERT INTO bookmark (userID, itemType, itemID, dateAdded) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute([$userID, $itemType, $itemID]);
        echo 'added';
    }
} catch (Exception $e) {
    echo 'error';
}
?>