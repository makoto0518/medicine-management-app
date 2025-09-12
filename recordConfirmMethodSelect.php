<!-- recordConfirmMethodSelect.php -->
<?php 
    session_start();
    $debug_mode = true;

    if(!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
        if($debug_mode) {
            echo "<p style = 'color:red'>セッションが存在しません</p>";
            echo "<p>デバッグ情報</p>";
            echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";
        }
    }

    if(!isset($_GET['medication_name'])) {
        if($debug_mode) {
            echo "<p style = 'color: red;>薬目が指定されていません";
            echo "<p>デバッグ情報</p>";
            echo "<pre>GET: " . print_r($_GET ,true) . "</pre>";
        }
    }

    $username = $_SESSION['username'];
    $user_id = $_SESSION['user_id'];
    $medication_name = $_GET['medication_name'];

    require 'db.php';

    try {
        $stmt = $pdo->prepare("
            
        ");
    }
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>服用記録の確認方法を選択</title>
</head>
<body>
    <h2>ここで<?php echo htmlspecialchars($medication_name); ?>の服用記録の確認方法を選択</h2>


    <form action="list.php">
        <button type="submit">薬リストに戻る</button>
    </form>
</body>

</html>