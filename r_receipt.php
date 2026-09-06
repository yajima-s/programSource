<?php
//サブルーチン

//和暦への変更
function convertToJapaneseEra($date) {
    date_default_timezone_set('Asia/Tokyo'); // タイムゾーンを日本に設定
    $datetime = new DateTime($date ?? 'now');
    $year = (int)$datetime->format('Y');
    $month = $datetime->format('m');
    $day = $datetime->format('d');

    if ($year >= 2019) {
        $era = '令和';
        $eraYear = $year - 2018;
    } elseif ($year >= 1989) {
        $era = '平成';
        $eraYear = $year - 1988;
    } elseif ($year >= 1926) {
        $era = '昭和';
        $eraYear = $year - 1925;
    } elseif ($year >= 1912) {
        $era = '大正';
        $eraYear = $year - 1911;
    } else {
        $era = '明治';
        $eraYear = $year - 1867;
    }

    return sprintf('%s%d年%s月%s日', $era, $eraYear, $month, $day);
}

//メインルーチン
require_once('tcpdf/tcpdf.php');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



// 新しいスプレッドシートを作成
$spreadsheet1 = new Spreadsheet();
$spreadsheet2 = new Spreadsheet();
$sheet1 = $spreadsheet1->getActiveSheet();
$sheet2 = $spreadsheet2->getActiveSheet();

// ヘッダーの設定
$sheet1->setCellValue('A1', 'メールアドレス');
$sheet1->setCellValue('B1', '氏名');
$sheet1->setCellValue('C1', '会社名');
$sheet2->setCellValue('A1', '郵便番号');
$sheet2->setCellValue('B1', '住所');
$sheet2->setCellValue('C1', 'ビル名');
$sheet2->setCellValue('D1', '会社名');
$sheet2->setCellValue('E1', '部署');
$sheet2->setCellValue('F1', '氏名');

$row1 = 2;
$row2 = 2;

