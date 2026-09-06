<?php
session_start();

function connect() {
    try {
        $pdo = new PDO(
            'mysql:host=localhost; dbname=jmca; charset=utf8mb4',
            'root',
            ''
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;

    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        exit;
    }
}


// セッションに nendo が入っていない場合はエラー
if (!isset($_SESSION['nendo'])) {
    echo "年度が設定されていません。前の画面に戻って入力してください。";
    exit;
}

$nendo = intval($_SESSION['nendo']);   // 西暦4桁
$baseId = ($nendo % 100) * 100;        // 下2桁 × 100

try {
    $pdo = connect();

    // seminar.id を使って JOIN（修正版）
    $sql = "
        SELECT 
            u.seminarid,
            u.kubun,
            u.name,
            u.mail,
            s.name AS seminar_name
        FROM uketukebo u
        LEFT JOIN seminar s ON u.seminarid = s.id
        WHERE u.seminarid > :baseId
        ORDER BY u.seminarid ASC, u.kubun ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':baseId', $baseId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "DBエラー: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>セミナー応募一覧</title>
<style>
table {
    border-collapse: collapse;
    width: 90%;
    margin: 20px auto;
}
th, td {
    border: 1px solid #666;
    padding: 8px;
}
th {
    background-color: #ddd;
}
</style>
</head>
<body>

<h2 style="text-align:center;">
    <?= htmlspecialchars($nendo) ?> 年度以降 セミナー応募一覧
</h2>

<table>
    <tr>
        <th>セミナー名</th>
        <th>区分</th>
        <th>名前</th>
        <th>メールアドレス</th>
    </tr>

<?php
if (empty($rows)):
?>
    <tr><td colspan="4" style="text-align:center;">該当データはありません</td></tr>
<?php
else:
    $prev_seminarid = null;
    $prev_kubun = null;

    foreach ($rows as $r):

        // セミナー名の表示制御
        if ($r['seminarid'] == $prev_seminarid) {
            $seminar_name = " ";
        } else {
            $seminar_name = htmlspecialchars($r['seminar_name']);
        }

        // 区分の表示制御
        if ($r['seminarid'] == $prev_seminarid && $r['kubun'] == $prev_kubun) {
            $kubun_display = " ";
        } else {
            $kubun_display = htmlspecialchars($r['kubun']);
        }
?>
    <tr>
        <td><?= $seminar_name ?></td>
        <td><?= $kubun_display ?></td>
        <td><?= htmlspecialchars($r['name']) ?></td>
        <td><?= htmlspecialchars($r['mail']) ?></td>
    </tr>
<?php
        // 次行比較用に保存
        $prev_seminarid = $r['seminarid'];
        $prev_kubun = $r['kubun'];

    endforeach;
endif;
?>

</table>

</body>
</html>
