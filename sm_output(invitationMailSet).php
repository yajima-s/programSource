<?php
// セミナーIDと何回目から、受付簿から参加者のメールADを取得しExcelに出力



// DBに接続
function connect() {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
}


// メインルーチン
// セッションスタート
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

$seminarId = $_SESSION['seminarId'];
$seminarName = $_SESSION['seminarName'];
$plural = $_SESSION['plural'];
$kaisu = $_SESSION['kaisu'];
$id1 = $_SESSION['id1'];
$id2 = $_SESSION['id2'];
$id3 = $_SESSION['id3'];
$id4 = $_SESSION['id4'];

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet1 = new Spreadsheet();
$sheet1 = $spreadsheet1->getActiveSheet();

// ヘッダーの設定
$sheet1->setCellValue('A1', 'メールアドレス');
$sheet1->setCellValue('B1', '氏名');
try {
    $pdo = connect();
} catch (PDOException $e) {
    echo "DB接続に失敗しました。" . $e;
    exit;
}

$row = 2;

//単発セミナーの処理
if ($plural == 1){
    $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
    $stmt->bindValue(':seminarid', $seminarId, PDO::PARAM_INT);
    $stmt->execute();
    while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($data['invitationMail1'] == null) {
            $sheet1->setCellValue('A' . $row, $data['mail']);
            $sheet1->setCellValue('B' . $row, $data['name']);
            $row++; 
            
            // メール送信済みタグをセット
            $invitation = 1;
            $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail1 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
            $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
            $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
            $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
            $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
            $stmt1->execute();        
            
    }
    }


//複数回セミナーの処理
}elseif($plural ==2){
    
    //対象セミナーが1回目の場合
    if ($kaisu == 1){
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id1, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail1'] == null) {
                if (preg_match('/まとめて|１回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail1 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
    }
    
    //対象セミナーが2回目の場合
    if ($kaisu == 2){
        //1回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id1, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail2'] == null) {
                if (preg_match('/まとめて|２回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail2 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
        
        //2回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id2, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail2'] == null) {
                if (preg_match('/まとめて|２回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 
                    
                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMai2 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
    }
        
    //対象セミナーが3回目の場合
    if ($kaisu == 3){
        //1回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id1, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail3'] == null) {
                if (preg_match('/まとめて|３回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail3 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
        
        //2回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id2, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail3'] == null) {
                if (preg_match('/まとめて|２回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail3 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
        
        //3回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id3, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail3'] == null) {
                if (preg_match('/まとめて|３回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail3 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
    }
    
    //対象セミナーが４回目の場合
    if ($kaisu == 4){
        //1回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id1, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail4'] == null) {
                if (preg_match('/まとめて|４回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail4 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
        
        //2回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id2, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail4'] == null) {
                if (preg_match('/まとめて|４回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail4 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
        
        //3回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id3, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail4'] == null) {
                if (preg_match('/まとめて|４回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail4 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        }
        
       //4回目のセミナーidのレコード処理
        $stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE seminarid = :seminarid');
        $stmt->bindValue(':seminarid', $id4, PDO::PARAM_INT);
        $stmt->execute();
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($data['invitationMail4'] == null) {
                if (preg_match('/まとめて|４回目/', $data['multi'])){
                    $sheet1->setCellValue('A' . $row, $data['mail']);
                    $sheet1->setCellValue('B' . $row, $data['name']);
                    $row++; 

                    // メール送信済みタグをセット
                    $invitation = 1;
                    $stmt1 = $pdo->prepare("UPDATE uketukebo SET invitationMail4 = :invitation WHERE name = :name AND mail = :mail AND seminarid = :seminarid");
                    $stmt1->bindValue(':name', $data['name'], PDO::PARAM_STR);
                    $stmt1->bindValue(':mail', $data['mail'], PDO::PARAM_STR);
                    $stmt1->bindValue(':seminarid', $data['seminarid'], PDO::PARAM_INT);
                    $stmt1->bindValue(':invitation', $invitation, PDO::PARAM_INT);
                    $stmt1->execute();        
                }
            }
        } 
    }
}



// Excelファイルとして保存
date_default_timezone_set('Asia/Tokyo'); // 標準時間を日本に合わせる
$writer1 = new Xlsx($spreadsheet1);
$now = date('Y-m-d-H-i-s');
$filename = "./aaaa/参加者メール一覧/mail_$now.xlsx";
$writer1->save($filename);

$row = $row - 2;
echo $seminarName . "の参加者人数は　" . $row . "です。";

$_SESSION = [];
session_destroy();
?>








