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
function getCategoryName($pdo, $categoryId, &$categoryName) {
    $stmt = $pdo->prepare('SELECT categoryname FROM category WHERE id = :id');
    $stmt->bindValue(':id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    // カテゴリー名が取得できたか確認
    $categoryName = $record ? $record['categoryname'] : '不明なカテゴリ';
}

// メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
// 年度末日を作成
if (!empty($_SESSION['nendo'])){
$nendo = $_SESSION['nendo']; // セッションに値がなければ現在の年を使用
$kisho = $nendo."-04-01";
$date = new DateTime($kisho);
$kisho = $date->format('Y-m-d');
$nendo++;
$mmdd = "03-31";
$dateString = $nendo . '-' . $mmdd;
$date = new DateTime($dateString);
$nendoMatu = $date->format('Y-m-d');
$_SESSION['list'][] = "インプット年度の年度末 " . $nendoMatu . "<br>";
}else{
    header('Location: ts.html?error=終わろうとしている年度あるいは終わった年度をインプットしてください。');
    exit();
}
// DBに接続
$pdo = connect();

//正会員マスターの生きている会員のトータル数を求める(withdraw =1 AND (adday <= nendomatu OR NULL)) OR (withdraw =2 AND( withdrawday > kisho))
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM regular  WHERE (withdraw = 1 AND (adday <= :nendoMatu OR adday IS NULL)) OR (withdraw = 2 AND (withdrawday > :kisho))');
$stmt->bindValue(':kisho', $kisho, PDO::PARAM_STR);
$stmt->bindValue(':nendoMatu', $nendoMatu, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total = $result['total'];

// 正会員マスターより、年度末日以前の入会者で、生きているレコード（withdraw = 1）でカテゴリー別人数を集計する
$_SESSION['list'][] = "正会員カテゴリー別人数<br>";
$stmt = null;
$stmt = $pdo->prepare('SELECT category, COUNT(*) AS total FROM regular 
                       WHERE (withdraw = 1 AND (adday <= :nendoMatu OR adday IS NULL)) OR (withdraw = 2 AND (withdrawday > :kisho))
                       GROUP BY category');
$stmt->bindValue(':nendoMatu', $nendoMatu, PDO::PARAM_STR);
$stmt->bindValue(':kisho', $kisho, PDO::PARAM_STR);
$stmt->execute();

while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $categoryId = $result['category'];
    $ninzu = $result['total'];
    $rate = number_format($ninzu / $total * 100, 2);

    // カテゴリー名を取得
    $categoryName = '';
    getCategoryName($pdo, $categoryId, $categoryName);
    
    $_SESSION['list'][] = $categoryName . " " . $ninzu . "人 (" . $rate . "%)<br>";
}

$_SESSION['list'][] = "年度末の正会員総数　＝　".$total."<br>";

//今年度入退会者
$stmt = null;

$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM regular  WHERE withdraw = 1 AND (adday >= :kisho AND  adday <= :nendoMatu )');
$stmt->bindValue(':kisho', $kisho, PDO::PARAM_STR);
$stmt->bindValue(':nendoMatu', $nendoMatu, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$add_total = $result['total'];
$_SESSION['list'][] = "今年度入会　＝　".$add_total."<br>";

$stmt = null;

$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM regular  WHERE withdraw = 2 AND (withdrawday >= :kisho AND  withdrawday <= :nendoMatu )');
$stmt->bindValue(':kisho', $kisho, PDO::PARAM_STR);
$stmt->bindValue(':nendoMatu', $nendoMatu, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$withdraw_total = $result['total'];
$_SESSION['list'][] = "今年度退会　＝　".$withdraw_total."<br>";

// 処理完了後、リダイレクト
$pdo = NULL;
header('Location: ts_support.php');
exit();

?>
