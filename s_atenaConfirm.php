<?php
// セッションスタート
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // セッションを開始
}
if (!isset($_SESSION['seminarId'])) {
    echo "セッションseminarIdがセットされていません";
    exit;
}

// サブルーチン：DB接続
function connect() {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
}

// メイン処理
$seminarId = $_SESSION['seminarId'];
$nameArray = [];
$officeArray = [];
$atenaArray = [];
$count = 0;

// DB接続
try {
    $pdo = connect();
} catch (PDOException $e) {
    echo "DB接続に失敗しました。" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

$stmt = $pdo->prepare('SELECT atena, name, office FROM uketukebo WHERE seminarid = :seminarid AND kubun < 9');
$stmt->bindValue(':seminarid', $seminarId, PDO::PARAM_INT);
$stmt->execute();

while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {

    // null 安全 2026/09/03に修正
    $atena = $result['atena'] ?? "";

    // 様・御中が含まれていればスキップ　UTF－8モードの[u]をつける2026/09/03に修正
    if (mb_strpos($atena, '様') !== false || mb_strpos($atena, '御中') !== false) {
    continue;
    }

    // 含まれていない場合だけ配列に追加
    $nameArray[$count]  = $result['name'];
    $officeArray[$count] = $result['office'];
    $atenaArray[$count] = $atena;
    $count++;
}


// セッションに保存
//$_SESSION['count'] = $count - 1; s_atenaModify.phpphpで使用していないため削除　2026/09/03
$_SESSION['name'] = $nameArray;
$_SESSION['office'] = $officeArray;
$_SESSION['atena'] = $atenaArray;

// フォーム出力
if ($count >= 1){
    echo '<body>';
    echo '<form action="s_atenaModify.php" method="post">';
    echo '<p>宛名に「様」も「御中」も入っていない応募です</p>';
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>氏名</th>';
    echo '<th>会社名</th>';
    echo '<th>宛名</th>';
    echo '<th>1：様　2：御中</th>';
    echo '</tr>';

    for ($i = 0; $i < $count; $i++) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($nameArray[$i], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($officeArray[$i], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($atenaArray[$i], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>';
        echo '<select name="atena_option_' . $i . '">';
        echo '<option value="2">2</option>';
        echo '<option value="1">1</option>';
        echo '</select>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';
    echo '<input type="submit" value="送信">';
    echo '</form>';
    echo '</body>';
}else {
    header('Location: s_bill.php');
    exit;
}

?>
