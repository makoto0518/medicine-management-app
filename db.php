<?php
    //使うサーバー、データベースの指定
    //今回はルートユーザー
    $servername = "localhost";
    $dbname = "inov_test";
    $username_db = "root";
    $password_db = "";

    try {
        //データベースに接続
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username_db, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("データベースに接続できません: " . $e->getMessage());
    }
?>