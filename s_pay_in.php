<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>セミナー参加費入金処理</title>
    <link rel="stylesheet" href="style.css" type="text/css">
</head>
<body>
<?php
if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
if (isset($_GET['message'])) {
    echo htmlspecialchars($_GET['message']);
}
$_GET['error'] = NULL;
$_GET['message'] = NULL;
//sessionの開始
if (session_status()===PHP_SESSION_NONE){
    session_start();
}
$_SESSION['pay'] = 0;//0:入金分未入金分全てを表示　1:入金済のみ表示、2:未入金のみ表示
$_SESSION['nameArray'] = [];
$_SESSION['seminaridArray'] = [];
$_SESSION['officeArray'] = [];
$_SESSION['withtaxArray'] = [];
$_SESSION['paidArray'] = [];
$_SESSION['noteArray'] = [];
$_SESSION['kensakuName'] = "";
$_SESSION['kensakuOffice'] = "";
    
?>
<h1>処理を１つだけ選んでください（２つは選べません）</h1>
<p>氏名または会社名で入金者を検索：氏名、会社名の一部分でも可：一部マッチングをします。終了する場合は終了、　</p>
<p>氏名　　　　　　　　　会社名　　　　　　　　　        終了(eを入力）    </p>
<div id="choice"> 
    <form action="s_pay_search.php" method="post">
        <input type="text" name="name" value="">
        <input type="text" name="office" value="">
         <input type="text" name="end" value="">
        <input type="submit" value="送信">
        <br><br><br>  
    </form>
</div>
</body>
</html>

    
