<?php
// セッションスタート
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB接続
function connect() {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
}

// セミナーIDが4桁の半角数字かチェック
function isFourDigitHalfWidthNumber($seminarId) {
    return preg_match('/^[0-9]{4}$/', $seminarId) === 1;
}

// セミナーIDの存在チェック→存在可否、セミナー名、複数回かを返す
function seminarId_dbcheck($pdo, $seminarId, &$plural, &$seminarName){
    $statement2 = $pdo->prepare('SELECT * FROM seminar WHERE id = :id');
    $statement2->bindValue(':id', $seminarId, PDO::PARAM_STR);
    $statement2->execute();
    $result = $statement2->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        header('Location: s_id_in.php?error=正しいセミナーIDを再インプットしてください');
        exit;
    }
    $seminarName = $result['name'];
    $plural = $result['plural'];
    if ($plural == 2) {
    $_SESSION['id1'] = $result['id1'];
    $_SESSION['id2'] = $result['id2'];
    $_SESSION['id3'] = $result['id3'];
    $_SESSION['id4'] = $result['id4'];
}else{
    $_SESSION['id1'] = 0;
    $_SESSION['id2'] = 0;
    $_SESSION['id3'] = 0;
    $_SESSION['id4'] = 0;

}
    
    return $result;
}

// DB接続
try {
    $pdo = connect();
} catch (PDOException $e) {
    echo "DB接続に失敗しました。" . $e;
    exit;
}

// セミナーIDの形式チェック
if (!isset($_POST['seminarId'])) {
    header('Location: s_id_in.php?error=セミナーＩＤが入力されていません');
    exit;
}

$seminarId = $_POST['seminarId'];
$result = isFourDigitHalfWidthNumber($seminarId);
if ($result === 0) {
    header('Location: s_id_in.php?error=セミナーＩＤが半角４桁の数字ではありません。');
    exit;
}

// seminarDBの存在チェック、複数回かを返す
$plural = 0;
$seminarName = "";
seminarId_dbcheck($pdo, $seminarId, $plural, $seminarName);



// 何回目かをチェック
if (!isset($_POST['kaisu'])) {
    header('Location: s_id_in.php?error=何回目かが入力されていません');
    exit;
}

$kaisu = $_POST['kaisu'];
if ($kaisu > 4 || $kaisu < 1) {
    header('Location: s_id_in.php?error=何回目かは１～４の範囲の数字です');
    exit;
}

if ($plural == 1 && $kaisu != 1) {
    header('Location: s_id_in.php?error=単独セミナーで、１回目以外はありません');
    exit;
}

$_SESSION['seminarId'] = $seminarId;
$_SESSION['seminarName'] = $seminarName;
$_SESSION['plural'] = $plural;
$_SESSION['kaisu'] = $kaisu;

//セミナーID、セミナー名、何回目を表示して、確認を取る
echo '<body>';
echo '<p>入力したセミナーID＝' . $seminarId . '</p>';
echo '<p>セミナー名＝' . $seminarName . '</p>';
echo '<p>複数回開催か(1:1回のみ　2：複数回)＝' . $plural . '</p>';
echo '<p>何回目か＝' . $kaisu . '</p>';
echo '<p>これでOKですか</p>';
echo '<form action="sm_output.php" method="post">';
echo '<label><input type="radio" name="choice" value="YES"> YES</label><br>';
echo '<label><input type="radio" name="choice" value="NO"> NO</label><br>';
echo '<input type="submit" value="送信">';
echo '</form>';
echo '</body>';
?>
