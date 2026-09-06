<?php
//新規登録の正会員TBのPW。宛先に様、御中をセットする
//2重登録、4月~12月に次年度を選択したエラーリストは、r_bill.php
//サブルーチン
function connect() {
    try{
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
    }catch (PDOException $e){
        echo 'Connection failed:'.$e->getMessage();
        exit;
    }
}

//メインルーチン
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//ＤＢと接続
$pdo = connect();

//2026年3月　PWはなくなったためPW処理は除去
/*
//PWを正会員マスターへ書き込み
foreach ($_SESSION['id_list'] as $id) {
    $stmt = $pdo->prepare('UPDATE regular SET pw = :pw WHERE id = :id');
    $stmt->bindValue(':pw', $_POST["pw_$id"], PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
}
*/

//宛名に様も御中も入っていないレコードに様または御中を書き込む
foreach ($_SESSION['atena_list'] as $item) {
    $id = $item['id'];
    $atena = $item['atena']; 

    $stmt1 = $pdo->prepare('UPDATE regular SET atena = :atena WHERE id = :id');
    $stmt1->bindValue(':id', $id, PDO::PARAM_INT); // IDは数値なら PARAM_INT
    $correctAtena = $atena . $_POST["atena_{$id}"];
    $stmt1->bindValue(':atena', $correctAtena, PDO::PARAM_STR);
    $stmt1->execute();
}

/*foreach ($_SESSION['atena_list'] as $id => $atena) {
    $stmt1 = $pdo->prepare('UPDATE regular SET atena = :atena WHERE id = :id');
    $stmt1->bindValue(':id', $id, PDO::PARAM_STR);
    $correctAtena = $atena.$_POST["atena_$id"];
    $stmt1->bindValue(':atena', $correctAtena, PDO::PARAM_STR);
    $stmt1->execute();
}*/
    

//既に正会員マスターに登録されている名前またはメールADで正会員入会を申請したレコード
//4月~12月に次年度入会選択のエラーとして表示するのは、r_bill.phpです
header('Location: r_bill.php');
exit();
?>


