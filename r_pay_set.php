<?php
//何年度の入金かの選択(1:fee2 2:fee1 3:feeに従い正会員TBの対象fee欄に、入金日を入れる

//-----－サブルーチン
//DBと接続
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

//正会員マスターより該当データを探す
function regular_set($pdo, $nameArray, $count, $paid,&$messageTag,$nyuukinNendo) {
    $name = $nameArray[$count]; 
    $stmt = $pdo->prepare('SELECT * FROM regular WHERE name = :name');
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    try {
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
    error_log("データ更新に失敗しました DB Error: " . $e->getMessage());
    exit();
    }
    
    if ($row) {
        if ($nyuukinNendo == 1){
            $stmt = $pdo->prepare('UPDATE regular SET fee2 = :paid,receiptsend = NULL WHERE name = :name');
            $stmt->bindValue(':paid', $paid, PDO::PARAM_STR);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute(); 
            }
        if ($nyuukinNendo == 2){
            $stmt = $pdo->prepare('UPDATE regular SET fee1 = :paid,receiptsend = NULL WHERE name = :name');
            $stmt->bindValue(':paid', $paid, PDO::PARAM_STR);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            }
        if ($nyuukinNendo == 3){
            $stmt = $pdo->prepare('UPDATE regular SET fee = :paid,receiptsend = NULL WHERE name = :name');
            $stmt->bindValue(':paid', $paid, PDO::PARAM_STR);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            }
      
    }else{
        $messageTag = 1;//正会員マスターに存在しない
    }
}

//------－メインルーチン
//インプットチェック
//sessionの開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ＤＢと接続
$pdo = connect();

if (!isset($_SESSION['nameArray'], $_SESSION['maxcount'])) {
    header('Location: r_pay_select.php?error=' . urlencode('セッション情報が不足しています。'));
    exit();
}

$nameArray = $_SESSION['nameArray'];
$maxcount = $_SESSION['maxcount'];
if (!empty($_POST['end'])) {
    if ($_POST['end'] == "e") {
        header('Location: r_receipt.php');
        exit();
    }else{
        header('Location: r_pay_in.php?error=' . urlencode('終了にe以外の文字が入力されています。'));
        exit();
    }
}
if (!empty($_POST['more'])) {
    if ($_POST['more'] == "m"){
        header('Location: r_pay_in.php?message=' . urlencode('再検索です。'));
        exit();
    }else{
        header('Location: r_pay_in.php?error=' . urlencode('再検索にｍ以外の文字が入力されています。'));
        exit();
    }
}
if (ctype_digit($_POST['count'])) {
    $count = (int)$_POST['count'];
    $count = $count -1; //何行目から0から始まるindexniに変換
    if ($count >= $_SESSION['maxcount']) {
        header('Location: r_pay_select.php?error=' . urlencode('番号が表示された番号の最大値を超えています。'));
        exit();
    }
    } else {
        header('Location: r_pay_select.php?error=' . urlencode('番号を小文字で入力してください。'));
        exit();
}

if (ctype_digit($_POST['nendo'])) {
    $nyuukinNendo = (int)$_POST['nendo']; 
    if ($nyuukinNendo > 3 OR $nyuukinNendo < 1 ) {
        header('Location: r_pay_select.php?error=' . urlencode('選択肢が１～３の範囲外です'));
        exit();
    }
    } else {
        header('Location: r_pay_select.php?error=' . urlencode('番号を半角数字で入力してください。'));
        exit();
}

if (preg_match('/\A\d{4}\/\d{2}\/\d{2}\z/', $_POST['paid'])) {
    $paid = $_POST['paid'];
} else {
    header('Location: r_pay_select.php?error=' . urlencode('入金日を小文字yyyy/mm/dd形式で入力してください'));
    exit();
}

$messageTag = 0;
regular_set($pdo, $nameArray, $count, $paid,$messageTag, $nyuukinNendo);

//セッションを閉じる
session_destroy();
$pdo = null;
$name = $nameArray[$count];
if ($messageTag == 0){
    $message = "の入金を問題なく処理しました。";
    $message = htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . $message;
    header('Location: r_pay_in.php?message=' . urlencode($message));
    exit();
}elseif($messageTag == 1){
    $error = "は、正会員マスターに存在しません";
    $error = htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . $error;
    header('Location: r_pay_in.php?error=' . urlencode($error));
    exit();
    
}


?>
