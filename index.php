<?php
session_start();

// 判斷是否登入
$is_logged_in = isset($_SESSION['username']);
$username = $is_logged_in ? $_SESSION['username'] : '訪客';

// 建立一個函數來處理按鈕的點擊行為
function getAction($target_url, $is_logged_in) {
    if ($is_logged_in) {
        // 已登入：正常前往目標網頁
        return 'href="' . $target_url . '"';
    } else {
        // 未登入：取消預設跳轉，彈出提示後導向登入頁
        return 'href="#" onclick="alert(\'請先登入才能使用此功能喔！\'); window.location.href=\'login.php\'; return false;"';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>JiChess - 遊戲大廳</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f9; text-align: center; padding-top: 50px; margin: 0; }
        .dashboard { position: relative; max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; margin-top: 0; }
        .welcome { color: #666; margin-bottom: 30px; font-size: 18px; }
        .menu-grid { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        
        .menu-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 200px;
            height: 150px;
            text-decoration: none;
            color: white;
            border-radius: 10px;
            font-size: 20px;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .menu-btn:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
        .menu-btn span { font-size: 40px; margin-bottom: 10px; }

        .btn-play { background: linear-gradient(135deg, #4CAF50, #2E7D32); }
        .btn-history { background: linear-gradient(135deg, #007BFF, #0056b3); }
        .btn-analysis { background: linear-gradient(135deg, #6c757d, #495057); }
        .btn-friends { background: linear-gradient(135deg, #FF9800, #F57C00); }

        /* 右上角狀態區塊 (登入 / 登出) */
        .auth-bar { position: absolute; top: 20px; right: 25px; }
        .btn-auth { text-decoration: none; padding: 8px 16px; border-radius: 5px; font-size: 14px; font-weight: bold; color: white; background-color: #007BFF; transition: 0.2s; }
        .btn-auth:hover { background-color: #0056b3; }
        .btn-logout { background-color: #dc3545; }
        .btn-logout:hover { background-color: #c82333; }
    </style>
</head>
<body>

<div class="dashboard">
    <!-- 右上角登入狀態切換 -->
    <div class="auth-bar">
        <?php if ($is_logged_in): ?>
            <a href="logout.php" class="btn-auth btn-logout">登出</a>
        <?php else: ?>
            <a href="login.php" class="btn-auth">登入 / 註冊</a>
        <?php endif; ?>
    </div>

    <h1>♟️ 歡迎來到 JiChess</h1>
    <div class="welcome">你好，<strong><?php echo htmlspecialchars($username); ?></strong>！準備好下一盤棋了嗎？</div>

    <div class="menu-grid">
        <!-- 透過 PHP 動態決定按鈕行為 -->
        <a <?php echo getAction('game.php', $is_logged_in); ?> class="menu-btn btn-play">
            <span>⚔️</span>與電腦對戰
        </a>
        <a <?php echo getAction('history.php', $is_logged_in); ?> class="menu-btn btn-history">
            <span>📜</span>歷史戰績
        </a>
        <a <?php echo getAction('analysis.php', $is_logged_in); ?> class="menu-btn btn-analysis">
            <span>🔍</span>分析面板
        </a>
        <a <?php echo getAction('friends.php', $is_logged_in); ?> class="menu-btn btn-friends">
            <span>👥</span>好友系統
        </a>
    </div>
</div>

</body>
</html>