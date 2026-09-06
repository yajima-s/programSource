<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Excelファイルのパス
$filePath = 'test.xlsx';

// ファイルの読み込み
$spreadsheet = IOFactory::load($filePath);

// シートを取得
$sheet = $spreadsheet->getActiveSheet();

// セルの値を表示
foreach ($sheet->getRowIterator() as $row) {
    $cellIterator = $row->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(false); // 空のセルも読み込む
    foreach ($cellIterator as $cell) {
        if (!is_null($cell)) {
            $value = $cell->getValue();
            echo $value . PHP_EOL;
        }
    }
}
?>
