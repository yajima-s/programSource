<?php
//サブルーチン
//セミナー実施日取得
function getJisshibi($pdo, $seminarid, &$jisshibi1, &$jisshibi2, &$jisshibi3) {
    $stmt = $pdo->prepare("SELECT * FROM seminar WHERE id = :seminarid LIMIT 1");
    $stmt->bindParam(':seminarid', $seminarid, PDO::PARAM_INT);
    $stmt->execute();
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($record['jisshibi1'] !== null) {
        $jisshibi1 = $record['jisshibi1'];
    }
    if ($record['jisshibi2'] !== null) {
        $jisshibi2 = $record['jisshibi2'];
    }
    if ($record['jisshibi3'] !== null) {
        $jisshibi3 = $record['jisshibi3'];
    }
}

//和暦への変更
function convertToJapaneseEra($date) {
    date_default_timezone_set('Asia/Tokyo'); // タイムゾーンを日本に設定
    $datetime = new DateTime($date);
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

//名前に危険な文字が入っている場合のエスケープ処理
function safeFileName($name) {
    // 禁止文字をアンダースコアに置換
    $name = str_replace(
        ['\\', '/', ':', '*', '?', '"', '<', '>', '|'],
        '_',
        $name
    );

    // 制御文字などを除去
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);

    // 前後の空白を除去
    return trim($name);
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
    $sheet1->setCellValue('B1', '参加者');
    $sheet1->setCellValue('C1', '会社名');
    $sheet2->setCellValue('A1', '郵便番号');
    $sheet2->setCellValue('B1', '住所');
    $sheet2->setCellValue('C1', '建物');
    $sheet2->setCellValue('D1', '会社名');
    $sheet2->setCellValue('E1', '所属');
    $sheet2->setCellValue('F1', '参加者');
    

    $row1 = 2;
    $row2 = 2;
try {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

//トランザクションスタート
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("SELECT * FROM uketukebo WHERE paid IS NOT NULL AND receiptsend IS NULL AND receiptmeans != 3");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as $record) {
        $file_name = "";
        //exelファイルの書き込み
        if ($record['receiptmeans'] == 1){
        $file_name = "jpeg-0001.jpg";
        $sheet1->setCellValue('A' . $row1, $record['mail']);
        $sheet1->setCellValue('B' . $row1, $record['name']);
        $sheet1->setCellValue('C' . $row1, $record['office']);
        $row1++;
        }else{
        $file_name = "jpeg-0002.jpg";    
        $sheet2->setCellValue('A' . $row2, $record['post']);
        $sheet2->setCellValue('B' . $row2, $record['address']);
        $sheet2->setCellValue('C' . $row2, $record['bilname']);
        $sheet2->setCellValue('D' . $row2, $record['office']);    
        $sheet2->setCellValue('E' . $row2, $record['busho']);    
        $sheet2->setCellValue('F' . $row2, $record['name']);        
        $row2++;    
        }
        
        
        
        // PDFの生成
        $pdf = new TCPDF();
        $pdf->AddPage();

        // フォントの追加
        $fontPath = './tcpdf/fonts/ipaexg.ttf'; // フォントファイルのパスを指定
        $fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
        $pdf->SetFont($fontname, '', 10);
        
        $wareki = convertToJapaneseEra($record['paid']);
        $formattedAmountWithtax = "￥" . number_format($record['withtax']);
        $formattedAmountNotax = "￥" . number_format($record['notax']);
        $formattedAmountTax = "￥" . number_format($record['tax']);

        $jisshibi1 = null;
        $jisshibi2 = null;
        $jisshibi3 = null;
        $seminarid = $record['seminarid'];
        getJisshibi($pdo, $seminarid, $jisshibi1, $jisshibi2, $jisshibi3);

        $kubun = "";
        switch ($record['kubun']) {
            case 1:
                $kubun = "正会員";
                break;
            case 2:
                $kubun = "賛助会員（枠内）";
                break;
            case 3:
                $kubun = "賛助会員（枠超過）";
                break;
            case 4:
                $kubun = "一般";
                break;
            case 5:
                $kubun = "学生";
                break;
            case 9:
                $kubun = "役員";
                break;
        }



 // HTMLコンテンツを設定
$html = <<<EOF
<style>
    h1 { font-size: 24px;text-align: center; }
    .green1 { background-color:#ccffcc;
        display: inline-block;text-align: center;width: auto; }
    .green2 { background-color: #ccffcc;
        display: inline-block; text-align: center;width: auto;}
    .right {text-align: right;}
    .underline {text-decoration: underline;}
</style>
<h1 class="green1">領&nbsp;&nbsp;&nbsp;収&nbsp;&nbsp;&nbsp;書</h1>
<body>
    <p class="right">{$wareki}</p>
    <h2 class="underline">{$record['atena']}</h2>
    <p>平素より格別のご高配を賜り、厚く御礼申し上げます。</p>
    <p>下記の金額を領収いたしました。</p>
    <p style="text-align: right; font-size ">
     <img src="c:/xampp/htdocs/jmca/imageF/{$file_name}" alt={$file_name} width="300" height="120">
    </p>
    <p class="right">登録番号　　T2010005010157</p>
    <br><br>
    <h2 class="underline">ご領収額 {$formattedAmountWithtax}円</h2>
    <p>10％対象 {$formattedAmountNotax}円&nbsp;消費税 {$formattedAmountTax}円</p>
    <table border="1">
        <tr> 
            <th> 開催日</th>
            <th> セミナー名</th>
            <th> 参加者</th>
            <th> 区分</th>
            <th> 税抜金額</th>
        </tr>
        <tr>
            <td> {$jisshibi1}<br> {$jisshibi2}<br> {$jisshibi3}</td>
            <td> {$record['seminarname']}</td>
            <td> {$record['name']}様</td>
            <td> {$kubun}</td>
            <td> {$formattedAmountNotax}円</td>
        </tr>
    </table>
    <p>{$record['multi']}</p>
    <br><br><br><br><br><br>
   

    <p class="green2">Japan Medical and Scientific Communicators Association (JMCA)</p>
 
</body>
EOF;

        $pdf->writeHTML($html);
         // タイムスタンプ
            $timestamp = date('Y_m_d_H_i_s');

            // 名前を安全化
            $safeName = safeFileName($record['name']);

        if ($record['receiptmeans'] == 1){
            // ファイル名作成
            $pdfFile = 'billpdf_' . $safeName . '様' . $timestamp . '.pdf';

            // PDF保存
            $pdf->Output('c:/xampp/htdocs/jmca/aaaa/セミナー領収書/pdf/' . $pdfFile, 'F');
            
        }else{
            $pdfFile = 'yuubinpdf_' . $safeName . '様' . $timestamp . '.pdf';
            $pdf->Output('c:/xampp/htdocs/jmca/aaaa/セミナー領収書/yuubin/' . $pdfFile, 'F');  
        }   

//uketukeboのreceiptsend（領収書送付日)を記載
        
    $stmt1 = $pdo->prepare('UPDATE uketukebo SET modified = CURRENT_TIMESTAMP,receiptsend = :receiptsend 
    WHERE name = :name and seminarid = :seminarid');
        $stmt1->bindValue(':name', $record['name'], PDO::PARAM_STR);
        $stmt1->bindValue(':seminarid', $record['seminarid'], PDO::PARAM_INT);
        date_default_timezone_set('Asia/Tokyo'); // タイムゾーンを日本時間に設定
        $today = date('Y-m-d');
        $stmt1->bindValue(':receiptsend', $today, PDO::PARAM_STR);
        $stmt1->execute();
        
    }
    // Excelファイルとして保存
        date_default_timezone_set('Asia/Tokyo');//標準時間を日本に合わせる
        if ($row1 > 2){
        $writer1 = new Xlsx($spreadsheet1);
        $now = date('Y-m-d-H-i-s');
        $filename ="./aaaa/セミナー領収書/pdf/receiptpdf_$now.xlsx";
        $writer1->save($filename);
        }
        if ($row2 >2){
        $writer2 = new Xlsx($spreadsheet2);
        $now = date('Y-m-d-H-i-s');
        $filename ="./aaaa/セミナー領収書/yuubin/receiptyuubin_$now.xlsx";
        $writer2->save($filename);
        }
    
        $row1 = $row1-2;
        echo "PDF領収書は".$row1."枚です。";
        $row2 = $row2 - 2;
        echo "紙の領収書は".$row2."枚です。";
    //DBの処理がすべてうまくいきました
    $pdo->commit();
    
} catch (PDOException $e) {
     $pdo->rollBack();
    echo "DB処理にエラーが発生しました。DBは更新されません。" . $e->getMessage();
}


?>
