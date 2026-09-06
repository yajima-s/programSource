<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>正会員年会費入金処理</title>
</head>
<body>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
if (isset($_GET['message'])) {
    echo htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8');
}
$_GET['error'] = NULL;
$_GET['message'] = NULL;
$_SESSION['nameArray'] =[];
$_SESSION['officeArray'] = [];
$_SESSION['feeArray'] = [];
$_SESSION['fee1Array'] = [];
$_SESSION['fee2Array'] = [];    
$_SESSION['noteArray'] = [];
$_SESSION['maxcount'] = 0;
$_SESSION['kensakuName'] = NULL;
?>
<h3>正会員年会費入金処理</h3>
<h3>処理を１つだけ選んでください（２つは選べません）</h3>
<p>氏名または会社名で入金者を検索。氏名、会社名の一部分でも可：部分マッチングをします。</p>
<p>終了する場合は終了（e)を入力、</p>

<div id="choice"> 
    
<form action="r_pay_search.php" method="post">
<p>氏名</p>
<input type="text" name="name" value="">
<p>会社名</p>
<input type="text" name="office" value="">
<p>終了（eをインプット）</p>
<input type="text" name="end" value="">
<p></p>
<input type="submit" value="送信">

</form>  
</div>
</body>
</html>
