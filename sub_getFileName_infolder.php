<?php
//セッッションスタート
if (session_status()===PHP_SESSION_NONE){
session_start(); // セッションを開始
}
require './vendor/autoload.php';


// サブルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
// 
//DBに接続
function connect() {
           $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
        } 

// メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー




// 受付簿フォルダー内のCSVファイルを取得
$directory = './aaaa/受付簿';
$csvFiles = glob($directory . '/*.csv');
if (empty($csvFiles)) { 
    echo "受付簿フォルダーに１つもcsvファイルはありません。";
    $_SESSION = [];
    session_destroy();
    exit();
}

$arrayFilename = ['ダミー'];
foreach ($csvFiles as $file) {
    $arrayFilename[] = basename($file); // ファイル名のみを格納
}

$_SESSION['filename'] = $arrayFilename;

// 処理ファイルの選択と表示
$count = 0;
    foreach ($arrayFilename as $filename) {
        //1件目読み飛ばし
        if ($count == 0){
            $count++;
            continue;
        }
        echo '<tr><td>' . $count . '</td>';
        echo '<td>' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '</td></tr>';
        $count++;
    }
?>

