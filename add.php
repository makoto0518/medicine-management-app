<?php
session_start();

$debug_mode = true;

//record.phpで服用日記録中にほかの画面に遷移したときに、SESSIONに残っていたstart_date、end_dateの情報を消す
unset($_SESSION['start_date']);
unset($_SESSION['end_date']);


if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    if ($debug_mode) {
        echo "<p style='color:red;'>セッションが存在しません。ログインページへリダイレクトします。</p>";
        echo "<p>デバッグ情報:</p>";
        echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";
    }
    header("location: login.php");
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

require 'db.php';

$message = "$username さんが服用する薬情報を入力してください。";

// echo "$username さんが服用する薬情報を入力してください。";

//もしPOSTメソッドの場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $medication_name = $_POST["medication_name"];
    $administration_time = $_POST["administration_time"];
    $dose_count = $_POST["dose_count"];
    $start_date = $_POST["start_date"];
    $end_date = $_POST["end_date"];

    try {
        //今入力された薬と一致するものが既にないか探す
        //countで数を数える
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM medication_schedule 
        WHERE user_id = :user_id AND medication_name = :medication_name 
        AND administration_time = :administration_time AND dose_count = :dose_count AND start_date = :start_date AND end_date = :end_date");

        $stmt_check->bindParam(':user_id', $user_id);
        $stmt_check->bindParam(':medication_name', $medication_name);
        $stmt_check->bindParam(':administration_time', $administration_time);
        $stmt_check->bindParam(':dose_count', $dose_count);
        $stmt_check->bindParam(':start_date', $start_date);
        $stmt_check->bindParam(':end_date', $end_date);

        $stmt_check->execute();

        // クエリの結果は1つの列（カウント）だけ。このような場合、fetchColumn を使用すると、直接その1つの列の値を取得できる
        $result = $stmt_check->fetchColumn();


        if ($result > 0) { //もし既に同じ薬が登録してある場合
            $message = "<p class='error-message'>全く同じ薬情報が既に登録されています。</p>";

        } else { //まだ登録されていない場合
            //登録する
            $stmt = $pdo->prepare("INSERT INTO medication_schedule (user_id, medication_name, administration_time, dose_count, start_date, end_date) 
            VALUES (:user_id, :medication_name, :administration_time, :dose_count, :start_date, :end_date)");

            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':medication_name', $medication_name);
            $stmt->bindParam(':administration_time', $administration_time);
            $stmt->bindParam(':dose_count', $dose_count);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);

            $stmt->execute();
            
            $message = "<p class='success-message'>薬の情報が正常に保存されました。</p>";
        }

    } catch (PDOException $e) {
        die("データベースエラー: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css" type="text/css">
    <title>薬の新規登録</title>
</head>

<style>

</style>

<body>
    <form action="logout.php" method="post">
        <button class="logout-button" type="submit">ログアウト</button>
    </form>

    <form action="delete.php" method="get">
        <button class="delete-button" type="submit">薬を削除</button>
    </form>

    <div class="container">
        <div class="header">
            <h2>薬登録画面</h2>
            <!-- <p><?php echo htmlspecialchars($message); ?></p> -->
             <!-- なぜかhtmlspecialcharsを使うと色が変わってくれない -->
            <?php echo $message; ?>
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
        </div>

        <div class="divider"></div>


        <div class="form-container">
            <form action="add.php" method="post">
                <label for="medication_name">薬名:</label>
                <input type="text" id="medication_name" name="medication_name" placeholder="薬名" required>

                <label for="administration_time">服用時間:</label>
                <input type="time" id="administration_time" name="administration_time" placeholder="服用時間" required>

                <label for="dose_count">服用数:</label>
                <input type="number" id="dose_count" name="dose_count" placeholder="服用数" required>

                <label for="start_date">服用開始日:</label>
                <input type="date" id="start_date" name="start_date" placeholder="服用開始日" required>

                <label for="end_date">服用終了日:</label>
                <input type="date" id="end_date" name="end_date" placeholder="服用終了日" required>
                
                <input type="submit" value="保存">
            </form>
        </div>

    </div>
</body>

</html>
