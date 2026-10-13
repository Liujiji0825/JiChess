<?php
session_start();

// 檢查是否已登入
if (!isset($_SESSION['username'])) {
    die("<h3>請先登入才能使用好友系統！</h3><a href='index.php'>回到首頁</a>");
}

$current_user = $_SESSION['username'];
$host = 'localhost';
$dbname = 'jichess_db';
$user = 'root';
$pass = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("資料庫連線失敗: " . $e->getMessage());
}

$message = "";

// ==========================================
// 處理表單動作 (發送邀請、接受、拒絕)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. 發送邀請
    if ($action === 'send_request' && !empty($_POST['target_user'])) {
        $target = trim($_POST['target_user']);
        
        if ($target === $current_user) {
            $message = "<p class='msg-error'>你不能加自己為好友！</p>";
        } else {
            // 檢查對方帳號是否存在於 users 表
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :target");
            $stmt->execute([':target' => $target]);
            if ($stmt->fetch()) {
                // 檢查是否已經是好友或已經發送過邀請 (不管誰發給誰)
                $stmt = $pdo->prepare("SELECT * FROM friends WHERE (user1 = :u1 AND user2 = :u2) OR (user1 = :u3 AND user2 = :u4)");
                $stmt->execute([':u1' => $current_user, ':u2' => $target, ':u3' => $target, ':u4' => $current_user]);
                
                if ($stmt->fetch()) {
                    $message = "<p class='msg-error'>你們已經是好友，或已經有待處理的邀請了！</p>";
                } else {
                    // 新增邀請
                    $stmt = $pdo->prepare("INSERT INTO friends (user1, user2, status) VALUES (:u1, :u2, 'pending')");
                    $stmt->execute([':u1' => $current_user, ':u2' => $target]);
                    $message = "<p class='msg-success'>已成功發送交友邀請給 <strong>$target</strong>！</p>";
                }
            } else {
                $message = "<p class='msg-error'>找不到玩家「{$target}」，請確認帳號是否正確。</p>";
            }
        }
    } 
    // 2. 接受邀請
    elseif ($action === 'accept_request' && !empty($_POST['request_id'])) {
        $req_id = $_POST['request_id'];
        $stmt = $pdo->prepare("UPDATE friends SET status = 'accepted' WHERE id = :id AND user2 = :me");
        $stmt->execute([':id' => $req_id, ':me' => $current_user]);
        $message = "<p class='msg-success'>已成功接受好友邀請！</p>";
    } 
    // 3. 拒絕邀請
    elseif ($action === 'decline_request' && !empty($_POST['request_id'])) {
        $req_id = $_POST['request_id'];
        $stmt = $pdo->prepare("DELETE FROM friends WHERE id = :id AND user2 = :me");
        $stmt->execute([':id' => $req_id, ':me' => $current_user]);
        $message = "<p class='msg-success'>已拒絕該邀請。</p>";
    }
}

// ==========================================
// 讀取資料：好友列表與收到的邀請
// ==========================================
// 獲取收到的邀請 (user2 是我，且狀態是 pending)
$stmt = $pdo->prepare("SELECT * FROM friends WHERE user2 = :me AND status = 'pending'");
$stmt->execute([':me' => $current_user]);
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 獲取我的好友列表 (user1 或 user2 是我，且狀態是 accepted)
$stmt = $pdo->prepare("SELECT * FROM friends WHERE (user1 = :me OR user2 = :me) AND status = 'accepted'");
$stmt->execute([':me' => $current_user]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>好友系統 - JiChess</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        h2 { text-align: center; color: #333; margin-top: 0; }
        h3 { color: #555; border-bottom: 2px solid #eee; padding-bottom: 8px; margin-top: 30px; }
        .msg-error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; text-align: center; }
        .msg-success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; text-align: center; }
        
        .search-box { display: flex; gap: 10px; margin-top: 15px; }
        input[type="text"] { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px; }
        button { padding: 10px 15px; background-color: #007BFF; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        button.btn-accept { background-color: #28a745; }
        button.btn-accept:hover { background-color: #218838; }
        button.btn-decline { background-color: #dc3545; }
        button.btn-decline:hover { background-color: #c82333; }
        
        ul { list-style: none; padding: 0; }
        li { background: #f8f9fa; margin-bottom: 10px; padding: 15px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #eee; }
        .friend-name { font-size: 18px; font-weight: bold; color: #333; }
        .actions { display: flex; gap: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h2>👥 好友系統</h2>
    <p style="text-align: center; color: #666;">目前登入玩家：<strong><?php echo htmlspecialchars($current_user); ?></strong></p>
    
    <?php if ($message) echo $message; ?>

    <!-- 1. 新增好友 -->
    <h3>🔍 新增好友</h3>
    <form method="POST" action="friends.php" class="search-box">
        <input type="hidden" name="action" value="send_request">
        <input type="text" name="target_user" placeholder="輸入玩家帳號..." required>
        <button type="submit">發送邀請</button>
    </form>

    <!-- 2. 收到的好友邀請 -->
    <h3>📩 收到的邀請</h3>
    <?php if (count($pending_requests) > 0): ?>
        <ul>
            <?php foreach ($pending_requests as $req): ?>
                <li>
                    <span class="friend-name"><?php echo htmlspecialchars($req['user1']); ?></span>
                    <div class="actions">
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="accept_request">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <button type="submit" class="btn-accept">接受</button>
                        </form>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="decline_request">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <button type="submit" class="btn-decline">拒絕</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p style="color: #888; text-align: center;">目前沒有新的交友邀請。</p>
    <?php endif; ?>

    <!-- 3. 我的好友列表 -->
    <h3>🧑‍🤝‍🧑 我的好友列表</h3>
    <?php if (count($friends) > 0): ?>
        <ul>
            <?php foreach ($friends as $f): 
                // 判斷對方是 user1 還是 user2
                $friend_name = ($f['user1'] === $current_user) ? $f['user2'] : $f['user1'];
            ?>
                <li>
                    <span class="friend-name"><?php echo htmlspecialchars($friend_name); ?></span>
                    <!-- 未來可以加功能，例如查看戰績或發起對戰 -->
                    <button style="background-color: #6c757d;">查看戰績</button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p style="color: #888; text-align: center;">你目前還沒有加入任何好友，快去搜尋朋友吧！</p>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 30px;">
        <a href="index.php" style="color: #007BFF; text-decoration: none; font-size: 16px;">&larr; 回到大廳</a>
    </div>
</div>

</body>
</html>