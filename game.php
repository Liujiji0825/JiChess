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
    <link rel="stylesheet" href="css/analysis.css">
    <link rel="stylesheet" href="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.css">
</head>
<body>
    <div class="container" style="max-width: 960px;">
        <h2 style="margin-top: 0;">⚔️ JiChess 對弈室</h2>
        <p style="text-align: center; color: #666;">目前玩家：<strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> vs 🤖 Stockfish 19</p>

        <!-- 左右雙欄工作區 -->
        <div class="analysis-workspace">
            
            <!-- 左側：棋盤與控制鈕 -->
            <div class="left-panel">
                <div id="myBoard" style="width: 480px; margin: 0 auto;"></div>
                
                <div class="controls" style="margin-top: 20px;">
                    <!-- 讓玩家查看舊棋譜時可以一鍵回到最新局勢 -->
                    <button onclick="goToLatestMove()" id="btn-latest" style="background-color: #28a745; color: white; display: none;">返回最新局勢</button>
                    
                    <!-- 原本的放棄按鈕，加上 id="btn-resign" -->
                    <a href="index.php" id="link-lobby"><button id="btn-resign" style="background-color: #6c757d; color: white;">放棄並回大廳</button></a>
                    
                    <!-- 新增的查看棋譜按鈕，預設隱藏 (display: none)，並導向戰績頁面 -->
                    <a href="history.php" id="btn-history" style="display: none; margin-left: 10px;"><button style="background-color: #007BFF; color: white;">查看棋譜</button></a>
                </div>
            </div>

            <!-- 右側：對戰紀錄 (棋譜欄位) -->
            <div class="right-panel">
                <div class="engine-box" style="text-align: center; font-size: 16px; padding: 15px;">
                    📜 實時對戰紀錄
                </div>
                <div class="moves-box" id="move-list">
                    <!-- JavaScript 會在這裡動態渲染棋譜 -->
                </div>
            </div>

        </div>
    </div>

    <!--引入外部 JS 庫 -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js"></script>

    <script src="js/game.js"></script>
</body>
</html>