<?php
/*if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($_SESSION['nameArray']){
$nameArray = $_SESSION['nameArray'];
$seminaridArray = $_SESSION['seminaridArray'];
$officeArray = $_SESSION['officeArray'];
$withtaxArray = $_SESSION['withtaxArray'];
$paidArray = $_SESSION['paidArray'];
$noteArray = $_SESSION['noteArray'];
}
if ($_SESSION['kensakuName'] !== ""){
    $kensaku = $_SESSION['kensakuName'];
}
if ($_SESSION['kensakuOffice'] !== ""){
    $kensaku = $_SESSION['kensakuOffice'];
}

$choice = "";
if ($_SESSION['pay'] == 1){
    $choice = "　未入金分は";
}elseif ($_SESSION['pay'] == 2){
    $choice = "　入金分は";
}else{
    $choice = "　入金分、未入金分共に";
}
echo '<body>';
    echo '<p>' . htmlspecialchars($kensaku, ENT_QUOTES, 'UTF-8') . $choice . 'での検索結果</p>';
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>番号</th>';
    echo '<th>氏名</th>';
    echo '<th>セミナーID</th>';
    echo '<th>会社名</th>';
    echo '<th>金額</th>';
    echo '<th>入金日</th>';      
    echo '<th>備考</th>';
    echo '</tr>';
    
       $count = 1;
    while (isset($nameArray[$count])) {
        
        echo '<tr>';
        echo '<td>' . $count . '</td>';
        echo '<td>' . htmlspecialchars($nameArray[$count]) . '</td>';
        echo '<td>' . htmlspecialchars($seminaridArray[$count]) . '</td>';
        echo '<td>' . htmlspecialchars($officeArray[$count]) . '</td>';
        echo '<td>' . number_format($withtaxArray[$count]) . '</td>';
        echo '<td>'. htmlspecialchars($paidArray[$count]??"") . '</td>';
        echo '<td>' . htmlspecialchars($noteArray[$count]??"") . '</td>';
        echo '</tr>';
       $count++; 
    
    }
    
    echo '</table>';
    echo '</body>';

if ($count == 1){
        $message = $kensaku . "の" . $choice . "受付簿の中に存在しません。";
        header('Location: s_pay_in.php?error=' . urlencode($message));
        exit();
}

    
 //該当レコード、表示方法の選択   
echo '<p>該当番号と入金日を入力ください。他の検索を行う場合は、再検索（m)を、終了の場合は終了の枠にeを入力してください。</p>';
echo '<p>あるいは、未入金分のみ、または入金分のみの表示に切り替え希望をインプットして下さい。</p>';
echo '<form action="s_pay_set.php" method="post">';
echo '<p>番号(小文字)</p>';
echo '<input type="text" name="count" value="">';
echo '<p>入金日（小文字）(yyyy/mm/dd)</p>';
echo '<input type"text" name="paid" value="">';
echo '<p>他のキーで再検索（mをインプット）</p>';
echo '<input type"text" name="more" value="">';
echo '<p>終了（eをインプット）</p>';
echo '<input type"text" name="end" value="">';
echo '<p>未入金分のみ表示か、入金分のみ表示かの選択</p>';
echo '<label><input type="radio" name="choice" value="未入金分のみ表示">未入金分のみ表示</label><br>';
echo '<label><input type="radio" name="choice" value="入金分のみ表示">入金分のみ表示</label><br>';
echo '<p></p>';
echo '<input type="submit" value="送信">';
echo '<br><br><br>';
echo '</form>';   
?>
