<?php
// セッションスタート
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // セッションを開始
}
$nameArray = $_SESSION['name'];
$atenaArray = $_SESSION['atena'];
$seminarId = $_SESSION['seminarId'];


// サブルーチン：DB接続
function connect() {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
}

// DB接続
try {
    $pdo = connect();
} catch (PDOException $e) {
    echo "DB接続に失敗しました。" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

// 宛名処理
$count1 = 0;
while (isset($nameArray[$count1])) {
    // POSTデータ取得
    $postKey = "atena_option_" . $count1;
    if (!isset($_POST[$postKey])) {
        $count1++;
        continue;
    }

    // 宛名更新処理
    if ($_POST[$postKey] == 2) {
        $atena = $atenaArray[$count1] . "御中";
    } else {
        $atena = $atenaArray[$count1] . "様";
    }

    // レコード確認
    $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE name = :name AND seminarid = :seminarid');
    $stmt->bindValue(':name', $nameArray[$count1], PDO::PARAM_STR);
    $stmt->bindValue(':seminarid', $seminarId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // 宛名更新
        $stmt = $pdo->prepare('UPDATE uketukebo SET atena = :atena WHERE name = :name AND seminarid = :seminarid');
        $stmt->bindValue(':name', $nameArray[$count1], PDO::PARAM_STR);
        $stmt->bindValue(':seminarid', $seminarId, PDO::PARAM_INT);
        $stmt->bindValue(':atena', $atena, PDO::PARAM_STR);
        $stmt->execute();
    }

    $count1++;
}

// DB接続解除
$pdo = null;

//セッション終了
    $_SESSION = [];
    session_destroy();

// リダイレクト
header('Location: s_bill.php');
exit();
?>
