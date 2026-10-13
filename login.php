<?php
// 開啟 Session 功能 (必須在檔案最上方)
session_start();
require_once 'db.php';

$message = '';

// 已經登入過的直接跳回首頁
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 處理登入表單送出的資料
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = "帳號與密碼不能為空！";
    } else {
        // 從資料庫尋找這個帳號
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 比對密碼
        if ($user && password_verify($password, $user['password_hash'])) {
            // 密碼正確
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rating'] = $user['rating'];

            // 跳轉回首頁
            header("Location: index.php");
            exit();
        } else {
            $message = "帳號或密碼錯誤！";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>JiChess - 玩家登入</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="register-container">
        <h1>玩家登入</h1>

        <?php if ($message != ''): ?>
            <p class="message" style="color: red;"><?php echo $message; ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div>
                <label>帳號：</label><br>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>密碼：</label><br>
                <input type="password" name="password" required>
            </div>
            <button type="submit">登入</button>
        </form>
        <p style="margin-top: 15px;">還沒有帳號？ <a href="register.php">點我註冊</a></p>
    </div>
</body>
</html>