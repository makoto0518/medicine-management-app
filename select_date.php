<!-- select_date -->
<!-- ここで日付を選択 -->
<?php
session_start();

//以下、デバッグ用
$debug_mode = true;

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    if ($debug_mode) {
        echo "<p style='color: red;'>セッションが存在しません。</p>";
        echo "<p>デバッグ情報</p>";
        echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";
    }
}

if (!isset($_GET['medication_name']) || !isset($_GET['administration_time'])) {
    if ($debug_mode) {
        echo "<p style='color: red;'>薬名または服用時間が指定されていません。</p>";
        echo "<p>デバッグ情報</p>";
        echo "<pre>GET: " . print_r($_GET, true) . "</pre>";
    }
}

//データを取得
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$medication_name = $_GET['medication_name'];
$administration_time = $_GET['administration_time'];

//データベース接続ソースコードを取得
require 'db.php';

try {
    // 服用開始日、服用終了日を得る カレンダーの入力範囲を絞るときに使う
    $stmt = $pdo->prepare("SELECT start_date, end_date FROM medication_schedule 
                           WHERE user_id = :user_id AND medication_name = :medication_name AND administration_time = :administration_time");

    //バインド
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':medication_name', $medication_name);
    $stmt->bindParam(':administration_time', $administration_time);

    //実行
    $stmt->execute();

    //実行結果を入手
    $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

    //もし入手できなかったら
    if (!$schedule) {
        die("データベースエラー: 指定された薬と服用時間のスケジュールが見つかりませんでした。");
    }

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}

//これらはレコードの結果を保存するスペース あとで表示する
$record_message = "";
$record_details = "";


//もしフォームからデータが送信された場合
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // カレンダーで選択された日付を取ってくる
    $selected_date = $_POST['administration_date'];

    //カレンダーで選択された日付が服用期間を満たしていたとき
    if ($selected_date >= $schedule['start_date'] && $selected_date <= $schedule['end_date']) {
        try {
            //既に選択された日の記録があるか確認 countでその数を数える(もしあったら1になる)
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM medication_record 
            WHERE user_id = :user_id AND drug_id = 
            (SELECT drug_id FROM medication_schedule WHERE user_id = :user_id AND medication_name = :medication_name AND administration_time = :administration_time)
            AND administration_date = :administration_date");

            $check_stmt->bindParam(':user_id', $user_id);
            $check_stmt->bindParam(':medication_name', $medication_name);
            $check_stmt->bindParam(':administration_time', $administration_time);
            $check_stmt->bindParam(':administration_date', $selected_date); //ここはフォームで選択した日付にする

            //実行
            $check_stmt->execute();

            //select *などを使うときはfetchColumnを使うらしい
            //ここに、送信されてきた日付に既に記録があるかを確認したものを入れる。
            $record_exists = $check_stmt->fetchColumn();
            


            if($record_exists) {
                //もし送信された日付が既に服用記録されていた場合、警告文を表示
                $record_message = "<p style='colot: red;'>{$selected_date}の服用記録は既に行っています。 </p>";
            } else {
                //まだ送信された日付が記録されていない場合、medication_recordテーブルに記録を取る
                //どのユーザーが、どの薬idをもつ薬を、どの日に服用したか記録
                $stmt = $pdo->prepare("INSERT INTO medication_record (user_id, drug_id, administration_date)
                                       VALUES (:user_id, 
                                    --    //drug_idは別で取ってくる
                                       (SELECT drug_id FROM medication_schedule 
                                        WHERE user_id = :user_id AND medication_name = :medication_name AND administration_time = :administration_time), 
                                       :administration_date)");

                //データをバインド
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':medication_name', $medication_name);
                $stmt->bindParam(':administration_time', $administration_time);
                $stmt->bindParam(':administration_date', $selected_date);

                //実行
                $stmt->execute();

                //レコードメッセージに結果を入れる
                $record_message = "<p>服用記録が正常に保存されました。</p>";

                //どんな内容が保存されたか記録しておく これらはあとで表示する
                $record_details = "<p>記録内容:</p>
                                   <ul>
                                        <li>薬名: " .  htmlspecialchars($medication_name) . "</li>
                                        <li>服用時間: " . htmlspecialchars($administration_time) . "</li>
                                        <li>服用日付: " . htmlspecialchars($selected_date) . "</li>
                                    </ul>";
            }
        } catch (PDOException $e) {
            //失敗したときの結果を記録する
            $record_message = "<p style = 'color: red;'>データベースエラー: " . $e->getMessage() ."</p>";
        }
    } else {
        //入力された日付が薬の服用期間の範囲外だった場合
        $record_message = "<p style = 'color: red;'>選択された日付は服用期間外です。もう一度やり直してください。</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服用日選択画面</title>
</head>
<body>
    <h2><?php echo htmlspecialchars($medication_name); ?>の服用日選択</h2>
    <!-- 今から服用記録する薬データの情報 -->
    <p>服用時間: <?php echo htmlspecialchars($administration_time)?></p>
    <p>服用開始日: <?php echo htmlspecialchars($schedule['start_date']); ?></p>
    <p>服用終了日: <?php echo htmlspecialchars($schedule['end_date']); ?></p>
    <!-- URLをエンコードしなければならない。 -->
    <form action="select_date.php?medication_name=<?php echo urlencode($medication_name); ?>&administration_time=<?php echo urlencode($administration_time); ?>" method="post">
        <label for="administration_date">服用日付を選択:</label>
        <!-- カレンダータイプで入力したものを取得し、送信 -->
        <input type="date" id="administration_date" name="administration_date" required>
        <button type="submit">記録</button>
    </form>

    <?php 

    //ここで保存しておいた結果メッセージ文を表示
    if($record_message) {
        echo $record_message;
        if($record_details) {
            echo $record_details;
        }
    }
    ?>

    <form action="home.php" method="get">
        <button type="submit">ホーム画面へ</button>
    </form>
</body>
</html>
