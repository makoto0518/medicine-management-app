<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "inov_test";

    try {
        //PDOインスタンスを作成してデータベースに接続
        $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
        //PDOエラー時の例外を設定
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        //SQLクエリを作成
        $sql = "SELECT * FROM medicine";

        //クエリを実行して結果を取得
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        //結果セットを取得する
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //結果を処理する
        if(count($result) > 0) {
            foreach($result as $row) {
                echo "id: " . $row["$id"]. " - drugNumber: " . $row["$drugNumber"] . "  - mediName: " . 
                $row["$mediName"] . " - NumberOfDoses: " . $row["$NumberOfDoses"];
            }
        } else {
            echo "0 results";
        }
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
    }

    $conn = null;
?>