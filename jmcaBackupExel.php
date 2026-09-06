<?php

// DB接続関数
function connect() {
    try {
        $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

// Excelファイル作成用のライブラリ
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// DB接続
$pdo = connect();

try {
    // トランザクション開始
    $pdo->beginTransaction();

    // 各テーブル用のSpreadsheetオブジェクト作成
    $spreadsheet1 = new Spreadsheet(); // uketukeboTB
    $sheet1 = $spreadsheet1->getActiveSheet();
    $spreadsheet2 = new Spreadsheet(); // regularTB
    $sheet2 = $spreadsheet2->getActiveSheet();
    $spreadsheet3 = new Spreadsheet(); // supportTB
    $sheet3 = $spreadsheet3->getActiveSheet();

    // 各シートのヘッダーを設定
    // uketukeboTBのヘッダー
    $sheet1->fromArray(['氏名', 'セミナーID', 'created', 'modified', 'メールアドレス', '所属', '部署', '区分', '請求書', '領収書', '宛名', '郵便番号', '住所', '建物', '複数回開催か', '請求書送付日', '領収書送付日', '支払日', '参加回数', '税抜価格', '消費税', '税込み価格', '会員ID', 'note', 'セミナー名', '応募日時'], NULL, 'A1');

    // regularTBのヘッダー
    $sheet2->fromArray(['ID', 'created', 'modified', 'mail', 'name', 'kana', '入会日', '退会', '退会日', '業種', '所属', '部署', '連絡先', '郵便番号', '住所', '建物', '電話', '宛名', 'fee3', 'fee2', 'fee1', 'fee', '請求書', '領収書', '請求書送付日', '領収書送付日', 'PW', 'note'], NULL, 'A1');

    // supportTBのヘッダー
    $sheet3->fromArray(['ID', 'created', 'modified', '会社名', 'カナ', '入会日', '退会', '退会日', '業種', '担当者1', 'カナ', 'メールAD', '部署', '担当者２', 'カナ', 'メールAD', '部署', '郵便番号', '住所', '建物', '電話', 'fee3', 'fee2', 'fee1', 'fee', '継続', '請求書', '領収書', '口数', '請求書送付日', '領収書送付日', 'PW', 'note'], NULL, 'A1');

    // uketukeboTBデータの取得と書き込み
    $stmt = $pdo->prepare("SELECT * FROM uketukebo");
    $stmt->execute();
    $row1 = 2; // データは2行目から
    while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sheet1->fromArray(array_values($record), NULL, 'A' . $row1++);
    }

    // regularTBデータの取得と書き込み
    $stmt = $pdo->prepare("SELECT * FROM regular");
    $stmt->execute();
    $row2 = 2; // データは2行目から
    while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sheet2->fromArray(array_values($record), NULL, 'A' . $row2++);
    }

    // supportTBデータの取得と書き込み
    $stmt = $pdo->prepare("SELECT * FROM support");
    $stmt->execute();
    $row3 = 2; // データは2行目から
    while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sheet3->fromArray(array_values($record), NULL, 'A' . $row3++);
    }

    // Excelファイルとして保存
    date_default_timezone_set('Asia/Tokyo'); // 標準時間を日本に設定
    $now = date('Y-m-d-H-i-s');

    // ファイル名を作成して保存
    $writer1 = new Xlsx($spreadsheet1);
    $filename1 = "C:/backup/uketukebo_$now.xlsx";
    $writer1->save($filename1);
    echo "uketukeboTBバックアップ終了<br>";

    $writer2 = new Xlsx($spreadsheet2);
    $filename2 = "C:/backup/regular_$now.xlsx";
    $writer2->save($filename2);
    echo "regularTBバックアップ終了<br>";

    $writer3 = new Xlsx($spreadsheet3);
    $filename3 = "C:/backup/support_$now.xlsx";
    $writer3->save($filename3);
    echo "supportTBバックアップ終了<br>";

    // トランザクションコミット
    $pdo->commit();
} catch (Exception $e) {
    // エラーが発生した場合はロールバック
    $pdo->rollback();
    echo "問題が発生しました: " . $e->getMessage();
}

header('Location: jmcaBackupDb.php');
exit();

?>
