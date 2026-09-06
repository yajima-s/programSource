<?php
function Wareki($date) {
    $eras = [
        ['name' => '令和', 'start' => '2019-05-01', 'offset' => 2018],
        ['name' => '平成', 'start' => '1989-01-08', 'offset' => 1988],
        ['name' => '昭和', 'start' => '1926-12-25', 'offset' => 1925],
        ['name' => '大正', 'start' => '1912-07-30', 'offset' => 1911],
        ['name' => '明治', 'start' => '1868-01-25', 'offset' => 1867]
    ];

    $dateTime = new DateTime($date);
    foreach ($eras as $era) {
        $start = new DateTime($era['start']);
        if ($dateTime >= $start) {
            $year = $dateTime->format('Y') - $era['offset'];
            $year = ($year == 1) ? '元' : $year;
            return $era['name'] . $year . '年' . $dateTime->format('m月d日');
        }
    }
    return null; // 明治以前の日付には対応していません
}

//メインルーチン
require_once('tcpdf/tcpdf.php');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $idArray = $_SESSION['id'];
    $startid = $_SESSION['startid'];
}

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
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

// トランザクションスタート
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("SELECT * FROM regular WHERE id >= :startid");
    $stmt->bindValue(':startid', $startid, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($records as $record) {
        // Excelファイルの書き込み
        $file_name = "";
        if ($record['billmeans'] == 1) {
            $file_name = "jpeg-0001.jpg";
            $sheet1->setCellValue('A' . $row1, $record['mail']);
            $sheet1->setCellValue('B' . $row1, $record['name']);
            $sheet1->setCellValue('C' . $row1, $record['office']);
            $row1++;
        } else {
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

        $today = new DateTime();
        $nendo = $today->format('Y');
        $yokunen = $nendo + 1;
        $todayString = $today->format('Y-m-d');
        $wareki = Wareki($todayString);

        // HTMLコンテンツを設定
        $html = <<<EOF
<style>
     h1 { font-size: 26px;text-align: center; }
     h2 { font-size: 18px;}
     dl {font-size:large; border: 0.1px #999 ;width:390px;}
     dt {float:left;width:100px;padding:5px 0.5px 10px;clear:both;font-weight:bold;}
     dd {width:260px; margin-left:100px; padding:5px 19px 5px 10px;}
    .green1 { background-color:#ccffcc;
        display: inline-block;text-align: center;width: auto; }
    .green2 { background-color: #ccffcc;
        display: inline-block; /* 文字の幅に合わせる */
        padding: 10px; /* 内側の余白を指定 */
        text-align: center;
        width: auto; /* 自動幅 */}
    .right {text-align: right;}
    .underline { font-size: 20px; text-decoration: underline;}
    .small {font-size: small;}
    

</style>
    <h1 class="green1">請&nbsp;&nbsp;&nbsp;求&nbsp;&nbsp;&nbsp;書</h1>
<body>
    <p class="right">{$wareki}</p>
    <h2 class="underline">{$record['atena']}</h2>
    <p>平素より格別のご高配を賜り、厚く御礼申し上げます。</p>
    <p>下記の通りご請求申し上げます。</p>
    <p style="text-align: right;">
    <img src="c:/xampp/htdocs/jmca/imageF/{$file_name}" alt={$file_name} width="300" height="120">
    </p>
    <p class="right">登録番号　　T2010005010157</p>
    <br><br>
   
    <dl>
        <dt> 件&nbsp;&nbsp;&nbsp;名</dt>
        <dd>　日本メディカルライター協会　　　正会員年会費</dd>
        <dd>　{$nendo}年度分　（会期：{$nendo}年4月～{$yokunen}年３月)</dd>
        <dd>　{$record['name']}様分</dd>
        <dd></dd>
        <dt> ご　請　求　額</dt>
        <dd>  ￥８，０００円</dd>
        <dd class = "small">*年会費には消費税は適用されません。記載通りの金額でお振込みください。</dd>
        <dt> お振込み先</dt>
            <dd>　三菱ＵＦＪ銀行　神田駅前支店</dd>
            <dd>　普通預金　２３２９９１１</dd>
            <dd>　トクヒ）ニホンメディカルライターキョウカイ</dd>
            <dd></dd>
            <dd class = "small">　＊振込手数料はご負担ください。</dd>
    </dl>
       
    <p></p>
    <p></p>
    <p></p>
    <p></p>
    <p></p>

    <p class="green2">Japan Medical and Scientific Communicators Association (JMCA)</p>
 
</body>
EOF;

        $pdf->writeHTML($html);
        if ($record['billmeans'] == 1){
        $pdfFile = 'billpdf_' . $record['name'] . '様.pdf';
        $pdf->Output('c:/xampp/htdocs/jmca/aaaa/年会費請求書/pdf/' . $pdfFile, 'F');            
        }else{
        $pdfFile = 'billyuubin_' . $record['name'] . '様.pdf';
        $pdf->Output('c:/xampp/htdocs/jmca/aaaa/年会費請求書/yuubin/' . $pdfFile, 'F');  
        }   

//uketukeboのbillsend（請求書送付日)を記載
        
    $stmt1 = $pdo->prepare('UPDATE regular SET modified = CURRENT_TIMESTAMP,billsend = :billsend
    WHERE id = :id');
        $stmt1->bindValue(':billsend', $todayString, PDO::PARAM_STR);
        $stmt1->bindValue(':id', $record['id'], PDO::PARAM_INT);
        $stmt1->execute();
        
    }
    // Excelファイルとして保存
        date_default_timezone_set('Asia/Tokyo');//標準時間を日本に合わせる
        if ($row1 > 2){
        $writer1 = new Xlsx($spreadsheet1);
        $now = date('Y-m-d-H-i-s');
        $filename ="./aaaa/年会費請求書/pdf/billpdf_$now.xlsx";
        $writer1->save($filename);
        }
        if ($row2 >2){
        $writer2 = new Xlsx($spreadsheet2);
        $now = date('Y-m-d-H-i-s');
        $filename ="./aaaa/年会費請求書/yuubin/billyuubin_$now.xlsx";
        $writer2->save($filename);
        }
    
        $row1 = $row1-2;
        echo "PDF請求書は".$row1."枚です。";
        $row2 = $row2 - 2;
        echo "紙の請求書は".$row2."枚です。";
    //DBの処理がすべてうまくいきました
        $pdo->commit();  
} catch (PDOException $e) {
     $pdo->rollBack();
    echo "DB処理にエラーが発生しました。DBは更新されません。" . $e->getMessage();
}


?>
