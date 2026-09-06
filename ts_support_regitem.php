<?php
require_once('tcpdf/tcpdf.php');
require_once 'vendor/autoload.php';   // PhpSpreadsheet
date_default_timezone_set('Asia/Tokyo');



use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// DB接続
function connect() {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=jmca;charset=utf8mb4',
            'root',
            ''
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

$pdo = connect();
$timestamp = date('Y-m-d-H-i-s');

// suport テーブル全件取得
$sql = "SELECT * FROM support";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// PDF 出力フォルダ
$outputDir = __DIR__ . "/aaaa/期末処理/賛助会員登録事項一覧表/";
if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Excel 用データ格納配列
$excelData = [];
$excelData[] = ["会員名", "メールアドレス"];  // 見出し

// PDF生成ループ
foreach ($rows as $row) {

    // Excel 用データを追加
    $excelData[] = [
        $row['name'] ?? "",
        $row['person1mail'] ?? ""
    ];

    // PDFファイル名
    $filename = $outputDir . "/support_" . $row['name'] . "御中_" . $timestamp . ".pdf";

    // TCPDF 初期化
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('JMCA System');
$pdf->SetAuthor('JMCA');
$pdf->SetTitle('賛助会員登録情報');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();

// ★ 日本語フォント設定（AddPage の後に必ず書く）
$pdf->SetFont('kozgopromedium', '', 12);



    // 表示用変換
    $billmeans = ($row['billmeans'] == 1) ? "PDF" : "郵送";
    $receiptmeans = "";
    if ($row['receiptmeans'] == 1) $receiptmeans = "PDF";
    elseif ($row['receiptmeans'] == 2) $receiptmeans = "郵送";
    elseif ($row['receiptmeans'] == 3) $receiptmeans = "不要";

    // PDF本文
    $html = '
    <h2 style="text-align:center;">賛助会員登録情報</h2>
    <br>
    <table border="0" cellpadding="6" style="font-size:12pt;">
        <tr><td><b>1．会員名</b></td><td>' . htmlspecialchars($row['name'] ?? "") . '</td></tr>
        <tr><td><b>2．担当者１（名前）</b></td><td>' . htmlspecialchars($row['person1'] ?? "") . '</td></tr>
        <tr><td><b>3．担当者１（カナ）</b></td><td>' . htmlspecialchars($row['person1kana'] ?? "") . '</td></tr>
        <tr><td><b>4．担当者１（メール）</b></td><td>' . htmlspecialchars($row['person1mail'] ?? "") . '</td></tr>
        <tr><td><b>5．担当者１（部署）</b></td><td>' . htmlspecialchars($row['person1busho'] ?? "" ) . '</td></tr>
        <tr><td><b>6．担当者２（名前）</b></td><td>' . htmlspecialchars($row['person2'] ?? "" ) . '</td></tr>
        <tr><td><b>7．担当者２（カナ）</b></td><td>' . htmlspecialchars($row['person2kana'] ?? "") . '</td></tr>
        <tr><td><b>8．担当者２（メール）</b></td><td>' . htmlspecialchars($row['person2mail'] ?? "" ) . '</td></tr>
        <tr><td><b>9．担当者２（部署）</b></td><td>' . htmlspecialchars($row['person2busho'] ?? "") . '</td></tr>
        <tr><td><b>10．〒</b></td><td>' . htmlspecialchars($row['post'] ?? "") . '</td></tr>
        <tr><td><b>11．住所</b></td><td>' . htmlspecialchars($row['address'] ?? "") . '</td></tr>
        <tr><td><b>12．ビル名</b></td><td>' . htmlspecialchars($row['bilname'] ?? "") . '</td></tr>
        <tr><td><b>13．電話番号</b></td><td>' . htmlspecialchars($row['tel'] ?? "") . '</td></tr>
        <tr><td><b>14．請求書形式</b></td><td>' . $billmeans . '</td></tr>
        <tr><td><b>15．領収書形式</b></td><td>' . $receiptmeans . '</td></tr>
    </table>
    ';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output($filename, 'F');
}

// ------------------------------------------------------------
// Excel 出力
// ------------------------------------------------------------
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$rowNum = 1;
foreach ($excelData as $line) {
    $sheet->setCellValue("A{$rowNum}", $line[0]);
    $sheet->setCellValue("B{$rowNum}", $line[1]);
    $rowNum++;
}
$excelDir = __DIR__ . "/aaaa/期末処理/賛助会員登録事項一覧表/";
if (!file_exists($excelDir)) {
    mkdir($excelDir, 0777, true);
}

$now = date('Y-m-d-H-i-s');
$excelFile = $excelDir . "登録事項_{$now}.xlsx";

$writer = new Xlsx($spreadsheet);
$writer->save($excelFile);


// 完了後リダイレクト
header('Location: ts_ouboshasu.php');
exit();

?>
