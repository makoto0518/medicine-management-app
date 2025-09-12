<!-- displayRecord.php -->
<?php
session_start();
$debug_mode = true;

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    if ($debug_mode) {
        echo "<p style='color: red;'>セッションが存在しません</p>";
        echo "<p>デバッグ情報</p>";
        echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";
    }
    exit;
}

if (!isset($_POST['drug_id'])) {
    if ($debug_mode) {
        echo "<p style='color: red;'>drug_idを受け取れませんでした</p>";
        echo "<p>デバッグ情報</p>";
        echo "<pre>POST: " . print_r($_POST, true) . "</pre>";
    }
    exit;
}

// echo "<pre>POST: " . print_r($_POST, true) . "</pre>";

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$drug_id = $_POST['drug_id'];

require 'db.php';


try {
    //薬名を得る
    $name_stmt = $pdo->prepare("SELECT medication_name FROM medication_schedule 
        WHERE drug_id = :drug_id");
    $name_stmt->bindParam(":drug_id", $drug_id);
    $name_stmt->execute();
    $name = $name_stmt->fetch(PDO::FETCH_ASSOC);

    // echo "<pre>name:" . print_r($name, true) . "</pre>";
} catch (PDOException $e) {
    die("薬名を得る段階でエラー:" . $e->getMessage());
}

try {
    //服用時間を得る
    $time_stmt = $pdo->prepare("SELECT administration_time FROM medication_schedule 
        WHERE drug_id = :drug_id");

    $time_stmt->bindParam(':drug_id', $drug_id);

    $time_stmt->execute();
    $time = $time_stmt->fetch(PDO::FETCH_COLUMN);
    // echo "<pre>time: " . print_r($time, true) . " </pre>";
} catch (PDOException $e) {
    die("服用時間を得るところでエラー:" . $e->getMessage());
}

try {
    //start_date, end_dateを得る
    $date_stmt = $pdo->prepare("SELECT start_date, end_date FROM medication_schedule
        WHERE drug_id = :drug_id");

    $date_stmt->bindParam(':drug_id', $drug_id);
    $date_stmt->execute();
    $date_range = $date_stmt->fetch(PDO::FETCH_ASSOC);
    $start_date = $date_range['start_date'];
    $end_date = $date_range['end_date'];

    // echo "<pre>start_date: " . print_r($start_date, true) . " </pre>";
    // echo "<pre>end_date: " . print_r($end_date, true) . " </pre>";
} catch (PDOException $e) {
    die("start_date, end_dateを得る段階でエラー: " . $e->getMessage());
}

//服用開始日から服用終了日が入った配列を作っておく
$dates = [];
//最初はcurrentはstart_dateに設定
//strtotime関数は、文字列形式の日付をUnixタイムスタンプに変換する
//もし文字列のままにすると、日付を1日進めるときに単純な加算ができなくなる。年度をまたぐ計算はもっとしんどい
//つまり、日付を扱う場合は文字列からdateに変換する
$current_date = strtotime($start_date); 
$end_date = strtotime($end_date);

while ($current_date <= $end_date) {
    $dates[] = date('Y-m-d', $current_date); //Y-m-d方式(年-月-日)にして入れる
    $current_date = strtotime('+1 day', $current_date); //currentがend_dateになるまで1日ずつ進める
}


try {
    //服用記録がついた日付を得る
    $result_stmt = $pdo->prepare("SELECT administration_date FROM medication_record
            WHERE drug_id = :drug_id");

    $result_stmt->bindParam(':drug_id', $drug_id);
    $result_stmt->execute();
    $results = $result_stmt->fetchAll(PDO::FETCH_ASSOC);

    //$resultsは配列であり、$resultsの各要素は、1つの連想配列である
    //各連想配列は、'administration_date'というキーを持ち、その値として服用記録の日付を含む
    //例えば、$results[0]は、administration_dateが2024-07-03であることを示す連想配列で、
    //$results[1]は、administration_dateが2024-07-02であることを示す連想配列

    // echo "<pre>results:" . print_r($results, true) . "</pre>";


    $table = [];
    foreach ($dates as $date) {
        $table[$date] = '×'; //最初は全部×にしておく
    }

    // echo "<pre>記録前table:" . print_r($table, true) . "</pre>";

    //服用記録があった日だけを〇にしていく 
    //$resultsには服用した日しか入っていないので、それを見ていけばよい
    foreach ($results as $result) {
        $table[$result['administration_date']] = '〇';
    }

    // echo "<pre>記録後テーブル" . print_r($table, true) . "</pre>";
} catch (PDOException $e) {
    die("服用記録を得る段階でエラー:" . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css" type="text/css">
    <title>服用記録表</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        h2 {
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            word-wrap: break-word;
        }

        th {
            background-color: #e0e0e0;
            color: black;
            position: sticky;
            top: 0;
        }

        .taken {
            background-color: lightpink;
        }

        .notaken {
            background-color: lightblue;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }
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
            <h2><?php echo htmlspecialchars($name['medication_name']); ?>の服用記録表</h2>
        </div>

        <div class="button-container">
            <form action="home.php" method="get">
                <button class="home-button" type="submit">ホーム画面へ</button>
            </form>

            <form action="list.php" method="get">
                <button type="submit">薬リスト</button>
            </form>

            <form action="record.php">
                <button type="submit">服用記録</button>
            </form>

            <form action="add.php" method="get">
                <button type="submit">薬の新規登録</button>
            </form>
        </div>

        <div class="divider"></div>

        <div class="medication-list">
            <table>
                <!-- テーブル見出し -->
                <thead>
                    <tr>
                        <th>日付</th>
                        <th><?php echo htmlspecialchars($time); ?></th>
                    </tr>
                </thead>

                <tbody>
                    <!-- 日付を１つずつ処理 -->
                    <?php foreach ($dates as $date): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($date); ?></td>
                            <!-- 結果によってcssで色を変える -->
                            <td class="<?php echo $table[$date] === '〇' ? 'taken' : 'notaken'; ?>">
                                <?php echo htmlspecialchars($table[$date]); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>

</html>