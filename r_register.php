<?php
//正会員登録申請書(googlform）を読込み、正会員TBに新規データを登録する
//
//サブルーティン

//jmcaDBを開きます
function connect() {
    try {
        $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}

//文字列の先頭４半角数字を取り出す
function extractFirstFourDigits($text) {
    if (preg_match('/^[0-9]{4}/', $text)) {  // 先頭4文字が半角数字かチェック
        return substr($text, 0, 4);
    }
    return null;
}

//idの最大値＋1を返す
function getmaxid($pdo, &$nextid) {
    $sql = "SELECT MAX(id) AS max_id FROM regular";
    $result = $pdo->query($sql);
    $row = $result->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['max_id'] !== null) {
        $nextid = $row['max_id'] + 1;
    } else {
        $nextid = 1; // レコードがない場合は1から開始
    }
}


//請求書・領収書の形式を数字化（1:PDF　2:郵送）GooglFormにて不要の選択肢はない
function searchmeans($data, &$billmeans, &$receiptmeans) {
    if (preg_match('/郵送/', $data[14])) {
        $billmeans = 2;
    } else {
        $billmeans = 1;
    }
    if (preg_match('/郵送/', $data[15])) {
        $receiptmeans = 2;
    } else {
        $receiptmeans = 1;
    }
}

//regularTable(DB)に既に存在しているかのチェック
//氏名で検索して、アンマッチの場合はさらにメールアドレスで検索
//両方アンマッチの場合アンマッチ、どちらかマッチすればマッチ
//$kaiin_id = 9999の時アンマッチ、他はマッチ

function searchkaiinid($pdo, $data, &$kaiin_id) {
    $kaiin_id = 9999;
    $name = str_replace([' ', '　'], '', $data[2]);

    $statement = $pdo->prepare('SELECT id FROM regular WHERE name = :name AND withdraw = 1');
    $statement->bindValue(':name', $name, PDO::PARAM_STR); 
    $statement->execute();
    $recordExists = $statement->fetchColumn();

    if ($recordExists) {
        $kaiin_id = $recordExists;
        return;
    }

    $statement = $pdo->prepare('SELECT id FROM regular WHERE mail = :mail AND withdraw = 1');
    $mail = $data[1];
    $statement->bindValue(':mail', $mail, PDO::PARAM_INT);
    $statement->execute();
    $recordExists = $statement->fetchColumn();

    if ($recordExists) {
        $kaiin_id = $recordExists;
    }else {
            $kaiin_id = 9999;
        }
    }


