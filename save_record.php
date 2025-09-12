<!-- save_record.php -->
<!-- 服用記録をデータベースに保存する -->
<?php
    session_start();
    if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    require 'db.php';

    $drug_id = $_POST['drug_id'];
    $record_date = $_POST['record_date'];
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("SELECT start_date, end_date, dose_count FROM medication_schedule WHERE drug_id = :drug_id AND user_id = :user_id");
        $stmt->bindParam(':drug_id', $drug_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $medication = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$medication) {
            header("Location: list.php");
            exit;
        }

        $start_date = $medication['start_date'];
        $end_date = $medication['end_date'];
        $dose_count = $medication['dose_count'];

        // 服用日が範囲内にあるかチェック
        if ($record_date < $start_date || $record_date > $end_date) {
            die("エラー: 服用日は指定された期間内でなければなりません。");
        }

        // 服用数チェック
        // 必要ならここで服用数のチェックを追加

        $stmt = $pdo->prepare("INSERT INTO medication_record (user_id, drug_id, administration_date) VALUES (:user_id, :drug_id, :administration_date)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':drug_id', $drug_id);
        $stmt->bindParam(':administration_date', $record_date);
        $stmt->execute();

        header("Location: list.php");
        exit;
    } catch (PDOException $e) {
        die("データベースエラー: " . $e->getMessage());
    }
?>
