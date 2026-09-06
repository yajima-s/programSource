<?php
//4年間年会費未払者を正会員マスターより削除
// サブルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
// jmcaDBを開きます
function connect() {
    $host = 'localhost';
    $dbname = 'jmca';
    $username = 'root';
    $password = '';  // 環境変数などに移動すべき
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Connection failed: ' . $e->getMessage());  // エラーログを残す
        exit('データベース接続エラーが発生しました。管理者に連絡してください。');
    }
}


// メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー

// DBに接続
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//各処理の結果情報をリストに
$_SESSION['list'][] = [];
$nendoMatu = $_SESSION['nendo'] + 1;
$nendoMatu = $nendoMatu."-03-31";

$pdo = connect();

//exelファイルを扱う準備
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// 新しいスプレッドシートを作成
$spreadsheet1 = new Spreadsheet();//削除者メールAD一覧表のexelファイル
$sheet1 = $spreadsheet1->getActiveSheet();

// ヘッダーの設定
$sheet1->setCellValue('A1', 'メールアドレス');
$sheet1->setCellValue('B1', '氏名');
$row1 = 2;

// 年度管理テーブル（nendo）と入力年度のチェック
$stmt2 = $pdo->prepare('SELECT * FROM nendo');
$stmt2->execute();
$result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$result2) {
    echo '年度管理テーブルにデータがありません';
    exit;
}
if ($result2['fee1'] !== $_SESSION['nendo']) {
    echo "エラー: 年度管理テーブルの記録 ($result2[fee1]) と入力された年度 ($_SESSION[nendo]) が一致しません。確認して再試行してください。";
    exit;
}


$stmt = $pdo->prepare('SELECT * FROM regular WHERE withdraw = 1');
$stmt->execute();

$count = 0;
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $result['id'];
    $fee = $result['fee'];
    $fee1 = $result['fee1'];
    $fee2 = $result['fee2'];
    $fee3 = $result['fee3'];
    //emptyを使うとNULL,””の両方をカバーできる：3年間+来期　連続未払者を選択
    if ((empty($fee) || $fee === '0000-00-00') && (empty($fee1) || $fee1 === '0000-00-00') 
        && (empty($fee2) || $fee2 === '0000-00-00') && (empty($fee3) || $fee3 === '0000-00-00')) {
        $stmt1 = $pdo->prepare('UPDATE regular SET withdraw = :withdraw,withdrawday = :withdrawday WHERE id = :id');
        $stmt1->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt1->bindValue(':withdraw', 2, PDO::PARAM_INT);
        $stmt1->bindValue(':withdrawday', $nendoMatu, PDO::PARAM_STR);
        $stmt1->execute();
        $count++;
        // Excelファイルの書き込
        $sheet1->setCellValue('A' . $row1, $result['mail']);
        $sheet1->setCellValue('B' . $row1, $result['name']);
        $row1++;
    }    
}
if ($row1 > 2){
        $writer1 = new Xlsx($spreadsheet1);
        $now = date('Y-m-d-H-i-s');
        $dir = "./aaaa/期末処理/未払削除者/";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);  // ディレクトリがなければ作成
        }
        $filename = $dir . "mibarai_sakujyosha_$now.xlsx";
        $writer1->save($filename);

        }
$_SESSION['list'][] = "正会員マスターより削除した3年間未払者数＝ " .$count;

// 処理完了後、リダイレクト
header('Location: ts_kurikoshi_regular.php');
exit();
?>
