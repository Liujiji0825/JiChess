<?php
session_start();
// 保護機制
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>JiChess - 對弈室</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.css">
</head>
<body>
    <div class="register-container" style="width: 500px;">
        <h1>JiChess 對弈室</h1>
        <p>目前玩家：<strong><?php echo $_SESSION['username']; ?></strong></p>

        <!-- 裝棋盤的空盒子 -->
        <div id="myBoard"></div>

        <a href="index.php"><button style="background-color: #6c757d; margin-top: 20px;">回大廳</button></a>
    </div>

    <!--引入外部 JS 庫 -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js"></script>

    <script src="js/game.js"></script>
</body>
</html>