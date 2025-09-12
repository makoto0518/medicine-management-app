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
    exit;
}

$user_id = $_SESSION['user_id'];
require 'db.php';


//システムメッセージ
$system_message = "";

//フラグは最初はfalseにしておく
//服用記録が成功したかどうか 成功した場合trueになり、薬を選ぶ画面に戻るボタンを表示させる
$end = false;

//日付選択画面を見せるかどうか 記録済みの日を選んでしまった場合trueになり、もう一度日付選択画面を映す
$date_select = false;


//服用日選択も服用記録も、POSTメソッドでかつdrug_idが送信されてくる
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['drug_id'])) {
    if (isset($_POST['administration_date'])) { //さらに、服用日も送信されてきたら、服用記録の処理に移行する
        $drug_id = $_POST['drug_id'];
        $administration_date = $_POST['administration_date'];

        $name = $pdo->prepare("SELECT medication_name FROM medication_schedule WHERE drug_id = :drug_id");
        $name->bindParam(":drug_id", $drug_id);
        $name->execute();
        $medication_name = $name->fetch(PDO::FETCH_ASSOC);

        //既にその日に記録があるかどうか確認
        //カウントを使って調べる
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM medication_record 
        WHERE user_id = :user_id AND drug_id = :drug_id AND administration_date = :administration_date");
        $check_stmt->bindParam(':user_id', $user_id);
        $check_stmt->bindParam(':drug_id', $drug_id);
        $check_stmt->bindParam(':administration_date', $administration_date);

        $check_stmt->execute();

        $record_exists = $check_stmt->fetchColumn();

        //もし、既にその日は記録済みだった場合
        if ($record_exists) {
            $system_message = $_POST['administration_date']  . "日は既に服用記録がついています";
            $date_select = true; //時間選択画面を再表示にするため、date_selectフラグをtrueにする

            //まだ記録がない場合
        } else {
            try {
                //medication_recordテーブルに記録を書き込む
                $stmt = $pdo->prepare("INSERT INTO medication_record (user_id, drug_id, administration_date) 
                VALUES (:user_id, :drug_id, :administration_date)");
                $stmt->bindParam(":user_id", $user_id);
                $stmt->bindParam(":drug_id", $drug_id);
                $stmt->bindParam(":administration_date", $administration_date);
                $stmt->execute();


                $system_message = $medication_name['medication_name'] . "の". $_POST['administration_date'] . "日の服用記録が保存されました";
                $end = true; //記録に成功したので、endフラグをtrueにする

                //SESSIONにstart_date,end_dateが残り続けないようにするために、記録が完了したら
                //SESSIONからstart_date,end_dateを無くす
                unset($_SESSION['start_date']);
                unset($_SESSION['end_date']);

            } catch (PDOException $e) {
                die("データベースエラー: " . $e->getMessage());
            }
        }
    }

    // 服用日選択の表示処理 
    else {
        $drug_id = $_POST['drug_id'];

        $name = $pdo->prepare("SELECT medication_name FROM medication_schedule WHERE drug_id = :drug_id");
        $name->bindParam(":drug_id", $drug_id);
        $name->execute();
        $medication_name = $name->fetch(PDO::FETCH_ASSOC);

        $system_message = $medication_name['medication_name'] . "を服用した日付を選んでください";
        $date_select = true; //日付を選択する画面を出したいので、date_selectをtrueにする

        try {
            //まず、服用開始日と服用終了日を取得する
            $stmt = $pdo->prepare("SELECT start_date, end_date FROM medication_schedule WHERE drug_id = :drug_id");
            $stmt->bindParam(":drug_id", $drug_id);
            $stmt->execute();
            $period = $stmt->fetch(PDO::FETCH_ASSOC);

            //もし、何かの間違いで期間を得ることができない場合
            if (!$period) {
                throw new Exception("期間が見つかりませんでした。");
            }

            //得られた日付を文字列からDateTimeに変換しておくとよい。
            $start_date = new DateTime($period['start_date']);
            $end_date = new DateTime($period['end_date']);

            //セッションに$start_dateと$end_dateを保存
            //これをしていないと、既に服用済みの日にちを選択してしまって、もう一度日付を選ぶ段階に戻った際に
            //$start_date,$end_dateが取ってこれなくなる。$_SESSIONに保存することで解決する。
            //しかし、このままだと他のページに遷移してもSESSIONにstart_date,end_dateが残り続ける
            //よって、他のページでは初めにこのセッションがあるか確認し、あった場合にこれらのデータを消すようにした
            $_SESSION['start_date'] = $start_date->format('Y-m-d'); //sessionに入れるときはy-m-d方式の文字列にして入れるとよい。
            $_SESSION['end_date'] = $end_date->format('Y-m-d');


            // ここのキャッチはなぜ2ついるのか？？？
        } catch (PDOException $e) {
            die("データベースエラー(prepare, excute部分): " . $e->getMessage());
        } catch (PDOException $e) {
            die("エラー:(それ以外) " . $e->getMessage());
        }
    }
} else {
    // 薬のリスト表示処理 このページを開いて最初に行う処理
    //postメソッドを使わない場合もここに飛ぶ
    $system_message = "服用した薬を選んでください";
    try {
        $stmt = $pdo->prepare("SELECT * FROM medication_schedule WHERE user_id = :user_id ORDER BY medication_name, administration_time ASC");
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$medications) {
            $system_message = "現在服用中の薬はありません";
        }

    } catch (PDOException $e) {
        die("データベースエラー: " . $e->getMessage());
    }
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
    <form action="logout.php" method="post">
        <button class="logout-button" type="submit">ログアウト</button>
    </form>

    <form action="delete.php" method="get">
        <button class="delete-button" type="submit">薬を削除</button>
    </form>

    <div class="container">
        <div class="header">
            <h2>服用記録画面</h2>
            <?php echo htmlspecialchars($system_message); ?>
        </div>

        <div class="button-container">
            <form action="home.php" method="get">
                <button class="home-button" type="submit">ホーム画面へ</button>
            </form>

            <form action="list.php" method="get">
                <button type="submit">薬リスト</button>
            </form>

            <form action="add.php" method="get">
                <button type="submit">薬の新規登録</button>
            </form>
        </div>

        <div class="divider"></div>

        <div class="medication-list">
            <!-- 薬を選択する段階 -->
            <?php if (isset($medications)): ?>
                <ul>
                    <?php foreach ($medications as $medication): ?>
                        <li>
                            <!-- いまのファイルに投げるときはactionの中身はなくてもよい -->
                            <form action="" method="post">
                                <!-- hiddenの内容を送信する drug_idはユーザーが見れなくてもよいからhidden -->
                                <!-- record_by_select_date.phpで受け取るときは$_POST['name']とすればよい そのためにname="drug_id"と設定している -->
                                <input type="hidden" name="drug_id"
                                    value="<?php echo htmlspecialchars($medication['drug_id']); ?>">
                                <button type="submit" class="button-container">
                                    <?php echo htmlspecialchars($medication['medication_name']); ?> <!-- ボタンの名前は薬名 -->
                                </button>
                            </form>
                            <p>⌚<?php echo htmlspecialchars($medication['administration_time']); ?>
                                💊<?php echo htmlspecialchars($medication['dose_count']); ?>
                                📅<?php echo htmlspecialchars($medication['start_date']); ?> ~
                                <?php echo htmlspecialchars($medication['end_date']); ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <!-- 薬を選択し、服用した日付を選ぶ段階 もしくは入力した日付に服用記録がついていた場合 -->
            <?php if ($date_select): ?>
                <div>
                    <h2>服用した日付の選択</h2>
                    <form action="" method="post">
                        <input type="hidden" name="drug_id" value="<?php echo htmlspecialchars($drug_id); ?>">
                        <label for="administration_date">服用日を選択してください:</label>
                        <select name="administration_date" id="administration_date">
                            <?php
                            // start_dateからend_dateまでの日付を生成して選択肢として表示
                        
                            //$_SESSIONから取ってきたものを、必ずDateTimeオブジェクトに変換しなければならない！
                            //これをしないと文字列のままとなり、日付が出てこない！！
                            $start_date = new DateTime($_SESSION['start_date']);
                            $end_date = new DateTime($_SESSION['end_date']);

                            $end_date->modify('+1 day'); // end_dateを含むために1日加算
                            $date_period = new DatePeriod($start_date, new DateInterval('P1D'), $end_date);
                            foreach ($date_period as $date) {
                                //optionの中身を1つずつ出す
                                echo "<option value=\"" . $date->format('Y-m-d') . "\">" . $date->format('Y-m-d') . "</option>";
                            }
                            ?>
                        </select>
                        <button type="submit">記録する</button>
                    </form>
                    <!-- このファイル自身に何も情報を渡さず遷移する つまり、もう一度薬を選ぶ段階に戻る -->
                    <form action="">
                        <button type="submit">薬選択画面に戻る</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- 薬の服用記録に成功した場合に表示する画面-->
            <?php if ($end): ?>
                <div>
                    <form action="">
                        <input type="submit" value="薬選択画面に戻る">
                    </form>
                    <form action="" method="post">
                        <input type="hidden" name="drug_id" value="<?php echo htmlspecialchars($drug_id); ?>">
                        <button type="submit">日付選択に戻る</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>