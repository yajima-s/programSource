<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// DB接続
function connect() {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=jmca;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        return $pdo;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

// 出力ファイルパス
$filepath = "C:/xampp/htdocs/jmca/aaaa/一般応募者/general.xlsx";

// ① 既存ファイルがあれば中身を空にする（新規 Spreadsheet を上書き）
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// 見出し
$sheet->setCellValue('A1', 'メールアドレス');
$sheet->setCellValue('B1', '名前');

// ② DB からデータ取得
$pdo = connect();
$sql = "SELECT mail, name FROM general";
$stmt = $pdo->query($sql);

$row = 2; // データ書き込み開始行

while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sheet->setCellValue("A{$row}", $data['mail']);
    $sheet->setCellValue("B{$row}", $data['name']);
    $row++;
}

// ③ Excel ファイルとして保存（存在していれば上書き）
$writer = new Xlsx($spreadsheet);
$writer->save($filepath);

echo "general.xlsx を出力しました。";

?>