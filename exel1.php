<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$reader = new Xlsx();
$spreadsheet = $reader->load('c:\xampp\htdocs\jmca\test.xlsx');

$sheet = $spreadsheet->();
$value = $sheet->getCell('A1')->getValue(); // セルA1の値を取得
echo $value;
?>
