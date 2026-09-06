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

// 正会員マスターの存在チェック
function regularCheck($mail, $name, &$isSw) {
    global $pdo;
    
    try {
        // メールをキーに正会員TBを検索
        $sql = "SELECT 1 FROM regular WHERE mail = :mail LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            $isSw = 1;
            return;
        }
        
        // 氏名をキーに正会員TBを検索
        $sql = "SELECT 1 FROM regular WHERE name = :name LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result) {
            $isSw = 1;
        }
        
    } catch (PDOException $e) {
        echo "エラー: " . $e->getMessage();
    }
}

// generalTBレコード追加
function addGeneral($pdo,$mail, $name) {

    
    try {
        // 事前に変数を用意
        $office = NULL;
        $nendo = 23;
        
        $sql = "INSERT INTO general (mail, created, modified, name, office, nendo) 
                VALUES (:mail, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :name, :office, :nendo)";
        
        $stmt1 = $pdo->prepare($sql);
        $stmt1->bindParam(':mail', $mail, PDO::PARAM_STR);
        $stmt1->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt1->bindParam(':office', $office, PDO::PARAM_STR);  // 変数を事前に定義
        $stmt1->bindParam(':nendo', $nendo, PDO::PARAM_INT);     // 変数を事前に定義
        $stmt1->execute();
        
    } catch (PDOException $e) {
        echo "エラー: " . $e->getMessage();
    }
}


// メインルーチン
$reader = new Xlsx();
$spreadsheet = $reader->load('./aaaa/一般応募者/general.xlsx');
$sheet = $spreadsheet->getSheetByName('Sheet1');

try {
    $pdo = connect();
} catch (PDOException $e) {
    echo "DB接続に失敗しました。" . $e;
    exit;
}

// 1件目読み飛ばし
$iterator = $sheet->getRowIterator();
//$iterator->rewind(); // 先頭にリセット
//$iterator->next();

$n = 0;
$count = 0;
$count1 = 0;
$count2 = 0;
//foreachは１番目から処理をするので、$iterator->next();では１行読み飛ばせない
foreach ($iterator as $row) {
    $n++;
    if ($n == 1){
        continue;
    }
    $mail = $sheet->getCell('A' . $row->getRowIndex())->getValue();
    $name = $sheet->getCell('B' . $row->getRowIndex())->getValue();
    $name = str_replace([' ', '　'], '', $name);
    $isSw = 0;
    
    regularCheck($mail, $name, $isSw);
    
    if ($isSw == 0) {
        $sql = "SELECT * FROM general WHERE mail = :mail LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // generalTBに同一キーが存在していた
        if ($result) {
            $count1++;
            continue;
        } else {
            addGeneral($pdo,$mail, $name);
            $count++;
        }
    }else{
        $count2++;
    }
}

echo "一般応募DBへの登録件数は" . $count;
echo "　同一キーレコードは" . $count1;
echo "　正会員になった人は" . $count2;
?>
