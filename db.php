<?php

$host = 'localhost';
$dbname = 'jichess_db';
$username = 'root';
$password = '';

try {
    // 嘗試建立 PDO 連線
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // 錯誤時會跳出警告
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 連線成功
    // echo "資料庫連線成功！";

} catch (PDOException $e) {
    // 連線失敗
    die("資料庫連線失敗: " . $e->getMessage());
}
?>