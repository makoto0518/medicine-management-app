<!-- detail.php -->
<?php
    //セッションスタート
    session_start();
    
    $debug_mode = true;  //デバッグモードの設定 true

    //もしセッションからデータが得られなかった場合
    if(!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
        if($debug_mode) {
            echo "<p style='color:red;'>セッションが存在しません</p>";
            echo "<p>デバッグ情報:</p>";
            echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";
        }
    }

    //URLからデータが得られなかった場合
    if(!isset($_GET['medication_name'])) {
        if($debug_mode) {
            echo "<p style='color: red;'>薬名が指定されていません。</p>";
            echo "<p style='color: red;'>デバッグ情報</p>";
            echo "<pre>SESSION:" . print_r($_SESSION, true) . "</pre>";
        }
    }

    //データを得る
    $medication_name = $_GET['medication_name'];

    //SQL接続ソースコードが入ったファイルを得る
    require 'db.php';

    try {
        //薬名と使用者のidに一致する薬情報を取り出す
        //服用時間でソート
        $stmt = $pdo->prepare("SELECT * FROM medication_schedule WHERE medication_name = :medication_name AND user_id = :user_id ORDER BY administration_time");

        //データをバインド
        $stmt->bindParam(':medication_name', $medication_name);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);

        //実行
        $stmt->execute();

        //全てのデータを得る
        $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(!$medications) {
            //薬が見つからない場合
            header("Location: list.php");
            exit;
        } 
    } catch (PDOException $e) {
        die("データベースエラー: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>薬の詳細</title>
</head>
<body>
    <h2>薬の詳細</h2>
    <!-- まず、薬名だけを表示する。 -->
    <p>薬名: <?php echo htmlspecialchars($medication_name); ?></p>
    <!-- 以下、その薬の詳細を表示 最初に薬を表示することで薬名の重複を防ぐ -->
    <?php foreach ($medications as $medication) : ?>
        <p>服用時間: <?php echo htmlspecialchars($medication['administration_time']); ?></p>
        <p>服用数: <?php echo htmlspecialchars($medication['dose_count']); ?></p>
        <p>服用開始日: <?php echo htmlspecialchars($medication['start_date']); ?></p>
        <p>服用終了日: <?php echo htmlspecialchars($medication['end_date']); ?></p>
        <hr>
        <!-- hrは区切り線 -->
    <?php endforeach; ?>

    <form action="list.php" method="get">
        <button type="submit">薬リストへ戻る</button>
    </form>
    <form action="home.php" method="get">
        <button type="submit">ホーム画面へ戻る</button>
    </form>
</body>
</html>
