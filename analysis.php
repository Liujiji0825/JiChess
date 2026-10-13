<?php
session_start();

$host = 'localhost';
$dbname = 'jichess_db';
$user = 'root';
$pass = '';

$pgn_to_load = "";
$show_menu = true;

// 判斷網址有沒有帶 id
if (isset($_GET['id'])) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user,$pass);
        $stmt =$pdo->prepare("SELECT pgn FROM games WHERE id = :id");
        $stmt->execute([':id' =>$_GET['id']]);
        $game =$stmt->fetch(PDO::FETCH_ASSOC);

        if ($game) {
            $pgn_to_load = $game['pgn'];
            $show_menu = false;
        }
    } catch (PDOException $e) {
        die("資料庫連線失敗");
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>分析面板 (Analysis)</title>
    <link rel="stylesheet" href="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.css">
    <link rel="stylesheet" href="css/analysis.css">
</head>
<body>
    <div class="container">
        <h2>♟️ 局勢分析 (Analysis Board)</h2>

        <?php if ($show_menu): ?>
        <div id="setup-menu" class="menu-box">
            <h3>請選擇分析模式：</h3>
            <button onclick="startNewAnalysis()">1. 新分析 (從頭開始)</button>
            <button onclick="openBoardEditor()">2. 棋盤編輯器</button>
            <button onclick="showImportBox()">3. 匯入 FEN / PGN</button>

            <div id="import-box" style="display:none; margin-top: 15px;">
                <textarea id="import-text" rows="5" style="width: 100%;" placeholder="貼上您的 PGN 或 FEN..."></textarea>
                <button onclick="importData()" style="background-color: #28a745;">確認匯入</button>
            </div>    
        </div>
        <?php endif; ?>

        <div class="analysis-workspace" id="board-area" style="display: <?php echo $show_menu ? 'none' : 'flex'; ?>;">

            <div class="left-panel">
                <div id="myBoard" style="width: 480px;"></div>
                <div class="controls" id="replay-controls">
                    <button onclick="goToStart()">|&lt; 最前</button>
                    <button onclick="prevMove()">&lt; 上一步</button>
                    <button onclick="nextMove()">下一步 &gt;</button>
                    <button onclick="goToEnd()">最後 &gt;|</button>
                    <button onclick="backToMenu()" class="btn-secondary">重新選擇模式</button>
                </div>
            </div>

            <div class="right-panel">
                <div class="engine-box">
                    <div class="engine-header">
                        <span class="engine-title">🤖 Stockfish 19</span>
                        <span id="eval-score" class="eval-badge">0.00</span>
                    </div>
                    <div id="engine-line" class="engine-line">正在載入引擎...</div>
                </div>

                <div class="moves-box" id="move-list">
                    <!-- JS 會在這裡動態渲染棋譜 -->
                </div>
            </div>

        </div>

        <div class="footer-links">
            <a href="index.php">回到大廳</a> | 
            <a href="history.php">我的戰績</a>
        </div>

    </div>

    <!-- 引入 jQuery, Chessboard.js, Chess.js -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://unpkg.com/@chrisoakman/chessboardjs@1.0.0/dist/chessboard-1.0.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js"></script>

    <!-- 把 PHP 抓到的 PGN 傳給 JavaScript -->
    <script>
        const loadedPgn = `<?php echo $pgn_to_load; ?>`;
    </script>

    <!-- 引入分析器的 JS 邏輯 -->
    <script src="js/analysis.js"></script>
</body>
</html>