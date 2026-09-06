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
// セミナーIDが4桁の半角数字かチェック
function isFourDigitNumber($value) {
    return preg_match('/^\d{4}$/', $value) === 1;
}

function seminarId_check(&$seminarId){
    if (!isset($_POST['seminarId'])) {
        header('Location: s_id_in.php?error=セミナーＩＤが入力されていません');
        exit;      
    }
    $value = $_POST['seminarId'];
    if (isFourDigitNumber($value)) {
        $seminarId = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    } else {
        header('Location: s_id_in.php?error=セミナーＩＤは半角4桁です');
        exit; 
    }
}

// セミナーIDの存在チェック→存在可否、セミナー名、複数回かを返す
function seminarId_dbcheck($pdo, $seminarId, &$plural, &$seminarName){
    $statement2 = $pdo->prepare('SELECT name, plural FROM seminar WHERE id = :id');
    $statement2->bindValue(':id', $seminarId, PDO::PARAM_STR);
    $statement2->execute();
    $result = $statement2->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        header('Location: s_id_in.php?error=正しいセミナーIDを再インプットしてください');
        exit;
    }
    $seminarName = $result['name']; // seminarNameはここで固定
    $plural = $result['plural'];    // pluralはここで固定
}

// インプットしたセミナーID、受付簿フォルダーにあるCSVファイル名を表示して、処理するファイル名を選択する
function getFilename(){
    if (!isset($_SESSION['filename'])) {
        header('Location: s_id_in.php?error=最初からやり直してください');
        exit;
    }
    $seminarId = $_SESSION['seminarId'];
    $seminarName = $_SESSION['seminarName'];
    $plural = $_SESSION['plural'];
    $arrayFilename = $_SESSION['filename'];

    echo '<body>';
    echo '<p>入力したセミナーID＝' . $seminarId . '</p>';
    echo '<p>セミナー名＝' . $seminarName . '</p>';
    echo '<p>複数回開催か(1:1回のみ　2：複数回)    ' . $plural . '</p>';
    echo '<p>以下のファイルが受付簿フォルダーにあります</p>';
    echo '<table border="1">';
    echo '<tr><th>番号</th><th>ファイル名</th></tr>';

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
    echo '</table>';
    echo '</body>';

    $_SESSION['count'] = $count - 1;
echo '<p>該当番号を1つ選択してください。</p>';
echo '<form action="s_dbsetAndo.php" method="post">';
echo '<p>番号(小文字）</p>';
echo '<input type="text" name="count" value="">';
echo '<input type="submit" value="送信">';
echo '<br><br><br>';
echo '</form>';

}

// メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー

if (isset($_GET['error'])) {
    echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
    getFilename();
    exit();
}

// DBと接続
try {
            $pdo = connect();
     } catch (PDOException $e) {
            echo "DB接続に失敗しました。".$e; 
            exit;}


// セミナーIDの形式チェック
$seminarId = 0;
seminarId_check($seminarId);

// seminarDBの存在チェック、複数回かを返す
$plural = 0;
$seminarName = "";
seminarId_dbcheck($pdo, $seminarId, $plural, $seminarName);

$_SESSION['seminarId'] = $seminarId;
$_SESSION['seminarName'] = $seminarName;
$_SESSION['plural'] = $plural;

// 受付簿フォルダー内のCSVファイルを取得
$directory = './aaaa/受付簿';
$csvFiles = glob($directory . '/*.csv');
if (empty($csvFiles)) { 
    echo "受付簿フォルダーに１つもcsvファイルはありません。";
    
    exit();
}

$arrayFilename = ['ダミー'];
foreach ($csvFiles as $file) {
    $arrayFilename[] = basename($file); // ファイル名のみを格納
}

$_SESSION['filename'] = $arrayFilename;

// 処理ファイルの選択と表示

getFilename();
exit;
?>

