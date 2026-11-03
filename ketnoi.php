<?php
$host = "localhost";
$user = "root";
$pass = "12345678"; 
$dbname = "quanlysuachua";

// Thêm dấu chữa lỗi truyền tham số rỗng cho password
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>