//regularTable(DB)に1レコード追加
function addRegular($pdo, $data, $billmeans, $receiptmeans, $yokunendo) {
    $nextid = 0;
    getmaxid($pdo,$nextid);
    $statement1 = $pdo->prepare('INSERT INTO regular (id, created, modified, mail, name, kana, adday, withdraw, withdrawday, category, office, busho, renraku, post, address, bilname, tel, atena, fee3, fee2, fee1, fee, billmeans, receiptmeans, receiptsend,nyuukainendo) 
    VALUES(:id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :mail, :name, :kana, :adday, :withdraw, :withdrawday, :category, :office, :busho, :renraku, :post, :address, :bilname, :tel, :atena, :fee3, :fee2, :fee1, :fee, :billmeans, :receiptmeans, :receiptsend, :nyuukainendo)');

    $statement1->bindValue(':id', $nextid, PDO::PARAM_INT);
    $statement1->bindValue(':mail', $data[1], PDO::PARAM_STR);
    $statement1->bindValue(':name', $data[2], PDO::PARAM_STR);
    $statement1->bindValue(':kana', $data[3], PDO::PARAM_STR);
    date_default_timezone_set('Asia/Tokyo'); // タイムゾーンを日本に設定
    $text = $data[17];
// 全角 → 半角（数字のみ）
    $textHalf = mb_convert_kana($text, 'n');
// 先頭4桁の数字を抽出
    if (preg_match('/\d{4}/', $textHalf, $m)) {
    $nyuukainendo = (int)$m[0];   // int(4) にキャスト
    }
    $statement1->bindValue(':nyuukainendo', $nyuukainendo, PDO::PARAM_INT);
    if ($nyuukainendo == $yokunendo){
        $statement1->bindValue(':adday', $yokunendo . '-04-01', PDO::PARAM_STR);     
    }else{
        $statement1->bindValue(':adday', date('Y-m-d'), PDO::PARAM_STR);     
        }
    $statement1->bindValue(':withdraw', 1, PDO::PARAM_INT);
    $statement1->bindValue(':withdrawday',NULL, PDO::PARAM_NULL);
    $value = $data[11];
    if (preg_match('/医薬品/', $value)) {
        $statement1->bindValue(':category', 1, PDO::PARAM_INT);
    } elseif (preg_match('/コンサルタント業/', $value)) {
        $statement1->bindValue(':category', 2, PDO::PARAM_INT);
    } elseif (preg_match('/医薬関連広告宣伝業/', $value)) {
        $statement1->bindValue(':category', 3, PDO::PARAM_INT);
    } elseif (preg_match('/医療機関/', $value)) {
        $statement1->bindValue(':category', 4, PDO::PARAM_INT);
    } elseif (preg_match('/メディア/', $value)) {
        $statement1->bindValue(':category', 5, PDO::PARAM_INT);
    } elseif (preg_match('/フリーランス/', $value)) {
        $statement1->bindValue(':category', 6, PDO::PARAM_INT);
    } elseif (preg_match('/大学/', $value)) {
        $statement1->bindValue(':category', 7, PDO::PARAM_INT);
    } else {
        $statement1->bindValue(':category', 8, PDO::PARAM_INT);
    }

    $statement1->bindValue(':office', $data[9], PDO::PARAM_INT);
    $statement1->bindValue(':busho', $data[10], PDO::PARAM_INT);

    $value = $data[4];
    if (preg_match('/勤務/', $value)) {
        $statement1->bindValue(':renraku', 1, PDO::PARAM_INT);
    } else {
        $statement1->bindValue(':renraku', 2, PDO::PARAM_INT);
    }

    $statement1->bindValue(':post', $data[5], PDO::PARAM_STR);
    $statement1->bindValue(':address', $data[6], PDO::PARAM_STR);
    $statement1->bindValue(':bilname', $data[7], PDO::PARAM_STR);
    $statement1->bindValue(':tel', $data[8], PDO::PARAM_STR);
    $statement1->bindValue(':atena', $data[16], PDO::PARAM_STR);
    
    $statement1->bindValue(':fee3', NULL, PDO::PARAM_NULL);
    $statement1->bindValue(':fee2', NULL, PDO::PARAM_NULL);
    $statement1->bindValue(':fee1', NULL, PDO::PARAM_NULL);
    $statement1->bindValue(':fee', NULL, PDO::PARAM_NULL);
    $statement1->bindValue(':billmeans', $billmeans, PDO::PARAM_INT);
    $statement1->bindValue(':receiptmeans', $receiptmeans, PDO::PARAM_INT);
    $statement1->bindValue(':receiptsend','2020-01-01',PDO::PARAM_STR);
    $statement1->execute();
}

//メインルーチン

//セッションスタート
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


//セッション変数のイニシャライズ
//同一名または同一メールADのレコードが既にあるレコード
$_SESSION['e_name'] = [];
$_SESSION['e_mail'] = [];

//今日の日付の月
date_default_timezone_set('Asia/Tokyo');//標準時間を日本に合わせる
$todayMm = date('n');
//ＤＢと接続
$pdo = connect();

//nendoTBを読込み、今年度、翌年度を取り込む
$statemrnt4 = $pdo->prepare('SELECT * FROM nendo');
$statemrnt4->execute();
$nendo = $statemrnt4->fetch(PDO::FETCH_ASSOC);
$yokunendo = $nendo['fee'];

//ｃｓｖファイルを読み込みregularTable(ＤＢ)を更新する
// 読み取り専用でtest.csvを開きます。
$file = './aaaa/正会員申請書/【NPO 日本メディカルライター協会】　正会員入会申請書.csv';//GoogleFormのCSVファイル名に一致させる

if (file_exists($file)) {
    $f = fopen($file, "r");
} else {
    die("aaaa/正会員申請書/【NPO 日本メディカルライター協会】　正会員入会申請書.csvがありません。<br>GoogleFormより【NPO 日本メディカルライター協会】　正会員入会申請書。CSVファイルをaaaa/正会員申請書/へセットしてから再度実行してください。");

}

// 正会員入会申請書.csvの行を1行ずつ読み込みます。1行目はスキップ
$data = fgetcsv($f);//1行目スキップ

//今回の追加レコードの最初のidを保存し、PWのインプットに使用する
 $nextid = 0;
 getmaxid($pdo,$nextid);
 $startid = $nextid;
 $_SESSION['startid'] = $startid;
 $n = 0;
 $n1 = 0;

while($data = fgetcsv($f)){
$billmeans = 1;
$receiptmeans = 1;    
searchmeans($data,$billmeans,$receiptmeans);
searchkaiinid($pdo,$data,$kaiin_id);   
if ($kaiin_id == 9999){
    $n++;
    addRegular($pdo,$data,$billmeans,$receiptmeans,$yokunendo);
}else{
    $_SESSION['e_mail'][] = $data[1];//2重申請者のメールアドレス
    $_SESSION['e_name'][] = $data[2];//2重申請者の名前
    $n1++;
}
}
    
//新規登録者のPW処理：一度正会員TBを更新後再度正会員TBを呼び込みPW処理をする

//新規登録件数０の場合処理終了
if ($n <= 0 && $n1 <= 0){
    echo '新規登録する申請書は0件です';
        exit;
}
if ($n <= 0 && $n1 > 0){
    header('Location: r_bill.php');
            exit();
}
//正会員TBLの更新前の最大id+１以降の正会員ＴＢＬを読み込む
$statement2 = $pdo->prepare('SELECT name,id FROM regular WHERE id >= :id');
$statement2->bindValue(':id', $startid, PDO::PARAM_INT);
$statement2->execute();

/*
//r-pwSet.phpでPWのセットと、様または御中が抜けているレコードに様または御中を入れる
echo '<h3>新規入会者氏名の下にPWをコピペしてください</h3>'; 
*/

//r-pwSet.phpで様または御中が抜けているレコードに様または御中を入れる
echo '<form action="r_pwSet.php" method="post">';
//正会員マスターのPWがなくなったための修正

/*
$_SESSION['id_list'] = [];
while ($row = $statement2->fetch(PDO::FETCH_ASSOC)) {

    echo '<p>新規入会者氏名: ' . htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p style="display: inline;">パスワード</p>';
    echo '<input type="text" name="pw_' . $row['id'] . '" value="">';
    
    $_SESSION['id_list'][] = $row['id'];// 新規入会者のIDを配列で保持するように変更
}
*/

// $n：新規登録した件数
if ($n > 0) {
    $_SESSION['n'] = $n;
}

//新規登録者の様、御中のない宛名を表示しラジオボタンで選択する



$statement3 = $pdo->prepare('SELECT id, atena FROM regular WHERE withdraw = 1 AND id >= :id');
$statement3->bindValue(':id', $startid, PDO::PARAM_INT);
$statement3->execute();

$n = 0;
$_SESSION['atena_list'] = []; // 初期化だけしておく

while ($row = $statement3->fetch(PDO::FETCH_ASSOC)) { 
    if (!preg_match('/様|御中/u', $row['atena'])) { 
        $_SESSION['atena_list'][] = [
            'id' => $row['id'],
            'atena' => $row['atena']
        ];
        $n++;    
    }
}

//$nは様も御中も入っていない宛名の数

if ($n > 0) {    
    echo '<p>宛名に様も御中もないレコードがあります。様か御中を入れてください</p>';
    foreach ($_SESSION['atena_list'] as $item) {
        $id = $item['id'];
        $atena = $item['atena'];
        echo "<p>id={$id} 宛名={$atena}</p>";
        echo "<input type='radio' name='atena_{$id}' value='様'> 様";
        echo "<input type='radio' name='atena_{$id}' value='御中'> 御中";
    }
    echo '<p></p>';
    echo '<input type="submit" value="送信">';
}


// 正会員入会申込書.csvを閉じます。
fclose($f);                                                           
//DBを閉じます
$pdo = null;


?>