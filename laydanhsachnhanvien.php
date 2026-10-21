<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new mysqli("localhost", "root", "", "qlnv_db");
$result = $conn->query("SELECT * FROM nhan_vien ORDER BY id DESC");

$list = [];
while ($row = $result->fetch_assoc()) {
    $list[] = $row;
}

echo json_encode($list);
$conn->close();
?>