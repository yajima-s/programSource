<?php

// サブルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
// jmcaDBを開きます
function connect() {
    try {
        $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

// メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
if (session_status() == PHP_SESSION_NONE) {
    // セッションを開始
    session_start();
}

// 年度末日と期初日を作成
if (!isset($_SESSION['nendo']) OR (empty($_SESSION['nendo']))){
    header('Location: ts.html?error=終わろうとしている年度あるいは終わった年度をインプットしてください。');
    exit();
}
$nendo = $_SESSION['nendo']; // 
$kisho = $nendo."-04-01";
$date = new DateTime($kisho);
$kisho = $date->format('Y-m-d');
$nendo++;
$mmdd = "03-31";
$dateString = $nendo . '-' . $mmdd;
$date = new DateTime($dateString);
$nendoMatu = $date->format('Y-m-d');
$_SESSION['list'][] = "期初: " . $kisho . " 年度末: " . $nendoMatu . "<br>";
$nendo--;
// DBに接続
$pdo = connect();

// 年度末日以前の賛助会員数を取得
$total = 0;
$stmt = $pdo->prepare('
    SELECT COUNT(*) AS total 
    FROM support
    WHERE 
        (withdraw = 1 AND (adday <= :nendoMatu1 OR adday IS NULL))
        OR
        (withdraw = 2 AND (withdrawday > :nendoMatu2))
');

$stmt->bindValue(':nendoMatu1', $nendoMatu, PDO::PARAM_STR);
$stmt->bindValue(':nendoMatu2', $nendoMatu, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['list'][] = $nendo . "年度賛助会員数 = " . $result['total'] . "<br>";
// 今期入会の賛助会員を表示
$sw = 0;
$stmt = $pdo->prepare('SELECT name FROM support 
                       WHERE withdraw = 1 AND (adday >= :kisho AND adday <= :nendoMatu)');
$stmt->bindValue(':kisho', $kisho, PDO::PARAM_STR);
$stmt->bindValue(':nendoMatu', $nendoMatu, PDO::PARAM_STR);
$stmt->execute();
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $_SESSION['list'][] = "今期入会賛助会員: " . $result['name'] . "<br>";
    $sw++;
}
if ($sw == 0) {
    $_SESSION['list'][] = "今期入会賛助会員はありません。<br>";
}

// 今期退会の賛助会員を表示
$sw = 0;
$stmt = $pdo->prepare('SELECT name FROM support 
                       WHERE withdraw = 2 AND (withdrawday >= :kisho AND withdrawday <= :nendoMatu)');
$stmt->bindValue(':kisho', $kisho, PDO::PARAM_STR);
$stmt->bindValue(':nendoMatu', $nendoMatu, PDO::PARAM_STR);
$stmt->execute();
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $_SESSION['list'][] = "今期退会賛助会員: " . $result['name'] . "<br>";
    $sw++;
}
if ($sw == 0) {
    $_SESSION['list'][] = "今期退会賛助会員はありません。<br>";
}

//来期入会の賛助会員
$sw = 0;
$stmt = $pdo->prepare('SELECT name FROM support 
                       WHERE withdraw = 1 AND (adday >= :nendoMatu)');
$stmt->bindValue(':nendoMatu', $nendoMatu, PDO::PARAM_STR);
$stmt->execute();
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $_SESSION['list'][] = "来期入会賛助会員: " . $result['name'] . "<br>";
    $sw++;
}
if ($sw == 0) {
    $_SESSION['list'][] = "来期入会賛助会員はありません。<br>";
}

/* ts_ouboshasu.phpの最後に移動
//$_SESSION['list']の表示
foreach ($_SESSION['list'] as $list) {
    if (is_array($list)) {
        // 配列内のネストされた要素を処理
        echo implode(', ', $list) . "<br>";
    } else {
        // 配列ではない場合、そのまま出力
        echo $list . "<br>";
    }
}
*/

$pdo = NULL;
header('Location: ts_support_regitem.php');
exit();
?>
