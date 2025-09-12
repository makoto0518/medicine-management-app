<?php
require 'db.php';
$drug_id = 54;
try {
    //全てを取得
    $stmt = $pdo->prepare("SELECT * FROM medication_schedule WHERE drug_id = :drug_id");
    $stmt->bindParam(":drug_id", $drug_id);
    $stmt->execute();
    $medication = $stmt->fetch(PDO::FETCH_ASSOC);

    $medication_name = $medication['medication_name'];
    $administration_time = $medication['administration_time'];
    $start_date = $medication['start_date'];
    $end_date = $medication['end_date'];

    echo $medication_name . " / " . $administration_time . " / " . $start_date . " / " . $end_date;
} catch (PDOException $e) {
    die("データベースエラー:" . $e->getMessage());
}
?>