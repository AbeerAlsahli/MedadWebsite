<?php
// Medad Database Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
$host = 'localhost';
$dbname = 'medad4';  
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optional: Set timezone for consistency
    $pdo->exec("SET time_zone = '+03:00';"); // Saudi Arabia time
    
} catch(PDOException $e) {
    // error handling 
    error_log("Database connection failed: " . $e->getMessage());
    die("عذراً، حدث خطأ في الاتصال بقاعدة البيانات. الرجاء المحاولة لاحقاً.");
}
?>