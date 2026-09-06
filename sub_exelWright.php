<?php


//メインルーチン
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


// 新しいスプレッドシートを作成
    $spreadsheet1 = new Spreadsheet();
    $sheet1 = $spreadsheet1->getActiveSheet();
    
// ヘッダーの設定
    $sheet1->setCellValue('A1', 'メールアドレス');
    $sheet1->setCellValue('B1', '参加者');
    $sheet1->setCellValue('C1', '会社名');
   
    
try {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}


try {
    $stmt = $pdo->prepare("SELECT * FROM uketukebo WHERE billsend IS NULL AND paid IS NULL AND kubun < 9");
    $stmt->execute();
    
    $row1 = 2;//exelの2行目より内容を書き込み　１行目はタイトル
    while ($records = $stmt->fetch(PDO::FETCH_ASSOC);) {
//exelファイルの書き込み
        $sheet1->setCellValue('A' . $row1, $record['mail']);
        $sheet1->setCellValue('B' . $row1, $record['name']);
        $sheet1->setCellValue('C' . $row1, $record['office']);
        $row1++;
        }   

// 日付入りExcelファイルとして保存
        date_default_timezone_set('Asia/Tokyo');//標準時間を日本に合わせる
        if ($row1 > 2){//exelに中身があれば
        $writer1 = new Xlsx($spreadsheet1);
        $now = date('Y-m-d-H-i-s');
        $filename ="./aaaa/セミナー請求書/pdf/billpdf_$now.xlsx";
        $writer1->save($filename);
        }
        
    
        $row1 = $row1-2;
        echo "処理件数は".$row1."枚です。";
        
    
} catch (PDOException $e) {
     $pdo->rollBack();
    echo "DB処理にエラーが発生しました。DBは更新されません。" . $e->getMessage();
}

?>
