<?php
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

//氏名で正会員TBを検索し、該当するレコードを表示する
function search_byname($pdo, $kensakuName) {
    $stmt = $pdo->prepare('SELECT name, office, fee, fee1,fee2, note FROM regular WHERE name LIKE :name AND withdraw = 1');
    $stmt->bindValue(':name', "%" . $kensakuName . "%", PDO::PARAM_STR);
    $stmt->execute();
    
    $count = 0;
    $kensakuNameArray = [];
    $kensakuOfficeArray = [];
    $feeArray = [];
    $fee1Array = [];
    $fee2Array = [];
    $noteArray = [];

    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        $kensakuNameArray[] = $row['name'];
        $kensakuOfficeArray[] = $row['office'];
        if ($row['fee'] == NULL){
            $feeArray[] = "";
        }else{
            $feeArray[] = $row['fee'];
        }
        if ($row['fee1'] == NULL){
            $fee1Array[] = "";
        }else{
            $fee1Array[] = $row['fee1'];
        }
        if ($row['fee2'] == NULL){
            $fee2Array[] = "";
        }else{
            $fee2Array[] = $row['fee2'];
        }
        if ($row['fee3'] == NULL){
            $fee3Array[] = "";
        }else{
            $fee3Array[] = $row['fee3'];
        }
    
       
        $noteArray[] = $row['note'];
    }
    
    if ($count == 0) {
        $message = $kensakuName . "は正会員マスターに存在しません。";
        header('Location: r_pay_in.php?error=' . urlencode($message));
        exit();
    }

   
    $_SESSION['nameArray'] = $kensakuNameArray;
    $_SESSION['officeArray'] = $kensakuOfficeArray;
    $_SESSION['feeArray'] = $feeArray;
    $_SESSION['fee1Array'] = $fee1Array;
    $_SESSION['fee2Array'] = $fee2Array;
    $_SESSION['noteArray'] = $noteArray;
    $_SESSION['maxcount'] = $count;
    //137行目で既に入れているので不要　$_SESSION['kensakuName'] = $kensakuName;
}

function search_byoffice($pdo, $kensakuOffice) {
    $stmt = $pdo->prepare('SELECT name, office, fee, fee1,fee2, note FROM regular WHERE office LIKE :office AND withdraw = 1');
    $stmt->bindValue(':office', "%" . $kensakuOffice . "%", PDO::PARAM_STR);
    $stmt->execute();
    
    $count = 0;
    $kensakuNameArray = [];
    $kensakuOfficeArray = [];
    $feeArray = [];
    $fee1Array = [];
    $fee2Array = [];
    $noteArray = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        $kensakuNameArray[] = $row['name'];
        $kensakuOfficeArray[] = $row['office'];
        if ($row['fee'] == NULL){
            $feeArray[] = "";
        }else{
            $feeArray[] = $row['fee'];
        }
        if ($row['fee1'] == NULL){
            $fee1Array[] = "";
        }else{
            $fee1Array[] = $row['fee1'];
        }
        if ($row['fee2'] == NULL){
            $fee2Array[] = "";
        }else{
            $fee2Array[] = $row['fee2'];
        }
    
       
        $noteArray[] = $row['note'];
    }
    
    if ($count == 0) {
        $message = $kensakuOffice . "は正会員マスターに存在しません。";
        header('Location: r_pay_in.php?error=' . urlencode($message));
        exit();
    }

    $_SESSION['nameArray'] = $kensakuNameArray;
    $_SESSION['officeArray'] = $kensakuOfficeArray;
    $_SESSION['feeArray'] = $feeArray;
    $_SESSION['fee1Array'] = $fee1Array;
    $_SESSION['fee2Array'] = $fee2Array;
    $_SESSION['noteArray'] = $noteArray;
    $_SESSION['maxcount'] = $count;
    //144行目で入れているので不要　$_SESSION['kensakuOffice'] = $kensakuOffice;
   
}

//正会員TBより該当データを探す
function regular_search($pdo, $kensakuName, $kensakuOffice) {
    //氏名の中のブランクを削除　矢島　重比古　⇒　矢島重比古
    if ($kensakuName !== "") {
        $string = $kensakuName;
        $kensakuName = str_replace([' ', '　'], '', $string);
        $_SESSION['kensakuName'] = $kensakuName;
        search_byname($pdo, $kensakuName);
    //会社名の中の”株式会社”、”㈱”、”（株）”、ブランクを全て削除
    } elseif ($kensakuOffice !== "") {
        $originalString = $kensakuOffice;
        $removeString = ["株式会社", "㈱", "（株）", " "];
        $kensakuOffice = str_replace($removeString, "", $originalString);
        $_SESSION['kensakuOffice'] = $kensakuOffice;
        search_byoffice($pdo, $kensakuOffice);
    }
}

//------－メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
//終了処理
$_GET['error'] = NULL;
$_GET['message'] = NULL;
if (isset($_POST['end']) && $_POST['end'] !== "") {
    if ($_POST['end'] == "e") {
        
        header('Location: r_receipt.php');
        exit();
    }else{
        header('Location: r_pay_in.php?error=' . urlencode('終了にe以外の文字を入力しています。'));
        exit();
    }
}

//インプットチェック
//sessionの開始
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//ＤＢと接続
$pdo = connect();
$kensakuName = "";
$kensakuOffice = "";
if (isset($_POST['name']) && $_POST['name'] !== "") {
    $kensakuName = $_POST['name'];  
}
if (isset($_POST['office']) && $_POST['office'] !== "") {
    $kensakuOffice = $_POST['office']; 
}

if ($kensakuName == "" && $kensakuOffice == "") {
   
    header('Location: r_pay_in.php?error=' . urlencode('氏名又は会社名のどちらかを入力してください。'));
    exit();
}

if ($kensakuName !== "" && $kensakuOffice !== "") {
    
    header('Location: r_pay_in.php?error=' . urlencode('氏名又は会社名のどちらかを入力してください。両方は入りません。'));
    exit();
}
regular_search($pdo, $kensakuName, $kensakuOffice);


$pdo = NULL;
header('Location: r_pay_select.php');
exit();
?>