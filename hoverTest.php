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
    <title>デザインし直したホーム画面</title>
    <style>
        /* Global Menu */
        header {
            margin-bottom: 13em;
            position: relative;
            width: 100%;
            background-color: #444;
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        ul>li {
            display: inline-block;
            position: relative;
        }

        ul>li>a {
            padding: 15px 30px;
            display: block;
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: .2em;
            color: white;
            text-decoration: none;
        }

        ul>li>span {
            margin-left: 1.2em;
        }

        ul>li>a:hover {
            background-color: #efefef;
            color: #444;
        }

        /* Submenu */
        ul li ul {
            position: absolute;
            top: 55px;
            left: 0;
            display: none;
        }

        ul li ul li {
            display: block;
        }

        ul li ul li a {
            background-color: #efefef;
            color: #444;
        }

        ul li ul li a:hover {
            background-color: #ddd;
        }
    </style>
</head>

<body>
    <header class="sample01">
        <ul>
            <li><a href="home.php">ホーム</a></li>
            <li><a href="list.php">薬一覧</a></li>
            <li><a href="record.php">服用を記録</a></li>
            <li><a href="add.php">薬登録</a></li>
            <li>
                <a href="#" onclick="toggleSubmenu(event)">その他 <span>▼</span></a>
                <ul>
                    <li><a href="logout.php">ログアウト</a></li>
                    <li><a href="deleteMedicine.php">薬の削除</a></li>
                </ul>
            </li>
        </ul>
    </header>

    <script>
        function toggleSubmenu(event) {
            event.preventDefault(); // デフォルトのリンク動作を防ぐ
            const submenu = event.currentTarget.nextElementSibling;
            if (submenu.style.display === "block") {
                submenu.style.display = "none";
            } else {
                submenu.style.display = "block";
            }
        }

        // クリックイベントのバブリングを防止し、サブメニューの外側をクリックするとサブメニューを閉じる
        document.addEventListener('click', function (event) {
            const isClickInsideMenu = event.target.closest('li');
            if (!isClickInsideMenu) {
                const submenus = document.querySelectorAll('.sample01 ul li ul');
                submenus.forEach(submenu => {
                    submenu.style.display = 'none';
                });
            }
        });
    </script>
</body>

</html>