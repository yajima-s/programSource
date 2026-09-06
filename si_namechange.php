<?php
// si_namchange.php

// DB接続
try {
    $pdo = new PDO(
        'mysql:host=localhost; dbname=jmca; charset=utf8mb4',
        'root',
        ''
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

// 更新件数カウンタ
$updateCount = 0;

// sinior 全件取得
$sql = "SELECT name, mail, seminar_id FROM sinior";
$stmt = $pdo->query($sql);
$uketukeList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 1件ずつ処理
foreach ($uketukeList as $row) {

    $uname = $row['name'];
    $umail = $row['mail'];
    $useminar_id =$row['seminar_id'];

    // ① mail で regular を検索
    $sql = "SELECT name, mail FROM regular WHERE mail = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$umail]);
    $regMail = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($regMail) {
        // mail が一致する regular が存在

        if ($regMail['name'] !== $uname) {
            // ★ 追加：同じ name + seminar_id が既に存在するかチェック
            $sql = "SELECT 1 FROM sinior WHERE name = ? AND seminar_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$regMail['name'], $row['seminar_id']]);
            $exists = $stmt->fetchColumn();
            if ($exist){
                 // ★ 先に今のレコードを削除する（ここが重要）
                $sql = "DELETE FROM sinior WHERE name = ? AND seminar_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$uname, $row['seminar_id']]);
                continue;
                }
            
            // 名前が違う → sinior.name を regular.name に変更し更新
            $sql = "UPDATE sinior SET name = ? WHERE mail = ? AND seminar_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$regMail['name'], $umail, $useminar_id]);
            $updateCount++;
        }

        continue; // 次のレコードへ
    }

// ② mail が存在しない → name で regular を検索
$sql = "SELECT name, mail FROM regular WHERE name = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$uname]);
$regName = $stmt->fetch(PDO::FETCH_ASSOC);

if ($regName) {

    // ★ 追加：同じ mail + seminar_id が既に存在するかチェック
    $sql = "SELECT 1 FROM sinior WHERE mail = ? AND seminar_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$regName['mail'], $row['seminar_id']]);
    $exists = $stmt->fetchColumn();

    if ($exists) {

        // ★ 先に今のレコードを削除する（ここが重要）
        $sql = "DELETE FROM sinior WHERE mail = ? AND seminar_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$umail, $row['seminar_id']]);

        continue;
    }

    // ★ 重複がない場合のみ UPDATE
    $sql = "UPDATE sinior SET mail = ? WHERE name = ? AND seminar_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$regName['mail'], $uname, $useminar_id]);
    $updateCount++;
}

    // ③ name でも見つからない場合は何もしない
}

// 結果表示
echo "更新件数： " . $updateCount . " 件\n";
echo "続行するには何かキーを押してください...";


// si_repot.php に移動
header("Location: si_report.php");
exit;

?>
