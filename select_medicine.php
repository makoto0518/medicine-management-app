<!-- ここで、服用記録を付けたい薬の名前を選ぶ。服用時間が違うものも同じ名前に含む。 -->
<!-- select_medicine.php -->

<?php
    //セッションの開始
    session_start();

    //デバッグモードをオン
    $debug_mode = true;
    if(!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) { //セッションからユーザー名、ユーザーidが得られなかった
        if($debug_mode) {   //デバッグモードがオンのとき
            echo "<p style='color:red;'>セッションが存在しません。</p>";
            echo "<p>デバッグ情報:</p>";
            //具体的な変数の中身を示す
            echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";    
        }
    }

    //セッションから値を取得
    $username = $_SESSION['username'];
    $user_id = $_SESSION['user_id'];

    //データベースに接続するコードが入ったファイルをインクルード
    require 'db.php';

    try {
        //ユーザーが使っている薬名を取得
        $stmt = $pdo->prepare("SELECT DISTINCT medication_name FROM medication_schedule WHERE user_id = :user_id");

        //パラメータをバインド
        $stmt->bindParam(':user_id', $user_id);

        //実行
        $stmt->execute();

        //全ての行を取得し、連想配列に格納
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
<body>
    <h2>薬選択画面</h2>
    <p>ここで記録したい薬を選ぶ</p>
    <!-- データが得られた場合 -->
    <?php if(!empty($medications)) : ?>
        <ul>
            <!-- 配列１つ１つを処理 -->
        <?php foreach($medications as $medication) : ?>
            <li>
                <form action="select_time.php" method="get">
                    <!-- ページ遷移のときに渡す -->
                <input type="hidden" name="medication_name" value="<?php echo htmlspecialchars($medication['medication_name']); ?>">
                <!-- 薬名を羅列 -->
                <button type="submit"><?php echo htmlspecialchars($medication['medication_name']); ?></button>
                </form>
            </li>
        <?php endforeach; ?>
        </ul>
    <!-- データが得られなかった場合 -->
    <?php else :?>
        <p>現在、服用中の薬はありません</p>
    <?php endif; ?>

    <form action="home.php" method="get">
        <button type="submit">ホーム画面へ</button>
    </form>
    

</body>
</html>