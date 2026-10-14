<?php

$host = 'sql312.infinityfree.com';
$dbname = 'if0_43169566_JiChess';
$user = 'if0_43169566';
$pass = 'Vicliu0825';

try {
    // 嘗試建立 PDO 連線
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);

    // 錯誤時會跳出警告
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 連線成功
    // echo "資料庫連線成功！";

} catch (PDOException $e) {
    // 連線失敗
    die("資料庫連線失敗: " . $e->getMessage());
}
?>