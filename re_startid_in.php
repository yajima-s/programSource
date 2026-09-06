<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スタートIDインプット</title>
   
</head>
<body>
<?php
if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
<h1>スタートID（請求書を出したい会員の最初のID）を入力して下さい。</h1>
<div id="choice"> 
    <form action="re_bill.php" method="post">
        <input type="text" name="startid" value="">
        <input type="submit" value="送信">
        <br><br><br>  
    </form>
</div>

</body>
</html>
