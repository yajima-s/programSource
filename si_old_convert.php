//2021年～2023年の手作業exelシニアポイントデータを読込みシニアDBを作成する
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

// メインルーチン
if (!file_exists('./aaaa/シニアポイント/siniorWf.xlsx')) {
    echo "ファイルが見つかりません。";
    exit;
}

$reader = new Xlsx();

try {
    $spreadsheet = $reader->load('./aaaa/シニアポイント/siniorWf.xlsx');
} catch (Exception $e) {
    echo "ファイルの読み込みに失敗しました: " . $e->getMessage();
    exit;
}

$sheet = $spreadsheet->getSheetByName('Sheet1');

if ($sheet === null) {
    echo "シートが見つかりません。";
    exit;
}


try {
    $pdo = connect();
} catch (PDOException $e) {
    echo "DB接続に失敗しました。" . $e; 
    exit;
}

// シニアポイントワークファイル読込み
$count = 0;
$count1 = 0;
foreach ($sheet->getRowIterator() as $row) {
    $count++;
    if ($count == 1) {
        continue; // タイトルを読み飛ばし
    }

    // 検索するキーの値
    $cell = $sheet->getCell('A' . $row->getRowIndex()); // メールAD
    $mail = $cell->getValue();
    $cell = $sheet->getCell('B' . $row->getRowIndex());
    $name = $cell->getValue();
    $name = str_replace([' ', '　'], '', $name);//氏名のスペースを削除
    $name = trim($name);//文字列を前詰め
    $cell = $sheet->getCell('C' . $row->getRowIndex()); // セミナーID
    // 5文字の文字列
    $originalString = $cell->getValue();
    // 最初の1文字を取り除く
    $trimmedString = substr($originalString, 1);
    // 数値として取り出す
    $seminar_id = (int)$trimmedString;
    $cell = $sheet->getCell('E' . $row->getRowIndex()); // ポイント
    $point = $cell->getValue();
    $cell = $sheet->getCell('F' . $row->getRowIndex()); // エッセンシャル
    $essential = $cell->getValue();
    // レコードが存在するか確認するSQL
    $checkSql = 'SELECT COUNT(*) FROM sinior WHERE mail = :mail AND seminar_id = :seminar_id';
    $stmt = $pdo->prepare($checkSql);
    $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
    $stmt->bindParam(':seminar_id', $seminar_id, PDO::PARAM_INT);
    $stmt->execute();

    // レコードが存在するか確認
    if ($stmt->fetchColumn() == 0) {
        // レコードが存在しない場合、新しいレコードを追加するSQL
        $insertSql = 'INSERT INTO sinior (mail, seminar_id, name, point, essential) VALUES (:mail, :seminar_id, :name, :point, :essential)';
        $stmt = $pdo->prepare($insertSql);
       
        $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
        $stmt->bindParam(':seminar_id', $seminar_id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':point', $point, PDO::PARAM_INT);
        $stmt->bindParam(':essential', $essential, PDO::PARAM_INT); // essentialはデフォルト値0
        $stmt->execute();
        
    }else{
        $count1++;
        echo "同一キー".$mail.$seminar_id;
        //同一レコードは後のレコードを優先する
        $stmt = $pdo->prepare('UPDATE sinior SET name = :name WHERE mail = :mail AND seminar_id = :seminar_id');
        $stmt->bindParam(':name',$name,PDO::PARAM_STR);
        $stmt->bindParam(':mail',$mail,PDO::PARAM_STR);
        $stmt->bindParam(':seminar_id',$seminar_id,PDO::PARAM_INT);
        $stmt->execute();
    }
}

$pdo = NULL;
echo "総件数＝".$count;
echo "同一キー".$count1;
?>
