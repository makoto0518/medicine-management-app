<?php
// セッションの開始
session_start();

$debug_mode = true;

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    if ($debug_mode) {
        echo "<p style='color:red;'>セッションが存在しません</p>";
        echo "<p>デバッグ情報:</p>";
        echo "<pre>SESSION:" . print_r($_SESSION, true) . "</pre>";
    }
}

if (!isset($_POST['drug_id'])) {
    if ($debug_mode) {
        echo "<p style='color:red;>POSTで受け取れませんでした</p>";
        echo "<p>デバッグ情報:</p>";
        echo "<pre>POST:" . print_r($_POST, true) . "</pre>";
    }
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$drug_id = $_POST['drug_id'];

require 'db.php';

try {
    echo "<pre>POST:" . print_r($_POST, true) . "</pre>";

    $stmt = $pdo->prepare("SELECT start_date, end_date FROM medication_schedule
    WHERE drug_id = :drug_id");

    $stmt->bindParam(":drug_id", $drug_id);

    $stmt->execute();

    //結果は1行だけなので、fetchを使う
    //もしfetchAllを使うと、結果が配列の配列として返されるため、アクセスするときは
    //$period[0]['start_date'] としなければならなく、面倒
    $period = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<pre>period:" . print_r($period, true) . "</pre>";

    if (!$period) {
        throw new Exception("期間が見つかりませんでした。");
    }

    $start_date = new DateTime($period['start_date']);
    $end_date = new DateTime($period['end_date']);

    echo "<pre>start_end:" . print_r($start_date, true) . "~"  . print_r($end_date, true) . "</pre>";



} catch (PDOException $e) {
    die("データベースエラー" . $e->getMessage());
} catch (Exception $e) {
    die("エラー:" . $e->getMessage());
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
    <div class="container">

    </div>
</body>
</html>
