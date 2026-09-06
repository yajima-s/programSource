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
        echo 'Connection failed:'.$e->getMassage();
        exit;
    }
}


//氏名で受付簿DBを検索し、該当するレコードを表示する
function search_byname($pdo,$kensakuName){
    //未入金分のみ表示
    if ($_SESSION['pay'] == 1){//未払い分のみ表示
        $stmt = $pdo->prepare('SELECT name,seminarid,office,kubun,withtax,paid,note FROM uketukebo WHERE name LIKE :name AND paid IS NULL');
        $stmt->bindValue(':name', "%" . $kensakuName . "%", PDO::PARAM_STR);
        $stmt->execute();
    //入金済み分のみ表示
    }elseif ($_SESSION['pay'] == 2){//支払済みのみ表示
        $stmt = $pdo->prepare('SELECT name,seminarid,office,kubun,withtax,paid,note FROM uketukebo WHERE name LIKE :name AND paid IS NOT NULL');
        $stmt->bindValue(':name', "%" . $kensakuName . "%", PDO::PARAM_STR);
        $stmt->execute();
    //入金分、未入金分全て表示
    }else{
        $stmt = $pdo->prepare('SELECT name,seminarid,office,kubun,withtax,paid,note FROM uketukebo WHERE name LIKE :name');
        $stmt->bindValue(':name', "%" . $kensakuName . "%", PDO::PARAM_STR);
        $stmt->execute();
    }
       
       $count = 0;
       $nameArray = [""];
       $seminaridArray = [0];
       $officeArray = [""];
       $withtaxArray = [0];
       $paidArray = [""];
       $noteArray =[""]; 
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        $nameArray[] = $row['name'];
        $seminaridArray[] = $row['seminarid'];
        $officeArray[] = $row['office'];
        $withtaxArray[] = $row['withtax'];
        $paidArray[] = $row['paid'];
        $noteArray[] = $row['note'];
        
        
        if ($count ==0) {
            if ($_SESSION['pay'] == 1){
                $message = $kensakuName . "の未入金レコードは受付簿に存在しません。";
                header('Location: s_pay_in.php?error=' . urlencode($message));
                exit();
        }elseif($_SESSION['pay'] == 2){
                $message = $kensakuName . "の入金済みレコードは受付簿に存在しません。";
                header('Location: s_pay_in.php?error=' . urlencode($message));
                exit();
        }else{
                $message = $kensakuName . "は受付簿に存在しません。";
                header('Location: s_pay_in.php?error=' . urlencode($message));
                exit();
        }
        }
    }

    $_SESSION['nameArray'] = $nameArray;
    $_SESSION['seminaridArray'] = $seminaridArray;
    $_SESSION['officeArray'] = $officeArray;
    $_SESSION['withtaxArray'] = $withtaxArray;
    $_SESSION['paidArray'] = $paidArray;
    $_SESSION['noteArray'] = $noteArray;
    $_SESSION['countMax'] = $count;
    

}

function search_byoffice($pdo,$kensakuOffice){
    if ($_SESSION['pay'] == 1){
        $stmt = $pdo->prepare('SELECT name,seminarid,office,kubun,withtax,paid,note FROM uketukebo WHERE office LIKE :office AND paid IS NULL');
        $stmt->bindValue(':office', "%" . $kensakuOffice . "%", PDO::PARAM_STR);
        $stmt->execute();
    }elseif ($_SESSION['pay'] == 2){
        $stmt = $pdo->prepare('SELECT name,seminarid,office,kubun,withtax,paid,note FROM uketukebo WHERE office LIKE :office AND paid IS NOT NULL');
        $stmt->bindValue(':office', "%" . $kensakuOffice . "%", PDO::PARAM_STR);
        $stmt->execute();
    }else{
        $stmt = $pdo->prepare('SELECT name,seminarid,office,kubun,withtax,paid,note FROM uketukebo WHERE office LIKE :office');
        $stmt->bindValue(':office', "%" . $kensakuOffice . "%", PDO::PARAM_STR);
        $stmt->execute();
    }
    
       $count = 0;
       $nameArray = [""];
       $seminaridArray = [0];
       $officeArray = [""];
       $withtaxArray = [0];
       $paidArray = [""];
       $noteArray =[""]; 

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        $nameArray[] = $row['name'];
        $seminaridArray[] = $row['seminarid'];
        $officeArray[] = $row['office'];
        $withtaxArray[] = $row['withtax'];
        $paidArray[] = $row['paid'];
        $noteArray[] = $row['note'];
 
        if ($count == 0) {
            if ($_SESSION['pay'] == 1){
                $message = $kensakuOffice . "の未入金レコードは受付簿に存在しません。";
                header('Location: s_pay_in.php?error=' . urlencode($message));
                exit();
        }elseif($_SESSION['pay'] == 2){
                $message = $kensakuOffice . "の入金済みレコードは受付簿に存在しません。";
                header('Location: s_pay_in.php?error=' . urlencode($message));
                exit();
        }else{
                $message = $kensakuOffice . "は受付簿に存在しません。";
                header('Location: s_pay_in.php?error=' . urlencode($message));
                exit();
        }
        
    }
}
    $_SESSION['nameArray'] = $nameArray;
    $_SESSION['seminaridArray'] = $seminaridArray;
    $_SESSION['officeArray'] = $officeArray;
    $_SESSION['withtaxArray'] = $withtaxArray;
    $_SESSION['paidArray'] = $paidArray;
    $_SESSION['noteArray'] = $noteArray;
    $_SESSION['countMax'] = $count;
}
   
