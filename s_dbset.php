<?php

// セッションスタート
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 外部サブルーティンの読み込み
require_once './s_jmca_function.php';

$seminarId = $_SESSION['seminarId'];
$seminarName = $_SESSION['seminarName'];
$plural = $_SESSION['plural'];
if (empty($seminarId) || empty($seminarName) || empty($plural)) {
    echo "セミナーテーブルが間違っています。修正が必要です。セミナーID ＝".$seminarId;
    exit;
}


// スプレッドシートの準備
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet1 = new Spreadsheet();
$sheet1 = $spreadsheet1->getActiveSheet();
// ヘッダーの設定
$sheet1->setCellValue('A1', 'メールアドレス');
$sheet1->setCellValue('B1', '氏名');
$sheet1->setCellValue('C1', 'セミナーID');
$sheet1->setCellValue('D1', '会社名');
$sheet1->setCellValue('E1', '区分');
$sheet1->setCellValue('F1', 'エラーメッセージ');
$sheet1->setCellValue('G1', '会員id');
$row = 2;

// DBと接続
$pdo = connect();

// 処理csvファイル名の確定
if (!isset($_POST['count'])) {
    header('Location: s_getFilename.php?error=番号が選択されていません。');
    exit;
}

$count = $_SESSION['count'];
$value = $_POST['count'];
if ($value > $count || !isset($_SESSION['filename'][$value])) {
    header('Location: s_getFilename.php?error=番号が最大値を超えています。');
    exit;
}

$arrayFilename = $_SESSION['filename'];
$filename = $arrayFilename[$value];

// CSVファイルを読み込み受付簿DBを更新する
$file = "./aaaa/受付簿/$filename";
$f = fopen($file, "r");
if ($f === false) {
    die("Error: Unable to open file $file");
}

