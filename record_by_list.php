<?php
//セッションの開始
session_start();

//デバッグモードをオン
$debug_mode = true;
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    if ($debug_mode) {
        echo "<p style='color:red;'>セッションが存在しません</p>";
        echo "<p>デバッグ情報</p>";
        echo "<pre>SESSION: " . print_r($_SESSION, true) . " </pre>";
    }
    exit;
}

//セッションから値を取得
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

//データベースに接続するコードが入ったファイルをインクルード
require 'db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM medication_schedule WHERE user_id = :user_id
    ORDER BY medication_name, administration_time ASC");

    $stmt->bindParam(":user_id", $user_id);

    $stmt->execute();

    $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // echo "<pre>medications: " . print_r($medications, true) . " </pre>";

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}


?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服用記録</title>
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <form action="logout.php" method="post">
        <button class="delete-button" type="submit">ログアウト</button>
    </form>

    <form action="deleteMedicine.php" method="get">
        <button class="delete-button" type="submit">薬を削除</button>
    </form>

    <div class="container">

        <div class="header">
            <h2>服用記録画面</h2>
            <p>記録したい薬を選んでください</p>
        </div>

        <div class="button-container">
            <form action="home.php" method="get">
                <button class="home-button" type="submit">ホーム画面へ</button>
            </form>

            <form action="list.php" method="get">
                <button type="submit">薬を新規登録</button>
            </form>

            <form action="add.php" method="get">
                <button type="submit">薬の新規登録</button>
            </form>
        </div>

        <div class="divider"></div>

        <div class="medication-list">
            <?php if (isset($medications)): ?>
                <ul>
                    <?php foreach ($medications as $medication): ?>
                        <li>
                            <?php echo htmlspecialchars($medication['drug_id']); ?>
                            <form action="record_by_select_date.php" method="post">
                                <!-- hiddenの内容を送信する drug_idはユーザーが見れなくてもよいからhidden -->
                                 <!-- record_by_select_date.phpで受け取るときは$_POST['name']とすればよい そのためにname="drug_id"と設定している -->
                                <input type="hidden" name="drug_id" value="<?php echo htmlspecialchars($medication['drug_id']); ?>">
                                <button type="submit" class="button-container">
                                    <!-- ボタンの名前は薬名 -->
                                    <?php echo htmlspecialchars($medication['medication_name']); ?>
                                </button>
                            </form>
                            <p>⌚<?php echo htmlspecialchars($medication['administration_time']); ?>
                                💊<?php echo htmlspecialchars($medication['dose_count']); ?>
                                📅<?php echo htmlspecialchars($medication['start_date']); ?> ~
                                <?php echo htmlspecialchars($medication['end_date']); ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>