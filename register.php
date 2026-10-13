<?php
// 引入資料庫
require_once 'db.php';

$message = ''; // 儲存給玩家看的訊息

// 檢查玩家是否按下「註冊」按鈕送出資料
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 拿到玩家輸入的帳密，去掉空白
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 檢查有無漏填
    if (empty($username) || empty($password)) {
        $message = "帳號與密碼不能為空！";
    } else {
        // 密碼加密
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // 把帳號和加密密碼放入資料庫
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (:username, :password)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashed_password);

            $stmt->execute();
            // 註冊成功：用 JavaScript 彈出提示並跳轉到 login.php
            echo "<script>
                    alert('註冊成功！歡迎加入 JiChess，請登入您的帳號。');
                    window.location.href = 'login.php';
                  </script>";
            exit(); // 終止後續的 PHP 執行，直接進行跳轉

        } catch (PDOException $e) {
            // 帳號重複了
            if ($e->getCode() == 23000) {
                $message = "這個帳號已經有人使用了，請換一個！";
            } else {
                $message = "註冊失敗：" . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>JiChess - 註冊帳號</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="register-container">
        <h1>註冊 JiChess 玩家帳號</h1>

        <?php if ($message != ''): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>

        <!-- HTML 註冊表單 -->
        <form method="POST" action="register.php">
            <div>
                <label>帳號：</label><br>
                <input type="text" name="username" required>
            </div>
            <div>
                <label>密碼：</label><br>
                <input type="password" name="password" required>
            </div>
            <button type="submit">點我註冊</button>

            <div style="text-align: center; margin-top: 20px;">
                <span style="color: #666;">已經有帳號了嗎？</span>
                <a href="login.php" style="color: #007BFF; text-decoration: none; font-weight: bold;">回到登入頁面</a>
            </div>
        </form>
    </div>
        
</body>
</html>