<?php
include('./vendor/autoload.php');
  
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
  
$reader = new XlsxReader();
$spreadsheet = $reader->load('.xlsx'); // ファイル名を指定
$sheet = $spreadsheet->getSheetByName('test'); // 読み込むシートを指定
  
$data = $sheet->rangeToArray('A1:E1'); // 配列で取得したい範囲を指定
var_dump($data);

?>