//受付簿より該当データを探す
function uketukebo_search($pdo,$kensakuName,$kensakuOffice){
//氏名の中のブランクを削除　矢島　重比古　⇒　矢島重比古
if ($kensakuName !==""){
    $string = $kensakuName;
    $kensakuName = str_replace([' ', '　'], '', $string);
    $_SESSION['kensakuName'] = $kensakuName;
    search_byname($pdo,$kensakuName);

//会社名の中の”株式会社”、”㈱”、”（株）”、ブランクを全て削除
}elseif($kensakuOffice !==""){
    $originalString = $kensakuOffice;
    $removeString = "株式会社";
    $resultString = str_replace($removeString, "", $originalString);
    $originalString = $resultString;
    $removeString = "㈱";
    $resultString = str_replace($removeString, "", $originalString);
    $originalString = $resultString;
    $removeString = "（株）";
    $resultString = str_replace($removeString, "", $originalString);
    $originalString = $resultString;
    $removeString = " ";
    $kensakuOffice = str_replace($removeString, "", $originalString);
    $_SESSION['kensakuOffice'] = $kensakuOffice;
    search_byoffice($pdo,$kensakuOffice);
}
}


//------－メインルーチン------------------------------------------------------------------------
//終了処理

if (isset($_POST['end']) && $_POST['end'] !== "") {
    if ($_POST['end'] == "e"){
      header('Location: s_receipt.php');
      exit();
  }else{
      header('Location: s_pay_in.php?error=' . urlencode('終了にe以外が入力されています。')); 
        exit();
      
    }
}
  
//インプットチェック
//sessionの開始
if (session_status()===PHP_SESSION_NONE){
    session_start();
}

//ＤＢと接続
$pdo=connect();
$kensakuName = "";
$kensakuOffice = "";
if ($_SESSION['pay'] == 1 or $_SESSION['pay'] == 2){
    $kensakuName = $_SESSION['kensakuName'];
    $kensakuOffice = $_SESSION['kensakuOffice'];
}
if (isset($_POST['name']) && $_POST['name'] !== '') {
  $kensakuName = $_POST['name'];
  $_SESSION['kensakuName'] = $kensakuName;
  $_SESSION['kensakuOffice'] = $kensakuOffice;//""をが入る
  }
if (isset($_POST['office']) && $_POST['office'] !== '') {
  $kensakuOffice = $_POST['office']; 
  $_SESSION['kensakuOffice'] = $kensakuOffice;
  $_SESSION['kensakuName'] = $kensakuName;//""が入る
  }

if ($kensakuName == "" && $kensakuOffice == "") {
    header('Location: s_pay_in.php?error=' . urlencode('氏名又は会社名のどちらかを入力してください。'));
    exit();
}

if ($kensakuName !== "" && $kensakuOffice !== "") {
    header('Location: s_pay_in.php?error=' . urlencode('氏名又は会社名のどちらかを入力してください。両方は入りません。'));
    exit();
}

uketukebo_search($pdo,$kensakuName,$kensakuOffice);  

$pdo = NULL;
header('Location: s_pay_select.php');
exit();
?>
  
        

        