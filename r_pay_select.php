<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>JMCAシステム</title>
</head>
<body>
<?php
if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
    
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

$fee  = (int) $result2['fee'];
$fee1 = (int) $result2['fee1'];
$fee2 = (int) $result2['fee2'];

    
if ($_SESSION['nameArray']){
    echo '<body>';
    if (isset($_SESSION['kensakuName']) && $_SESSION['kensakuName'] !== "") {
        echo '<h3>' . htmlspecialchars($_SESSION['kensakuName'], ENT_QUOTES, 'UTF-8') . 'での検索結果</h3>';

    } elseif (isset($_SESSION['kensakuOffice']) && $_SESSION['kensakuOffice'] !== "") {
        echo '<h3>' . htmlspecialchars($_SESSION['kensakuOffice'], ENT_QUOTES, 'UTF-8') . 'での検索結果</h3>';

            } else {
                    $message = "氏名又は会社名をインプットしてください。";
                    header('Location: r_pay_in.php?error=' . urlencode($message));
                    exit();
                    }
    echo '<span style="color:red;">注意　同一レコードに対して複数年度の入金を連続して処理できますが、領収書は一番最後の年度の入金しか出ません。</span>';
    echo '<br>';
    echo '<span style="color:red;">    1件入金処理後に終了（e）をし、領収書を出した後、再度入金処理を行ってください。</span>';
    echo '<br>';
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>番号</th>';
    echo '<th>氏名</th>';
    echo '<th>会社名</th>';
    echo '<th>1:' . $fee2 . '年度入金日</th>';
    echo '<th>2:' . $fee1 . '年度入金日</th>';
    echo '<th>3:' . $fee . '年度入金日</th>';    
    echo '<th>備考</th>';
    echo '</tr>';
    echo '<br>';
    
    $count = 0;

    while (isset($_SESSION['nameArray'][$count])) {
        $count1 = $count+1;
        $nameArray = $_SESSION['nameArray'][$count];
        $officeArray = $_SESSION['officeArray'][$count];
        $feeArray = $_SESSION['feeArray'][$count];
        $fee1Array = $_SESSION['fee1Array'][$count];
        $fee2Array = $_SESSION['fee2Array'][$count];
        $noteArray = $_SESSION['noteArray'][$count];
       
        echo '<tr>';
        echo '<td>' . $count1 . '</td>';
        echo '<td>' . htmlspecialchars($nameArray) . '</td>';
        echo '<td>' . htmlspecialchars($officeArray) . '</td>';
        echo '<td>' . htmlspecialchars($fee2Array ?? "") . '</td>';
        echo '<td>'. htmlspecialchars($fee1Array ?? "") . '</td>';
        echo '<td>'. htmlspecialchars($feeArray ?? "") . '</td>';
        echo '<td>' . htmlspecialchars($noteArray ?? "") . '</td>';
        echo '</tr>';
       $count++; 
    
    }
    
    echo '</table>';
    echo '</body>';
    
}else{ //$_SESSION['nameArray']に何も入っていない場合
      $message = $_SESSION['kensakuName'] . "は、正会員マスターに存在しません。";
        header('Location: r_pay_in.php?error=' . urlencode($message));
        exit();
  }

?>
    <p>該当番号と入金日を入力ください。他の人の検索をしたい場合は、再検索の枠にｍを入力してください。</p>
    <p>終了の場合は終了の枠にeを入力してください。（全てに小文字です）</p>
    <div id="choice"> 
    <form action="r_pay_set.php" method="post">
        <p>番号(半角)</p>
        <input type="text" name="count" value="">
        <p>何年度分の入金か１～３(半角)の数字を選択</p>
        <input type="text" name="nendo" value=""> 
        <p>入金日（yyyy/mm/dd)</p>
        <input type="text" name="paid" value="">
        <p>再検索(mを入力）</p>
        <input type="text" name="more" value="">
        <p>終了(eを入力）</p>
        <input type="text" name="end" value="">
        <p></p>
        <input type="submit" value="送信">
        <br><br><br>  
    </form>
    </div>
    </body>

</html>
