<?php
session_start();

if (!isset($_SESSION['username'])) {
    die("<h3>請先登入才能查看戰績！</h3><a href='index.php'>回到首頁</a>");
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

// SQL 查詢，選取該使用者的所有棋譜
$sql = "SELECT * FROM games
        WHERE white_player = :username OR black_player = :username
        ORDER BY played_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':username' => $current_user]);
$games = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>我的戰績</title>
    <link rel="stylesheet" href="css/history.css">
</head>
<body>
    <div class="container">
        <h2>🏆 <?php echo htmlspecialchars($current_user); ?> 的對戰紀錄</h2>

        <?php if (count($games) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>對戰時間</th>
                        <th>白方</th>
                        <th>黑方</th>
                        <th>勝負結果</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($games as $game): ?>
                        <?php
                            // 判斷玩家是贏是輸
                            $isWhite = ($game['white_player'] === $current_user);
                            $resultText = '平局';
                            $resultClass = "draw";

                            if ($game['result'] === '1-0') {
                                $resultText = $isWhite ? "勝利" : "戰敗";
                                $resultClass = $isWhite ? "win" : "lose";
                            } elseif ($game['result'] === '0-1') {
                                $resultText = $isWhite ? "戰敗" : "勝利";
                                $resultClass = $isWhite ? "lose" : "win";
                            }
                        ?>
                        <tr>
                            <td><?php echo $game['played_at']; ?></td>
                            <td><?php echo htmlspecialchars($game['white_player']); ?></td>
                            <td><?php echo htmlspecialchars($game['black_player']); ?></td>
                            <td class="<?php echo $resultClass; ?>"><?php echo $resultText; ?></td>
                            <td>
                                <a href="analysis.php?id=<?php echo $game['id']; ?>" class="btn-play">查看棋譜</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666;">目前沒有任何對戰紀錄！</p>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="color: #007BFF;">回到大廳</a>
        </div>
    </div>
</body>
</html>