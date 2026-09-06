<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\IOFactory;

function connect() {
           $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
        } 

function convertDateToExcelSerial($value) {
    // DateTimeオブジェクトを作成
    $dateTime = new DateTime($value);

    // PhpSpreadsheetの関数を使用して、Excelの日付シリアル値に変換
    $excelSerial = PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($dateTime);

    return $excelSerial;
}

$reader = new Xlsx();
$spreadsheet = $reader->load('./aaaa/会員マスター/jmcam.xlsx');

$sheet = $spreadsheet->getSheetByName('正会員');

try {
            $pdo = connect();
     } catch (PDOException $e) {
            echo "DB接続に失敗しました。".$e; 
            exit;}

foreach ($sheet->getRowIterator() as $row){
   
    $statement = $pdo->prepare('INSERT INTO regular(id, created,modified,mail,
  name,kana,adday,withdraw,withdrawday,category,office,busho,renraku,post,address,bilname,tel,atena,fee3,fee2,fee1,fee,billmeans,receiptmeans,receiptsend,pw,note) VALUES(:id, CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,:mail,:name,:kana,:adday,:withdraw,:withdrawday,:category,:office,:busho,:renraku,:post,:address,:bilname,:tel,:atena,:fee3,:fee2,:fee1,:fee,:billmeans,:receiptmeans,:receiptsend,:pw,:note)');
    $cell = $sheet->getCell('F' . $row->getRowIndex());//Fは退会：TRUE　退会していない:null　exelファイルのタイトルは“退会”のためNULLではない
    $value = $cell->getValue();
    if ($value == null){
    $cell = $sheet->getCell('A' . $row->getRowIndex());
    $value = $cell->getValue();
     if ($value !== null){
            $statement->bindValue(':id',$value, PDO::PARAM_INT);
        }else{
            continue;}
    $cell = $sheet->getCell('P' . $row->getRowIndex());
    $value = $cell->getValue();
            $statement->bindValue(':mail',$value, PDO::PARAM_STR);      
    $cell = $sheet->getCell('B' . $row->getRowIndex());
    $value = $cell->getValue();
    //氏名の空白は削除する→正会員マスターDBの指名は空白がない状態
    $value = str_replace([' ', '　'], '', $value);
            $statement->bindValue(':name',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('C' . $row->getRowIndex());
    $value = $cell->getValue();
            $statement->bindValue(':kana',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('D' . $row->getRowIndex());
    $value = $cell->getValue();
    if (is_numeric($value)) {
             $date = Date::excelToDateTimeObject($value)->format('Y-m-d');
             $statement->bindValue(':adday',$date,PDO::PARAM_STR);
    }elseif (!empty($value)){
             $date = DateTime::createFromFormat('Y/n/j', $value)->format('Y-m-d');
             $statement->bindValue(':adday',$date,PDO::PARAM_STR);
    }else{
             $statement->bindValue(':adday',NULL,PDO::PARAM_NULL); 
    }

    $cell = $sheet->getCell('F' . $row->getRowIndex());
    $value = $cell->getValue();
    if ($value === '' || $value === NULL) {
    $statement->bindValue(':withdraw', 1, PDO::PARAM_INT); // 1: life
    } else {
    $statement->bindValue(':withdraw', 2, PDO::PARAM_INT); // 2: death
    }

    $cell = $sheet->getCell('G' . $row->getRowIndex());
    $value = $cell->getValue();
    if ($value != null){$dateValue = Date::excelToDateTimeObject($value);
             $statement->bindValue(':withdrawday',$dateValue->format('Y-m-d'),PDO::PARAM_STR);}
        else {$statement->bindValue(':withdrawday',NULL,PDO::PARAM_NULL);}             
    $cell = $sheet->getCell('H' . $row->getRowIndex());
    $value = $cell->getValue();
            if (preg_match('/医薬品/',$value)) {
        $statement->bindValue(':category',1, PDO::PARAM_INT);}
            elseif (preg_match('/コンサルタント業/',$value)) {$statement->bindValue(':category',2, PDO::PARAM_INT);}
            elseif (preg_match('/医薬関連広告宣伝業/',$value)) {$statement->bindValue(':category',3, PDO::PARAM_INT);}
            elseif (preg_match('/医療機関/',$value)) {$statement->bindValue(':category',4, PDO::PARAM_INT);}
            elseif (preg_match('/メディア/',$value)) {$statement->bindValue(':category',5, PDO::PARAM_INT);}
            elseif (preg_match('/フリーランス/',$value)) {$statement->bindValue(':category',6, PDO::PARAM_INT);}
            elseif (preg_match('/大学/',$value)) {$statement->bindValue(':category',7, PDO::PARAM_INT);}
            else{$statement->bindValue(':category',8, PDO::PARAM_INT);}
           
      
    $cell = $sheet->getCell('I' . $row->getRowIndex());
    $value = $cell->getValue();
             $statement->bindValue(':office',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('K' . $row->getRowIndex());
    $value = $cell->getValue();
             $statement->bindValue(':busho',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('L' . $row->getRowIndex());
    $value = $cell->getValue();
             $statement->bindValue(':renraku',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('M' . $row->getRowIndex());
    $value = $cell->getValue();
             $statement->bindValue(':post',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('N' . $row->getRowIndex());
    $value = $cell->getValue();
             $statement->bindValue(':address',$value, PDO::PARAM_STR); 
    $cell = $sheet->getCell('O' . $row->getRowIndex());
    $value = $cell->getValue();
             $statement->bindValue(':bilname',$value, PDO::PARAM_STR); 
    $cell = $sheet->getCell('Q' . $row->getRowIndex());
    $value = $cell->getValue();
             $statement->bindValue(':tel',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('R' . $row->getRowIndex());
    $value = $cell->getValue();
             $statement->bindValue(':atena',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('S' . $row->getRowIndex());
    $value = $cell->getValue();
    if (is_int($value)){$dateValue = Date::excelToDateTimeObject($value);
             $statement->bindValue(':fee3',$dateValue->format('Y-m-d'),PDO::PARAM_STR);}
        else {$statement->bindValue(':fee3',NULL,PDO::PARAM_NULL);}
    $cell = $sheet->getCell('T' . $row->getRowIndex());
    $value = $cell->getValue();
    if (is_int($value)){$dateValue = Date::excelToDateTimeObject($value);
             $statement->bindValue(':fee2',$dateValue->format('Y-m-d'),PDO::PARAM_STR);}
        else {$statement->bindValue(':fee2',NULL,PDO::PARAM_NULL);}
    $cell = $sheet->getCell('U' . $row->getRowIndex());
    $value = $cell->getValue();
    if (is_int($value)){$dateValue = Date::excelToDateTimeObject($value);
             $statement->bindValue(':fee1',$dateValue->format('Y-m-d'),PDO::PARAM_STR);}
        else {$statement->bindValue(':fee1',NULL,PDO::PARAM_NULL);}
    $cell = $sheet->getCell('V' . $row->getRowIndex());
    $value = $cell->getValue();
    if (is_int($value)){$dateValue = Date::excelToDateTimeObject($value);
             $statement->bindValue(':fee',$dateValue->format('Y-m-d'),PDO::PARAM_STR);}
        else {$statement->bindValue(':fee',NULL,PDO::PARAM_NULL);}
    $cell = $sheet->getCell('W' . $row->getRowIndex());
    $value = $cell->getValue();
        if (!is_null($value))  {   if (preg_match('/PDF/',$value)) {
        $statement->bindValue(':billmeans',1, PDO::PARAM_INT);}
              elseif (preg_match('/郵送/',$value)) {
        $statement->bindValue(':billmeans',2, PDO::PARAM_INT);}
              else{
        $statement->bindValue(':billmeans',3, PDO::PARAM_INT);} 
                               }else {$statement->bindValue(':billmeans',1, PDO::PARAM_INT);}
    $cell = $sheet->getCell('X' . $row->getRowIndex());
    $value = $cell->getValue();
            if (!is_null($value))  {   if (preg_match('/PDF/',$value)) {
        $statement->bindValue(':receiptmeans',1, PDO::PARAM_INT);}
              elseif (preg_match('/郵送/',$value)) {
        $statement->bindValue(':receiptmeans',2, PDO::PARAM_INT);}
              else{
        $statement->bindValue(':receiptmeans',3, PDO::PARAM_INT);} 
                               }else {$statement->bindValue(':receiptmeans',1, PDO::PARAM_INT);}
    $cell = $sheet->getCell('Y' . $row->getRowIndex());
    $value = $cell->getValue();
    if (is_int($value)){$dateValue = Date::excelToDateTimeObject($value);
            $statement->bindValue(':receiptsend',$dateValue->format('Y-m-d'),PDO::PARAM_STR);}
        else {$statement->bindValue(':receiptsend',NULL,PDO::PARAM_NULL);}
    
    $cell = $sheet->getCell('Z' . $row->getRowIndex());
    $value = $cell->getValue();
            $statement->bindValue(':pw',$value, PDO::PARAM_STR);
    $cell = $sheet->getCell('AA' . $row->getRowIndex());
    $value = $cell->getValue();
            $statement->bindValue(':note',$value, PDO::PARAM_STR);
    
 $statement->execute();   
    }
  
}


?>
