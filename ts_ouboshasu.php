<?php
if (session_status() == PHP_SESSION_NONE) {
    // セッションを開始
    session_start();
}

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

// カテゴリー名を取得
function getSeminarName($pdo, $seminarId, &$seminarName) {
    $stmt = $pdo->prepare('SELECT name FROM seminar WHERE id = :id');
    $stmt->bindValue(':id', $seminarId, PDO::PARAM_INT);
    $stmt->execute();
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    // カテゴリー名が取得できたか確認
    $seminarName = $record ? $record['name'] : '不明なカテゴリ';
}

// メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
// 年度末日を作成
$nendo = $_SESSION['nendo'] ?? date('Y'); // セッションに値がなければ現在の年を使用
$lastTwoDigits = $nendo % 100;  // 下2桁を取得
$startId = $lastTwoDigits * 100;
$endId = $startId + 99;

echo "スタートID= " . $startId . " エンドID＝" . $endId . "<br>";

// DBに接続
$pdo = connect();

// uketukeboTBより、年度末日以前のセミナーについてセミナー毎の参加者をカウントする
$total = 0;
$stmt = $pdo->prepare('SELECT seminarid, COUNT(*) AS total FROM uketukebo 
                       WHERE seminarid > :startId AND seminarid < :endId
                       GROUP BY seminarid');
$stmt->bindValue(':startId', $startId, PDO::PARAM_INT);
$stmt->bindValue(':endId', $endId, PDO::PARAM_INT);
$stmt->execute();

$arrayResult = []; // 配列を初期化
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    
    $arrayResult[] = $result;
    $total = $total + $result['total'];
}

// カテゴリ別に集計結果を表示
$_SESSION['list'][] = "セミナー別応募者数<br>";
foreach ($arrayResult as $result) {
    $seminarId = $result['seminarid'];
    $seminarName = "";
    getSeminarName($pdo, $seminarId, $seminarName);
    $ninzu = $result['total'];
    $_SESSION['list'][] = $seminarName . " " . $ninzu . "人<br>";
}

//ため込んだ$_SESSION['list']の全件を表示
foreach ($_SESSION['list'] as $list) {
    if (is_array($list)) {
        // 配列内のネストされた要素を処理
        echo implode(', ', $list) . "<br>";
    } else {
        // 配列ではない場合、そのまま出力
        echo $list . "<br>";
    }
}
session_destroy(); 
?>
