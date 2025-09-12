<!-- list.php -->
<?php
//セッションの再開
session_start();

//record.phpで服用日記録中にほかの画面に遷移したときに、SESSIONに残っていたstart_date、end_dateの情報を消す
unset($_SESSION['start_date']);
unset($_SESSION['end_date']);


//デバッグモードをオン
$debug_mode = true;

//セッションからデータを得られなかった場合
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    if ($debug_mode) {
        echo "<p style='color:red;'>セッションが存在しません。ログインページへリダイレクトします。</p>";
        echo "<p>デバッグ情報:</p>";
        //print_rで具体的な変数の中身を示す
        echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";
    }
}

//セッションから値を得る
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

require 'db.php'; //データベース接続をインクルード

try {
    //プリペアドステートメントを準備
    //薬名⇒同じ薬名があった場合は服用日が早い順にソート
    //同じ薬でかつ同じ時期に服用し始めた⇒服用時間が早い順にソート
    $stmt = $pdo->prepare("SELECT * FROM medication_schedule 
    WHERE user_id = :user_id ORDER BY medication_name, start_date, administration_time");

    //パラメータをバインド
    $stmt->bindParam(':user_id', $user_id);

    //ステートメントを実行
    $stmt->execute();

    //結果を取得
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
    <title>薬リスト</title>
</head>
<link rel="stylesheet" href="style.css" type="text/css">
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
            <h2>薬リスト画面</h2>
            <p>薬の情報は詳細情報、過去の服用記録は記録確認から</p>
        </div>

        <div class="button-container">
            <form action="home.php" method="get">
                <button class="home-button" type="submit">ホーム画面へ</button>
            </form>

            <!-- 服用記録をする薬を選ぶ -->
            <form action="record.php" method="get">
                <button type="submit">服用記録</button>
            </form>
            <form action="add.php" method="get">
                <button type="submit">薬を新規登録</button>
            </form>

        </div>

        <div class="divider"></div>


        <!-- データが得られた -->
        <div class="medication-list">
            <?php if (!empty($medications)): ?>
                <ul>
                    <!-- medications の連想配列を1つずつmedicationとして処理していく -->
                    <!-- 薬たちの塊を、１つ１つの薬に切り分けて処理する -->
                    <?php foreach ($medications as $medication): ?>
                        <li class="medication-item">
                            <h3><?php echo htmlspecialchars($medication['medication_name']); ?></h3>
                            <p>⌚<?php echo htmlspecialchars($medication['administration_time']); ?>
                                💊<?php echo htmlspecialchars($medication['dose_count']); ?>
                                📅<?php echo htmlspecialchars($medication['start_date']); ?> ~
                                <?php echo htmlspecialchars($medication['end_date']); ?>
                            </p>
                            <form action="displayRecord.php" method="post" style="display: inline;">
                                <input type="hidden" name="drug_id"
                                    value="<?php echo htmlspecialchars($medication['drug_id']); ?>">
                                <button type="submit">記録確認</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- データがない -->
            <?php else: ?>
                <p>現在、服用中の薬はありません</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>