// CSVファイルの処理
$data = fgetcsv($f); // 1行目スキップ
while ($data = fgetcsv($f)) {
    // 料金検索
    $tax = null;
    $withtax = null;
    $id1 = null;
    $id2 = null;
    $kaiinId = null;
    $billmeans = null;
    $receiptmeans = null;
    $kubun = $data[5];
    $regularFee = 0;
    $supportFee = 0;
    //pluralとCSVファイルのフォーマットが一致するかチェック
    $sw = 0;
    if ($plural == 2){
        if (preg_match('/郵送/', $data[6])){
            $sw = 1;
        }elseif (preg_match('/PDF/', $data[6])){
            $sw = 1;
        }elseif (preg_match('/不要/', $data[6])){
            $sw = 1;
    }
    }
    if ($plural == 1){
         if (preg_match('/のみ/', $data[6])) {
            $sw = 1;
    
        } elseif (preg_match('/まとめて/', $data[6])) {
            $sw = 1;
        } elseif (preg_match('/回目/', $data[6])) {
            $sw = 1;
        } 
    }
  
    if ($sw == 1){
        header('Location: s_id_in.php?error=入力したセミナーIDと選択した受付簿のフォーマットが異なります。初めからやり直してください。');
        exit; 
    }
    
    searchFee($pdo, $data, $plural, $notax, $tax, $withtax, $id1, $id2,$kubun);
    searchkaiinid($pdo, $data, $kaiinId, $regularFee, $supportFee);
    searchmeans($data, $plural, $billmeans, $receiptmeans);

    // 受付簿テーブルに同じレコードが存在するかチェック
    $shortname = str_replace([' ', '　'], '', $data[2]);
    $mail = $data[1];
    $paid = null;
    $duplicate = 0;

    oubo_1kenshori($pdo, $data, $plural, $notax, $tax, $withtax, $id1, $seminarId, $kaiinId, $billmeans, $receiptmeans,
                   $shortname, $seminarName, $mail, $kubun, $paid, $duplicate);

    // 重複レコードのチェック
    if ($duplicate == 1) {
        continue;
    }

//ワーニングメッセージの作成
//既に支払が済んでいるが、2回目の応募をしました
    if ($paid !== NULL){
        $sheet1->setCellValue('A'.$row,$data['1']);
        $sheet1->setCellValue('B'.$row,$data['2']);
        $sheet1->setCellValue('C'.$row,$seminarName);
        $sheet1->setCellValue('D'.$row,$data['3']);
        $sheet1->setCellValue('E'.$row,$data['5']);
        $sheet1->setCellValue('F'.$row, '既に支払が済んでいるが、２回目の応募をしました。');
        $row++;
    }
    

//区分を正会員、賛助会員で応募したが、正会員マスタ、賛助会員マスタに存在しない
    if ($kaiinId == 9999){
        $sheet1->setCellValue('A'.$row,$data['1']);
        $sheet1->setCellValue('B'.$row,$data['2']);
        $sheet1->setCellValue('C'.$row,$seminarName);
        $sheet1->setCellValue('D'.$row,$data['3']);
        $sheet1->setCellValue('E'.$row,$data['5']);
        if ($id1 == 1){
            $sheet1->setCellValue('F'.$row, '正会員マスターに存在しません。'); 
        }else{
            $sheet1->setCellValue('F'.$row, '賛助会員マスターに存在しません。');
        } 
        $row++;
    }   
    
    
//正会員マスター、賛助会員マスターには存在するが今期の年会費未納者

    if ($regularFee == 1 || $supportFee == 1){
        $sheet1->setCellValue('A'.$row,$data['1']);
        $sheet1->setCellValue('B'.$row,$data['2']);
        $sheet1->setCellValue('C'.$row,$seminarName);
        $sheet1->setCellValue('D'.$row,$data['3']);
        $sheet1->setCellValue('E'.$row,$data['5']);
        if ($regularFee == 1){
             $sheet1->setCellValue('F'.$row, '正会員年会費未払いです。'); 
        }else{
             $sheet1->setCellValue('F'.$row, '賛助会員年会費未払いです。');
        } 
        $row++;
    }   

//複数に一括応募処理
    if ($plural == 2) {
        if ($data[14] == "複数人同時応募") {  // 比較には '==' を使用 
            if ($id1 == 2 || $id1 == 3) {  // 論理ORには '||' を使用　$id1は正会員当の区分　2;正会員　3:賛助会員
                $n = 15;
                while (isset($data[$n]) && $data[$n] !== "") {
                    $shortname = str_replace([' ', '　'], '', $data[$n]);
                    $mail = $data[$n + 1];
                    $kubun = $data[$n + 2];
                    searchFee($pdo, $data, $plural, $notax, $tax, $withtax, $id1, $id2,$kubun);
                    $paid = NULL;
                    $duplicate = 0;
                    oubo_1kenshori($pdo, $data, $plural, $notax, $tax, $withtax, $id1, $seminarId, $kaiinId, $billmeans, $receiptmeans, $shortname, $seminarName, $mail,$kubun,$paid,$duplicate);
                
                    // 重複レコードのチェック
                    if ($duplicate == 1) {
                        $n = $n + 3;
                        continue;
                    }
                
                    if ($paid !== NULL){
                        $sheet1->setCellValue('A'.$row,$mail);
                        $sheet1->setCellValue('B'.$row,$shortname);
                        $sheet1->setCellValue('C'.$row,$seminarName);
                        $sheet1->setCellValue('D'.$row,$data['3']);
                        $sheet1->setCellValue('E'.$row,$kubun);
                        $sheet1->setCellValue('F'.$row, '既に支払が済んでいるが、２回目の応募をしました。');
                        $row++;  
                    }
                    if ($kaiinId == 9999){
                        $sheet1->setCellValue('A'.$row,$mail);
                        $sheet1->setCellValue('B'.$row,$shortname);
                        $sheet1->setCellValue('C'.$row,$seminarName);
                        $sheet1->setCellValue('D'.$row,$data['3']);
                        $sheet1->setCellValue('E'.$row,$kubun);
                        if ($id1 == 1){
                            $sheet1->setCellValue('F'.$row, '正会員マスターに存在しません。'); 
                        }else{
                            $sheet1->setCellValue('F'.$row, '賛助会員マスターに存在しません。');
                        }
                        $row++;
                    }
                    if ($n > 23 && $kubun == "賛助会員（枠内）"){
                        $sheet1->setCellValue('A'.$row,$mail);
                        $sheet1->setCellValue('B'.$row,$shortname);
                        $sheet1->setCellValue('C'.$row,$seminarName);
                        $sheet1->setCellValue('D'.$row,$data['3']);
                        $sheet1->setCellValue('E'.$row,$kubun);
                        $sheet1->setCellValue('F'.$row, '枠超過ではないですか。');
                        $row++;  
                    }
                    $n = $n + 3;
                }
            }
        }
    }



        if ($plural == 1) {
            if ($data[13] == "複数人同時応募") {  // 比較には '==' を使用
                if ($id1 == 2 || $id1 == 3) {  // 論理ORには '||' を使用 $id1＝区分　＄id2＝参加回数 賛助会員だったら
                    $n = 14;
                    //
                    while (!empty($data[$n])) {
                        $shortname = isset($data[$n]) ? str_replace([' ', '　'], '', $data[$n]) : "";
                        $mail      = $data[$n + 1] ?? "";
                        $kubun     = $data[$n + 2] ?? "";
                        searchFee($pdo, $data, $plural, $notax, $tax, $withtax, $id1, $id2,$kubun);
                        $paid = NULL;
                        $duplicate = 0;
                        oubo_1kenshori($pdo, $data, $plural, $notax, $tax, $withtax, $id1, $seminarId, $kaiinId, $billmeans, $receiptmeans, $shortname, $seminarName, $mail,$kubun,$paid,$duplicate);
                        
                        // 重複レコードのチェック
                        if ($duplicate == 1) {
                            $n = $n + 3;
                            continue;
                        }
                        
                        if ($paid !== NULL){
                            $sheet1->setCellValue('A'.$row,$mail);
                            $sheet1->setCellValue('B'.$row,$shortname);
                            $sheet1->setCellValue('C'.$row,$seminarName);
                            $sheet1->setCellValue('D'.$row,$data['3']);
                            $sheet1->setCellValue('E'.$row,$kubun);
                            $sheet1->setCellValue('F'.$row, '既に支払が済んでいるが、２回目の応募をしました。');
                            $row++;
                        }
                        if ($kaiinId == 9999){
                            $sheet1->setCellValue('A'.$row,$mail);
                            $sheet1->setCellValue('B'.$row,$shortname);
                            $sheet1->setCellValue('C'.$row,$seminarName);
                            $sheet1->setCellValue('D'.$row,$data['3']);
                            $sheet1->setCellValue('E'.$row,$kubun);
                            if ($id1 == 1){
                                $sheet1->setCellValue('F'.$row, '正会員マスターに存在しません。'); 
                            }else{
                                $sheet1->setCellValue('F'.$row, '賛助会員マスターに存在しません。');
                            }
                            $row++;
                            }
                        if ($n > 22 && $kubun == "賛助会員（枠内）"){
                            $sheet1->setCellValue('A'.$row,$mail);
                            $sheet1->setCellValue('B'.$row,$shortname);
                            $sheet1->setCellValue('C'.$row,$seminarName);
                            $sheet1->setCellValue('D'.$row,$data['3']);
                            $sheet1->setCellValue('E'.$row,$kubun);
                            $sheet1->setCellValue('F'.$row, '枠超過ではないですか。');
                            $row++;  
                        }
                        $n = $n + 3;
                        }
                    }
                }
            }
}

//エラーメッセージをexelにアウトプット

        date_default_timezone_set('Asia/Tokyo');//標準時間を日本に合わせる
        if ($row > 2){
        $writer1 = new Xlsx($spreadsheet1);
        $now = date('Y-m-d-H-i-s');
        $filename ="./aaaa/受付簿警告リスト/error_$now.xlsx";
        $writer1->save($filename);
        }
       
    

// Excelファイルを保存
date_default_timezone_set('Asia/Tokyo');
if ($row > 2) {
    $writer1 = new Xlsx($spreadsheet1);
    $now = date('Y-m-d-H-i-s');
    $filename = "./aaaa/受付簿警告リスト/error_$now.xlsx";
    $writer1->save($filename);
}

// CSVファイルを閉じて削除
fclose($f);
//CSVファイルが残るよう変更（削除しない）
/* if (unlink($file)) {
    echo "受付簿CSVファイルが削除されました。";
} else {
    echo "受付簿CSVファイルの削除に失敗しました。";
}  */

// DBを閉じる
$pdo = null;

header('Location:s_atenaConfirm.php');
exit();

?>
