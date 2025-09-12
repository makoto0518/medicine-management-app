<?php
//セッションの開始
session_start();

//record.phpで服用日記録中にほかの画面に遷移したときに、SESSIONに残っていたstart_date、end_dateの情報を消す
unset($_SESSION['start_date']);
unset($_SESSION['end_date']);


//デバッグモードをオン
$debug_mode = true;

//セッションに値がないとき
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    if ($debug_mode) {
        echo "<p style='color:red;'>セッションが存在しません</p>";
        echo "<p>デバッグ情報</p>";
        echo "<pre>SESSION: " . print_r($_SESSION, true);
        //具体的な変数の値を示す trueのとき返り値が文字列になる
    }
}

//セッションから値を得る
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

//データベースに接続するコードが入ったファイルを得る
require 'db.php';

$system_message = "";


//もしpostメソッドでdrug_idが送られてきた場合
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drug_id'])) {
    try {
        $drug_id = $_POST['drug_id'];
        $stmt = $pdo->prepare("SELECT medication_name FROM medication_schedule WHERE drug_id = :drug_id");
        $stmt->bindParam(":drug_id", $drug_id);
        $stmt->execute();
        $name = $stmt->fetch(PDO::FETCH_ASSOC);


        //その薬を削除する
        $delete_medicine = $pdo->prepare("DELETE FROM medication_schedule WHERE drug_id = :drug_id");
        $delete_medicine->bindParam(':drug_id', $drug_id);
        $delete_medicine->execute();

        //その薬の過去の記録も消す
        $delete_record = $pdo->prepare("DELETE FROM medication_record WHERE drug_id = :drug_id");
        $delete_record->bindParam(":drug_id", $drug_id);
        $delete_record->execute();

        $system_message = $name['medication_name'] . "が削除されました";

        //まだ消されていない薬を表示
        try {
            $stmt = $pdo->prepare("SELECT * FROM medication_schedule WHERE user_id = :user_id ORDER BY medication_name, administration_time, start_date");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("薬表示時、データベースエラー:" . $e->getMessage());
        }
    } catch (PDOException $e) {
        die("薬削除時、データベースエラー:" . $e->getMessage());
    }

    
} else {
    //薬を選ぶ段階
    try {
        $stmt = $pdo->prepare("SELECT * FROM medication_schedule WHERE user_id = :user_id ORDER BY medication_name, administration_time");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("薬表示時、データベースエラー:" . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>薬削除画面</title>
    <link rel="stylesheet" href="style.css" type="text/css">
</head>

<body>
    <form action="logout.php" method="post">
        <button class="logout-button" type="submit">ログアウト</button>
    </form>

    <div class="container">
        <div class="header">
            <h2>薬削除画面</h2>
            <?php echo htmlspecialchars($system_message); ?>
        </div>

        <div class="button-container">
            <form action="home.php" method="get">
                <button class="home-button" type="submit">ホーム画面へ</button>
            </form>

            <form action="list.php" method="get">
                <button type="submit">薬リスト</button>
            </form>

            <form action="record.php" method="get">
                <button type="submit">服用記録</button>
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
                            <form action="delete.php" method="post">
                            <h3><?php echo htmlspecialchars($medication['medication_name']); ?></h3>
                                <p>⌚<?php echo htmlspecialchars($medication['administration_time']); ?>
                                    💊<?php echo htmlspecialchars($medication['dose_count']); ?>
                                    📅<?php echo htmlspecialchars($medication['start_date']); ?> ~
                                    <?php echo htmlspecialchars($medication['end_date']); ?>
                                    <input type="hidden" name="drug_id"
                                        value="<?php echo htmlspecialchars($medication['drug_id']); ?>">
                                    <input type="submit" value="削除する">
                                </p>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>


    </div>
</body>

</html>