<?php
//賛助会員マスターの繰越処理：feeを１つ右にずらす。fee,billsend,receiptsend,continue1にNULLを入れる
// サブルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
// jmcaDBを開きます
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

// メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー

if (session_status() == PHP_SESSION_NONE) {
    // セッションを開始
    session_start();
}
// DBに接続
$pdo = connect();

    $stmt = $pdo->prepare('SELECT * FROM support WHERE withdraw = 1');
    $stmt->execute();

    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $id = $result['id'];
        $fee1 = $result['fee'];
        $fee2 = $result['fee1'];
        $fee3 = $result['fee2'];

        $stmt1 = $pdo->prepare('UPDATE support SET fee3 = :fee3, fee2 = :fee2, fee1 = :fee1,
                                fee = NULL, billsend = NULL, receiptsend = NULL, continue1 = NULL
                                WHERE id = :id');
        $stmt1->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt1->bindValue(':fee3', $fee3, PDO::PARAM_STR);
        $stmt1->bindValue(':fee2', $fee2, PDO::PARAM_STR);
        $stmt1->bindValue(':fee1', $fee1, PDO::PARAM_STR);
        $stmt1->execute();
    }
    $_SESSION['list'][] = "賛助会員マスターの繰越処理はおわりました。";

// 処理完了
$pdo = NULL;
echo "期末繰り越し処理が完了しました"；
echo "年度TBの全ての年度（来年度、今年度、前年度、前々年度にそれぞれ1をプラスする";
echo "3期連続：前期、前々期、前々前期）連続未入金者削除(withdraw＝2";
echo "正会員TBの入金日（fee,fee1,fee2,fee3)を1つずらす。"；
echo "賛助会員TBの入金日（fee,fee1,fee2,fee3)を1つずらす";
?>
