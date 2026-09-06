<?php
//-----－サブルーチン
//DBと接続
function connect() {
    try{
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
    }catch (PDOException $e){
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}
//受付簿より該当データを探す

function uketukebo_set($pdo, $name, $seminarid, &$paid, &$paidTag) {
    $stmt1 = $pdo->prepare('SELECT paid FROM uketukebo  WHERE name = :name AND seminarid = :seminarid');
    $stmt1->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt1->bindValue(':seminarid', $seminarid, PDO::PARAM_INT);
    $stmt1->execute();
    if (!$stmt1->execute()) {
    throw new Exception("SELECT クエリの実行に失敗しました: " . implode(", ", $stmt1->errorInfo()));
    }
    $record = $stmt1->fetch(PDO::FETCH_ASSOC);
    if (!isset($record['paid'])) {
        $stmt = $pdo->prepare('UPDATE uketukebo SET paid = :paid WHERE name = :name AND seminarid = :seminarid');
        $stmt->bindValue(':paid', $paid, PDO::PARAM_STR);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':seminarid', $seminarid, PDO::PARAM_INT);
        $stmt->execute();
    }else{
        $paidTag = 2;
        $paid = $record['paid'];
    }   
}



//------－メインルーチン
//インプットチェック
//sessionの開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $_SESSION['pay'] = 0;//未払、入金済みの選択をクリア
}

if (isset($_POST['choice'])) {
        $choice = $_POST['choice'];
        $choice = htmlspecialchars($choice, ENT_QUOTES, 'UTF-8');
        if ($choice == "未入金分のみ表示") {
            $_SESSION['pay'] = 1;//未入金のみ表示
            header('Location: s_pay_search.php');
            exit();
        }elseif ($choice == "入金分のみ表示") {
            $_SESSION['pay'] = 2;//入金分のみ表示
            header('Location: s_pay_search.php');
            exit();
        }
}

// ＤＢと接続
$pdo = connect();

$nameArray = $_SESSION['nameArray'];
$seminaridArray = $_SESSION['seminaridArray'];
$countMax = $_SESSION['countMax'];

if (isset($_POST['end']) && $_POST['end'] !== "") {
    if ($_POST['end'] == "e"){
      header('Location: s_receipt.php');
      exit();
  }else{
      header('Location: s_pay_select.php?error=' . urlencode('終了にe以外が入力されています。')); 
        exit();
      
    }
}

if (isset($_POST['more']) && $_POST['more'] !== '') {
    if ($_POST['more'] == "m"){
       header('Location: s_pay_in.php?message=' . urlencode('再検索です。'));
        exit();
  }else{
      header('Location: s_pay_select.php?error=' . urlencode('他の検索にm以外が入力されています。'));
      exit();
    }
}

if (is_numeric($_POST['count']) && $_POST['count'] !== '') {
    $count = $_POST['count'];
} else {
    header('Location: s_pay_select.php?error=番号を小文字で入力してください。');
    exit();
}
if ($countMax < $count){
    header('Location: s_pay_select.php?error=番号が表示された番号の最大値を超えてます。');
    exit();
}
if (preg_match('/\A\d{4}\/\d{2}\/\d{2}\z/', $_POST['paid']) && ($_POST['paid'] !== '')){
    $paid = $_POST['paid'];
} else {
    
    header('Location: s_pay_select.php?error=入金日を小文字yyyy/mm/dd形式で入力してください');
    
    exit();
}
$name = $nameArray[$count];
$seminarid = $seminaridArray[$count];
$paidTag = 1;
uketukebo_set($pdo, $name, $seminarid, $paid,$paidTag);


//セッションを閉じる
session_destroy();
$_SESSION['nameArray'] = [];
$_SESSION['seminaridArray'] = [];
$_SESSION['officeArray'] = [];
$_SESSION['withtaxArray'] = [];
$_SESSION['paidArray'] = [];
$_SESSION['noteArray'] = [];
$pdo = null;
if ($paidTag == 2){
    $error = htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "は" . htmlspecialchars($paid, ENT_QUOTES, 'UTF-8') . "に既に入金されています";
    header('Location: s_pay_in.php?error=' . urlencode($error));
    exit();
} else {
    $message = $name . "の入金を問題なく処理しました";
    header('Location: s_pay_in.php?message=' . urlencode($message));
    exit();
}

?>
