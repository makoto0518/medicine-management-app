<?php
// サーバー側でデータを保持する。ここでセッションをスタート
session_start();

//現在のリクエストメソッドがPOSTの場合のみ
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //POSTメソッドで送信された値を取得
    $username = $_POST["username"];
    $password = $_POST["password"];

    require 'db.php'; //データベース接続をインクルード これをするとnew PDO文を書かなくてよい

    //ここの構造はよくない。例えばユーザーが何万人もいると、全てを選択して取り出すのはセキュリティもよくないし、
    //ネットーワークとしてもよくない。
    //システムにユーザー名、パスワードを送って、合致したかどうかを判断させたほうがよい。
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");

        // パラメータをバインド
        //SQLインジェクションを防止するため、バインドする
        //ユーザー入力がクエリ文字の一部としてではなく、独立したデータとして扱われるので、攻撃者がSQLインジェクションを仕掛けることが出来なくなる
        $stmt->bindParam(':username', $username);

        // ステートメントを実行
        $stmt->execute();


        if ($stmt->rowCount() > 0) {
            // ユーザーが見つかった場合はパスワードを比較
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stored_password = $row['password'];

            // パスワードの比較
            if ($password === $stored_password) {
                // 認証成功
                $_SESSION['username'] = $username; // セッションにユーザー名を保存
                $_SESSION['user_id'] = $row['user_id']; //ユーザーidをセッションに保存
                header("Location: home.php");  //home.phpに移動
                // header("Location: hoverTest.php");
                exit;
            } else {
                // 認証失敗
                $message = "ユーザー名またはパスワードが違います。";
            }
        } else {
            // ユーザーが見つからない場合
            $message = "ユーザー名またはパスワードが違います。";
        }
    } catch (PDOException $e) {
        die("データベースに接続できません: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
</head>

<style>
    body {
        display: flex;
        justify-content: center;
        padding-top: 50px;
        /* ページの上部に余白を追加 */
        font-family: Arial, sans-serif;

    }

    .main {
        background-color: white;
        padding: 20px;
        border-radius: 8px;

    }

    .login {
        display: flex;
        flex-direction: column;
    }

    .login input[type="text"],
    .login input[type="password"] {
        margin-bottom: 10px;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .login input[type="submit"] {
        padding: 10px;
        border: none;
        border-radius: 4px;
        background-color: #5cb85c;
        color: white;
        cursor: pointer;
    }

    .login input[type="submit"]:hover {
        background-color: #4cae4c;
    }

    .errorBox {
        display: flex;
        justify-content: center;
        margin-top: 10px;
    }

    .error {
        color: red;
        height: 20px
    }
</style>

<body>
    <div class="main">
        <!-- POSTはデータがURLに表示されないため、安全にデータ送信ができる -->
        <form class="login" action="login.php" method="post">

            <input type="text" name="username" placeholder="ユーザー名" required><br>
            <input type="password" name="password" placeholder="パスワード" required><br>
            <input type="submit" value="ログイン">
        </form>
    </div>


    <?php if (isset($message)): ?>
        <div class="errorBox">
            <p class="error"><?php echo $message; ?></p>
        </div>
    <?php endif; ?>

</body>

</html>