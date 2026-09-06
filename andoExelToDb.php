<?php
require 'vendor/autoload.php';   // PhpSpreadsheet を利用

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// DB接続
function connect() {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
}

$pdo = connect();

// Excel 読込
$excelPath = "c:/xampp/htdocs/jmca/aaaa/andoFile.xlsx";
$spreadsheet = IOFactory::load($excelPath);
$sheet = $spreadsheet->getActiveSheet();

$updatedCount = 0;

/**
 * Excel の日付を安全に yyyy-mm-dd に変換する
 * 対応形式：
 *   - Excel シリアル値（数値）
 *   - YYYY/MM/DD
 *   - mm月dd日
 *   - 不正形式 → NULL
 */
function convertDateOrNull($value) {

    if ($value === null || $value === '') {
        return null;
    }

    // Excel の日付シリアル値（数値）
    if (is_numeric($value)) {
        try {
            $dt = Date::excelToDateTimeObject($value);
            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }

    // YYYY/MM/DD
    if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $value)) {
        return str_replace('/', '-', $value);
    }

    // mm月dd日
    if (preg_match('/^\d{1,2}月\d{1,2}日$/', $value)) {
        $value = str_replace("月", "-", $value);
        $value = str_replace("日", "", $value);
        $year = date("Y");
        return "{$year}-{$value}";
    }

    return null;
}


// Excel の全行をループ（1行目はヘッダ想定）
foreach ($sheet->getRowIterator(2) as $row) {

    $cells = [];

    foreach ($row->getCellIterator() as $cell) {
        $value = $cell->getValue();

        if (is_null($value)) {
            $cells[] = "";
        } else {
            $cells[] = trim((string)$value);
        }
    }

    // Excel カラム
    $mail = $cells[1];                            // 2番目のカラム（メール） 

    // 全角スペース・不可視文字・改行・タブを除去
    $mail = preg_replace('/[\x00-\x1F\x7F]/u', '', $mail);  // 制御文字除去
    $mail = str_replace([" ", "　", "\r", "\n", "\t"], "", $mail);  // 半角/全角スペース・改行・タブ除去
    $mail = trim($mail);  // 最終トリム
    $mail = strtolower($mail);  // 小文字化（DBが小文字なら）
    // ★ メールが空なら何も表示せずスキップ
    if ($mail === "") continue;                        
    $billSend = convertDateOrNull($cells[14]);     // 15番目
    $receiptSend = convertDateOrNull($cells[15]);  // 16番目
    $paid = convertDateOrNull($cells[17]);         // 18番目

    // DB検索（mail + seminarid）
    $sql = "SELECT mail, seminarid 
            FROM uketukebo 
            WHERE mail = ? AND seminarid BETWEEN 2609 AND 2610";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mail]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        echo "メールアドレス {$mail} は DBに存在しません\n";
        continue; //uketukeboテーブルにレコードが存在しない場合は更新しない
    }

    // 更新（mail + seminarid）
    $update = "UPDATE uketukebo 
               SET billsend = ?, receiptsend = ?, paid = ?
               WHERE mail = ? AND seminarid BETWEEN 2609 AND 2610";

    $stmt2 = $pdo->prepare($update);
    $stmt2->execute([$billSend, $receiptSend, $paid, $mail]);

    $updatedCount++;
}

// 最後に更新件数を表示
echo "更新件数：{$updatedCount} 件\n";
?>


