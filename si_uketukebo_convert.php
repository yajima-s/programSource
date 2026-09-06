<?php
function connect() {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
} 

// セミナーIDが2桁の半角数字かチェック
function isTwoDigitHalfWidthNumber($year) {
    return preg_match('/^[0-9]{2}$/', $year) === 1;
}

// seminarTBよりセミナー情報を取得
function get_seminarInf($pdo, $seminarId, &$point, &$essential, &$plural, &$id1, &$id2, &$id3, &$id4) {
    $statement2 = $pdo->prepare('SELECT plural, point, essential,id1,id2,id3,id4 FROM seminar WHERE id = :id');
    $statement2->bindValue(':id', $seminarId, PDO::PARAM_STR);
    $statement2->execute();
    $result = $statement2->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo "uketukeboTBのセミナーIDに対応するseminarTBにレコードが無く処理を中止します。";
        exit;
    }

    $plural = $result['plural'];
    $point = $result['point'];
    $essential = $result['essential'];
    $id1 = $result['id1'];
    $id2 = $result['id2'];
    $id3 = $result['id3'];
    $id4 = $result['id4'];
}

// siniorTBにレコードを追加
function addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential) {
    $insertSql = 'SELECT COUNT(*) FROM sinior WHERE mail = :mail AND seminar_id = :seminarId';
    $stmt = $pdo->prepare($insertSql);
    $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
    $stmt->bindParam(':seminarId', $seminarId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) { //siniorTBを、mail,seminar_ｉｄで検索した結果カウントは０=存在しない
        if ($seminarId !== NULL){
        $insertSql = 'INSERT INTO sinior (mail, seminar_id, name, point, essential) VALUES (:mail, :seminarId, :name, :point, :essential)';
        $stmt = $pdo->prepare($insertSql);
        $stmt->bindParam(':mail', $mail, PDO::PARAM_STR);
        $stmt->bindParam(':seminarId', $seminarId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':point', $point, PDO::PARAM_INT);
        $stmt->bindParam(':essential', $essential, PDO::PARAM_INT);
        $stmt->execute();
    }
    }
}


// メインルーチン
if (!isset($_POST['year'])) {
    header('Location:si_year_input.php?error=年度下2桁が入力されていません');
    exit;
}

$year = $_POST['year'];
if (!isTwoDigitHalfWidthNumber($year)) {
    header('Location: s_id_in.php?error=年度の下２桁が半角２桁の数字ではありません。');
    exit;
}

if ($year < 24) {
    header('Location: s_id_in.php?error=年度の下２桁は２４以上です。');
    exit;
}

$low = $year * 100;
$high = $low + 99;

try {
    $pdo = connect();
} catch (PDOException $e) {
    echo "DB接続に失敗しました。" . $e; 
    exit;
}

$stmt = $pdo->prepare(
    'SELECT * FROM uketukebo 
     WHERE seminarid > :low 
       AND seminarid < :high 
       AND (kubun = 1 OR kubun = 9)'
);

$stmt->bindValue(':low', $low, PDO::PARAM_INT);
$stmt->bindValue(':high', $high, PDO::PARAM_INT);
$stmt->execute();
$count = 0;
$count1 =0;
while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $count++;
    $point = 0;
    $essential = 0;
    $plural = 0;
    $seminarId = $record['seminarid'];
    get_seminarInf($pdo, $seminarId, $point, $essential, $plural, $id1, $id2, $id3, $id4);
    
    $mail = $record['mail'];
    $name = $record['name'];

    $checkSql = 'SELECT * FROM sinior WHERE mail = :mail AND seminar_id = :seminarid';
    $stmt1 = $pdo->prepare($checkSql);
    $stmt1->bindParam(':mail', $mail, PDO::PARAM_STR);
    $stmt1->bindParam(':seminarid', $seminarId, PDO::PARAM_INT);
    $stmt1->execute();

    $sinior = $stmt1->fetch(PDO::FETCH_ASSOC);
    if ($sinior) {
        echo "同一レコード".$mail.$record['name'];
        $count1++;
        continue;
    }

    if ($plural == 1) {
    addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
    continue;
    }

    if ($plural == 2) {
        if (preg_match('/１回目のみ/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/２回目のみ/', $record['multi'])) {
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/３回目のみ/', $record['multi'])) {
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/４回目のみ/', $record['multi'])) {
            $seminarId = $id4;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/２回分まとめて/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
         if (preg_match('/３回分まとめて/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
         }
        if (preg_match('/４回分まとめて/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id4;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/１回目、２回目、３回目/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/１回目、２回目、４回目/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id4;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/１回目、３回目、４回目/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id4;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/２回目、３回目、４回目/', $record['multi'])) {
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id4;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/１回目、２回目/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/１回目、３回目/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/１回目、４回目/', $record['multi'])) {
            $seminarId = $id1;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id4;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/２回目、３回目/', $record['multi'])) {
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/２回目、４回目/', $record['multi'])) {
            $seminarId = $id2;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id4;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        if (preg_match('/３回目、４回目/', $record['multi'])) {
            $seminarId = $id3;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            $seminarId = $id4;
            addSiniorTB($pdo, $mail, $seminarId, $name, $point, $essential);
            continue;
        }
        
    }
}

$pdo = null;
echo "処理件数＝".$count;
echo "同一レコード件数＝".$count1;
?>
