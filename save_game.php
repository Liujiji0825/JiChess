<?php
session_start();

// 設定回傳格式為 JSON
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'jichess_db';
$user = 'root';
$pass = '';

// 嘗試連線至資料庫
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "資料庫連線失敗"]);
    exit;
}

// 接收前端資料
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $current_user = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';

    // 目前先預設玩家白，stockfish 黑
    $white_player = $current_user;
    $black_player = 'Stockfish 19';

    // 預設模式和時制
    $game_mode = 'ai_match';
    $time_control = 'none'; // 目前無計時功能

    // 從前端接收 PGN
    $pgn = $data['pgn'];
    $result = $data['result'];

    $stmt = $pdo->prepare("INSERT INTO games (white_player, black_player, game_mode, time_control, pgn, result) VALUES (:white_player, :black_player, :game_mode, :time_control, :pgn, :result)");

    if ($stmt->execute([
        ':white_player' => $white_player,
        ':black_player' => $black_player,
        ':game_mode' => $game_mode,
        ':time_control' => $time_control,
        ':pgn' => $pgn,
        ':result' => $result
    ])) {
        echo json_encode(["status" => "success", "message" => "棋譜儲存成功！"]);
    } else {
        echo json_encode(["status" => "error", "message" => "棋譜儲存失敗！"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "沒有接收到資料！"]);
}
?>