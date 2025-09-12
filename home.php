<!-- home.php -->
<?php
session_start(); //セッションの再開

// home.phpに遷移したときにstart_dateとend_dateを削除する
//record.phpで服用日記録中にほかの画面に遷移したときに、SESSIONに残っていたstart_date、end_dateの情報を消す
unset($_SESSION['start_date']);
unset($_SESSION['end_date']);

$debug_mode = true; //デバッグモードをオン

// タイムゾーンを日本の東京にしないと、今日服用開始の薬が表示されない場合がある
date_default_timezone_set('Asia/Tokyo');

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) { //もしセッションの中に値がない場合
    if ($debug_mode) {
        echo "<p style='color:red;'>セッションが存在しません</p>";
        echo "<p>デバッグ情報:</p>";
        echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>"; //print_rで詳しく表示
    }
}

// echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>"; //print_rで詳しく表示

//セッションからデータを得る
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

//データベース接続ファイルを入手
require 'db.php';

//今日の日付を取得
$today = new DateTime();
$today_formatted = $today->format('Y-m-d'); //今日の日付をYYYY-MM-DDにして取得しなければならない！
//データベースのDate型は通常yyyy-mm--dd型であるので、$today->format('Y-m-d')として変換する必要がある。

try {
    //今日服用する薬の一覧を取り出す 同じ名前がある場合は服用時間が早い順
    $stmt = $pdo->prepare("SELECT medication_name, administration_time, dose_count, start_date, end_date 
        FROM medication_schedule 
        WHERE user_id = :user_id AND start_date <= :today AND end_date >= :today
        ORDER BY medication_name, administration_time ASC");

    //データをバインド
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->bindParam(':today', $today_formatted);

    //実行
    $stmt->execute();

    //全てのデータを得る 条件に一致する薬は複数あることの方が多い！
    $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result_message = "";
    if (!$medications) {
        $result_message = "今日服用する薬はありません";
    } else {
        $result_message = "本日服用予定の薬は以下の通りです";
    }

    // デバッグ用: start_dateとend_dateを表示
    // if ($debug_mode) {
    //     echo "<p>デバッグ情報: 薬の開始日と終了日</p>";
    //     foreach ($medications as $medication) {
    //         echo "<p>薬: " . htmlspecialchars($medication['medication_name']) .
    //             ", 開始日: " . htmlspecialchars($medication['start_date']) .
    //             ", 終了日: " . htmlspecialchars($medication['end_date']) . "</p>";
    //     }
    // }

} catch (PDOException $e) {
    die("データベースエラー:" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css" type="text/css">
    <title>ホーム</title>
    <style>

    </style>
</head>

<body>
    <form action="logout.php" method="post">
        <button class="logout-button" type="submit">ログアウト</button>
    </form>

    <form action="delete.php" method="get">
        <button class="delete-button" type="submit">薬を削除</button>
    </form>
    

    <div class="container">
        <div class="header">
            <h2>ホーム画面</h2>
            <ul>
                <p>薬の種類、服用記録を確認⇒薬リスト</p>
                <p>服用した記録を付ける⇒服用記録</p>
            </ul>
            <!-- <p>ログイン成功! ようこそ、<?php echo htmlspecialchars($username); ?>さん</p> -->
        </div>

        <div class="button-container">
            <!-- actionに送信先のURIを指定 method="get"は省略してもよい(デフォルトでmethod="get"になっている)-->
            <form action="list.php" method="get">
                <button type="submit">薬リスト</button>
            </form>
            <!-- <form action="select_medicine.php" method="get">
                <button type="submit">服用記録</button>
            </form> -->
            <form action="record.php" method="get">
                <button type="submit">服用記録</button>
            </form>

            <form action="add.php" method="get">
                <button type="submit">薬を新規登録</button>
            </form>
        </div>

        <div class="divider"></div>

        <div class="medication-list">
            <h3><?php echo $result_message; ?></h3>
            <ul>
                <!-- 沢山の薬たちを1つ１つ切り分けて確認していく -->
                <?php foreach ($medications as $medication): ?>
                    <li>
                        <!-- strongで太文字に -->
                        <!-- セキュリティと正確な表示のためにhtmlspecailcharsを使う -->
                        <strong><?php echo htmlspecialchars($medication['medication_name']); ?></strong>
                        <p>⌚<?php echo htmlspecialchars($medication['administration_time']); ?>
                            💊<?php echo htmlspecialchars($medication['dose_count']); ?>
                            📅<?php echo htmlspecialchars($medication['start_date']); ?> ~
                            <?php echo htmlspecialchars($medication['end_date']); ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>

</html>