try {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

//年度の設定
$stmt2 = $pdo->prepare("SELECT * FROM nendo");
$stmt2->execute();
$nendRow = $stmt2->fetch(PDO::FETCH_ASSOC);
$feeNendo = $nendRow['fee'];
$fee1Nendo = $nendRow['fee1'];
$fee2Nendo = $nendRow['fee2']; 

$error = 0;//receiptsendがNULLなのに、fee,fee1,fee2共にNULLの場合：入金の事実がない

// トランザクションスタート
$pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM regular WHERE receiptsend IS NULL ");
    $stmt->execute();


    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as $record) {
        
        

        // PDFの生成
        $pdf = new TCPDF();
        $pdf->AddPage();

        // フォントの追加
        $fontPath = './tcpdf/fonts/ipaexg.ttf'; // フォントファイルのパスを指定
        $fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
        $pdf->SetFont($fontname, '', 10);

        if (!empty($record['fee'])) {
        $wareki = convertToJapaneseEra($record['fee']);
        $nendo = $feeNendo;
        }elseif (!empty($record['fee1'])){
                $wareki = convertToJapaneseEra($record['fee1']);
                $nendo = $fee1Nendo;
        }elseif(!empty($record['fee2'])){
                $wareki = convertToJapaneseEra($record['fee2']);
                $nendo = $fee2Nendo;
        }else{
            echo "{$record['name']}{$record['office']}.は、fee,fee1,fee2とも入金実績がありません。正会員TBを確認してください。";
            
            //領収書対象とならないように、receiptsendに2020-01-01のダミーひふけを入れる
            $stmt3 = $pdo->prepare('UPDATE regular SET modified = CURRENT_TIMESTAMP,receiptsend = "2020-01-01"  
            WHERE id = :id');
            $stmt3->bindValue(':id', $record['id'], PDO::PARAM_INT);
            $stmt3->execute();
            $error++;
            continue;

        }
       
        $nendo1 = $nendo +1;
        
        // Excelファイルの書き込み
        $file_name = "";
        if ($record['receiptmeans'] == 1) {
            $file_name = "jpeg-0001.jpg";
            $sheet1->setCellValue('A' . $row1, $record['mail']);
            $sheet1->setCellValue('B' . $row1, $record['name']);
            $sheet1->setCellValue('C' . $row1, $record['office']);
            $row1++;
        } elseif($record['receiptmeans'] == 2){
            $file_name = "jpeg-0002.jpg";
            $sheet2->setCellValue('A' . $row2, $record['post']);
            $sheet2->setCellValue('B' . $row2, $record['address']);
            $sheet2->setCellValue('C' . $row2, $record['bilname']);
            $sheet2->setCellValue('D' . $row2, $record['office']);
            $sheet2->setCellValue('E' . $row2, $record['busho']);
            $sheet2->setCellValue('F' . $row2, $record['name']);
            $row2++;
        } else {
            continue;
        }
        
        // HTMLコンテンツを設定
        $html = <<<EOF
<style>
    h1 { font-size: 26px;text-align: center; }
    h2 {font-size:20px;text-align: center;text-decoration: underline;} 
    .center {text-align: center;}
    .right{text-align: right;}
   
</style>
<h1>領&nbsp;&nbsp;&nbsp;収&nbsp;&nbsp;&nbsp;書</h1>
<body>
    <p><br><br><br></p>
    <h2>{$record['atena']}</h2>
    <p><br><br><br></p>
    <h2>￥８，０００円</h2>
    <p><br><br><br></p>
    <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;但し、{$nendo}年度　（会期：{$nendo}年4月～{$nendo1}年３月）{$record['name']}様分</p>
    <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;正会員年会費として受領いたしました。</p>
    <p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;※年会費には消費税は適用されません。</p>
    <p></p>
    <p></p>
    <p></p>
    <p></p>
    <p></p>
    <p class="center">{$wareki}</p>
    
    <p style="text-align: right;">
    <img src="c:/xampp/htdocs/jmca/imageF/{$file_name}" alt={$file_name} width="300" height="120">
    </p>
    <p class="right">登録番号　　T2010005010157</p>
    
</body>
EOF;

        $pdf->writeHTML($html);
        if ($record['receiptmeans'] == 1) {
            $pdfFile = 'receiptpdf_' . $record['name'] . '様.pdf';
            $pdf->Output('c:/xampp/htdocs/jmca/aaaa/年会費領収書/pdf/' . $pdfFile, 'F');
        } else {
            $pdfFile = 'receiptyuubin_' . $record['name'] . '様.pdf';
        $pdf->Output('c:/xampp/htdocs/jmca/aaaa/年会費領収書/yuubin/' . $pdfFile, 'F');  
        }   

//regularのreceiptsend（領収書送付日)を記載
        
    $stmt1 = $pdo->prepare('UPDATE regular SET modified = CURRENT_TIMESTAMP,receiptsend = :receiptsend 
    WHERE name = :name');
        $stmt1->bindValue(':name', $record['name'], PDO::PARAM_STR);
        date_default_timezone_set('Asia/Tokyo'); // タイムゾーンを日本に設定
        $today = date('Y-m-d');
        $stmt1->bindValue(':receiptsend', $today, PDO::PARAM_STR);
        $stmt1->execute();
        
    }
    // Excelファイルとして保存
        date_default_timezone_set('Asia/Tokyo');//標準時間を日本に合わせる
        if ($row1 > 2){
        $writer1 = new Xlsx($spreadsheet1);
        $now = date('Y-m-d-H-i-s');
        $filename ="./aaaa/年会費領収書/pdf/receiptpdf_$now.xlsx";
        $writer1->save($filename);
        }
        if ($row2 >2){
        $writer2 = new Xlsx($spreadsheet2);
        $now = date('Y-m-d-H-i-s');
        $filename ="./aaaa/年会費領収書/yuubin/receiptyuubin_$now.xlsx";
        $writer2->save($filename);
        }
    
        $row1 = $row1-2;
        echo "PDF領収書は".$row1."枚です。";
        $row2 = $row2 - 2;
        echo "紙の領収書は".$row2."枚です。";
        echo "fee,fee１,fee2共に日付が入ってなく(入金がなく）receiptsendがNULLのレコード数＝".$error."です。";
 $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "DB処理にエラーが発生しました。DBは更新されません。" . $e->getMessage();
}   



?>

