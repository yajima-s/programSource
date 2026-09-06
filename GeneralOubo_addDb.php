<?php

function connect() {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
}

// 正会員マスターの存在チェック
function regularCheck($pdo,$mail,&$exist) {
    try {
            // メールをキーに正会員TBを検索
            $stmt2 = $pdo->prepare("SELECT 1 FROM regular WHERE mail = :mail LIMIT 1");
            $stmt2->bindParam(':mail', $mail, PDO::PARAM_STR);
            $stmt2->execute();
            $result = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $exist = 1;
            }else{
                $exist = 0;
            }
        
    } catch (PDOException $e) {
        error_log("エラー: " . $e->getMessage());
    }
}

// generalTBレコード追加
function addGeneral($pdo,$mail, $name) {
    $exist = 0;
    regularCheck($pdo,$mail,$exist);
    if ($exist == 0){
    try {

        $stmt = $pdo->prepare("SELECT * FROM general WHERE mail = :mail");
        $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            // 同一キーのレコードはない
            $stmt = $pdo->prepare("INSERT INTO general (mail, created, modified, name)
                                   VALUES (:mail, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :name)");
            $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("エラー: " . $e->getMessage());
    }
}
}

// 2桁の数字数値かチェック
function isTwoDigitNumber($value) {
    return preg_match('/^\d{2}$/', $value) === 1;
}

// メインルーチン
try {
    $pdo = connect();
} catch (PDOException $e) {
    error_log("DB接続に失敗しました: " . $e->getMessage());
    exit;
}

//generalTBの全件を削除する
$stmt = $pdo->prepare("DELETE FROM general");
$stmt->execute();

// 年度チェック
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // フォームで送信された値を取得
    $inputValue = $_POST['nendo'];

    // 入力が空かどうかをチェック
    if (empty($inputValue)) {//NULLまたは””の場合　issetではNULLだけで、””はOKになる
        header('Location: GeneralOubo_nendoIn.php?error=年度は半角2桁で入力してください');
            exit;
    }
    
    $nendo = $_POST['nendo'];
    
    if (!isTwoDigitNumber($nendo)) {
        header('Location: GeneralOubo_nendoIn.php?error=年度は半角2桁で入力してください');
        exit;
    }
}else{
    header('Location: GeneralOubo_nendoIn.php?error=年度は半角2桁で入力してください');
        exit;
}



// 受付簿DB読込み
$low = $nendo * 100;
$count1 = 0;
$count2 = 0;
$count3 = 0;

$stmt = $pdo->prepare('SELECT name, mail FROM uketukebo WHERE seminarid >= :low AND kubun = 4');
$stmt->bindParam(':low', $low, PDO::PARAM_INT);
$stmt->execute();

while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $name = $record['name'];
    $mail = $record['mail'];
    addGeneral($pdo,$mail, $name);
}

header('Location: GeneralOubo_exelOutput.php');
        exit;
?>
