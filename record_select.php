<!-- record_select.php -->
<?php
session_start();
if(!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
require 'db.php';

try {
    $stmt = $pdo->prepare("SELECT drug_id, medication_name FROM medication_schedule WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服用記録選択</title>
</head>
<body>
    <h2>服用記録選択画面</h2>
    <p>服用記録をつける薬を選択してください</p>

    <?php if (!empty($medications)) : ?>
        <ul>
            <?php foreach ($medications as $medication) : ?>
                <li>
                    <form action="record.php" method="post">
                        <input type="hidden" name="drug_id" value="<?php echo htmlspecialchars($medication['drug_id']); ?>">
                        <input type="hidden" name="medication_name" value="<?php echo htmlspecialchars($medication['medication_name']); ?>">
                        <button type="submit"><?php echo htmlspecialchars($medication['medication_name']); ?></button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p>現在、服用中の薬はありません</p>
    <?php endif; ?>
    <form action="home.php" method="get">
        <button type="submit">ホーム画面へ戻る</button>
    </form>
</body>
</html>
