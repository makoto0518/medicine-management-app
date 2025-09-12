<!-- select_time.php -->
<!-- 時間を選ぶ -->
<?php
//セッションの開始
session_start();
$debug_mode = true;

//以下、デバッグ
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    if ($debug_mode) {
        echo "<p style='color:red;'>セッションが存在しません。</p>";
        echo "<p>デバッグ情報:</p>";
        echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";
    }
}

if (!isset($_GET['medication_name'])) {
    if ($debug_mode) {
        echo "<p style='color: red;'>薬名が指定されていません。</p>";
        echo "<p>デバッグ情報:</p>";
        echo "<pre>GET: " . print_r($_SESSION, true) . "</pre>";
    }
}

//データを得る
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$medication_name = $_GET['medication_name'];

require 'db.php';

try {
    //１つの薬から複数の服用時間を取り出す
    $stmt = $pdo->prepare("SELECT administration_time FROM medication_schedule WHERE user_id = :user_id AND medication_name = :medication_name ORDER BY administration_time");

    //データのバインド
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':medication_name', $medication_name);

    //実行
    $stmt->execute();

    //全ての服用時間を得るためALL
    $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("データベースエラー:" . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>薬の服用時間リスト</title>
</head>
<body>
    <h2>薬の服用時間リスト</h2>
    <p>ここで選ばれた薬の服用時間を選択</p>
    <!-- データが存在する場合 -->
    <?php if (!empty($medications)) : ?>
        <?php echo $medication_name ."の服用時間は";?>
        <ul>
            <?php foreach ($medications as $medication) : ?>
                <!-- 配列１つ１つごとに処理をする -->
                <li>
                    <!-- 以下で送信されるものはselect_date.phpに送る -->
                    <form action="select_date.php" method="get">
                        <!-- 遷移先に薬名と服用時間帯を渡す -->
                        <input type="hidden" name="medication_name" value="<?php echo htmlspecialchars($medication_name)?>">
                        <input type="hidden" name="administration_time" value="<?php echo htmlspecialchars($medication['administration_time']); ?>">
                        <button type="submit"><?php echo htmlspecialchars($medication['administration_time']); ?></button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <!-- データが存在しない場合 -->
    <?php else : ?>
        <p>服用時間が設定されていません</p>
    <?php endif; ?>

    <form action="home.php" method="get">
        <button type="submit">ホーム画面へ</button>
    </form>

</body>
</html>
