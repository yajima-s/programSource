<?php
// 正会員マスターの繰越処理：feeを１つ左にずらす。fee, billsend, receiptsendにNULLを入れる
// jmcaDBを開く
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


//メインルーチン
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// DBに接続
$pdo = connect();

// 年度管理テーブル（nendo）の更新
$stmt2 = $pdo->prepare('SELECT * FROM nendo');
$stmt2->execute();
$result2 = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$result2) {
    echo '年度管理テーブルにデータがありません';
    exit;
}
if ($result2['fee1'] !== $_SESSION['nendo']){
    echo "年度管理TBの今年度が終ったまたは終ろうとしている入力した年度と異なります。確認してやり直してください。";
    echo "年度管理TBの今年度＝".$result2['fee1'];
    echo " 入力した年度＝".$_SESSION['nendo'];
    exit;
}
$id = $result2['id'];
$fee  = (int) $result2['fee']  + 1;
$fee1 = (int) $result2['fee1'] + 1;
$fee2 = (int) $result2['fee2'] + 1;
$fee3 = (int) $result2['fee3'] + 1;

$stmt3 = $pdo->prepare("UPDATE nendo 
                        SET fee = :fee, fee1 = :fee1, fee2 = :fee2, fee3 = :fee3  
                        WHERE id = :id");
$stmt3->bindValue(':id', $id, PDO::PARAM_INT);
$stmt3->bindValue(':fee', $fee, PDO::PARAM_INT);
$stmt3->bindValue(':fee1', $fee1, PDO::PARAM_INT);
$stmt3->bindValue(':fee2', $fee2, PDO::PARAM_INT);
$stmt3->bindValue(':fee3', $fee3, PDO::PARAM_INT);
$stmt3->execute();



// 正会員マスター（regular）のデータを取得
$stmt = $pdo->prepare('SELECT * FROM regular');
$stmt->execute();

while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id = $result['id'];
    $fee  = $result['fee'];  // feeを保持しておく
    $fee1 = $result['fee1'];
    $fee2 = $result['fee2'];
    $fee3 = $result['fee3'];
    $billsend = $result['billsend'];

    // データを更新（feeを左にずらし、新しいfeeはNULL）
    $stmt1 = $pdo->prepare('UPDATE regular 
                            SET fee3 = :fee3, fee2 = :fee2, fee1 = :fee1, fee = NULL,
                                billsend = :billsend 
                            WHERE id = :id');
    $stmt1->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt1->bindValue(':fee3', $fee2, PDO::PARAM_STR);
    $stmt1->bindValue(':fee2', $fee1, PDO::PARAM_STR);
    $stmt1->bindValue(':fee1', $fee, PDO::PARAM_STR);

    // fee1 が NULL または 空文字の場合、billsend を NULL にする
    if (is_null($fee) || $fee === "" || $fee === '0000-00-00') {
        $stmt1->bindValue(':billsend', NULL, PDO::PARAM_NULL);
    } else {
        //翌年度の年会費を既に払ている人
        $stmt1->bindValue(':billsend', $billsend, PDO::PARAM_STR);
    }
    
    $stmt1->execute();
}



// 処理完了後、リダイレクト

header('Location: ts_kurikoshi_support.php');
exit();
?>
