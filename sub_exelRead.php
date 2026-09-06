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

//メインルーチン

$reader = new Xlsx();
$spreadsheet = $reader->load('./aaaa/jmcam.xlsx');
$sheet = $spreadsheet->getSheetByName('正会員');

try {
            $pdo = connect();
     } catch (PDOException $e) {
            echo "DB接続に失敗しました。".$e; 
            exit;}

foreach ($sheet->getRowIterator() as $row){
   
    $statement = $pdo->prepare('INSERT INTO regular(id, created,modified,mail) 
    VALUES(:id, CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,:mail');
    
//読み飛ばし
    $cell = $sheet->getCell('A' . $row->getRowIndex());
    $value = $cell->getValue();
     if ($value !== null){
            $statement->bindValue(':id',$value, PDO::PARAM_INT);
     }else{
            continue;}
         
    $cell = $sheet->getCell('P' . $row->getRowIndex());
    $value = $cell->getValue();
    $statement->bindValue(':mail',$value, PDO::PARAM_STR);      
   
           
    
    $statement->execute();   
}

?>
