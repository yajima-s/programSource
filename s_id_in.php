<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>受付簿処理</title>
   
</head>
<body>
<?php
if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
<h1>セミナーID半角４桁を入力してください</h1>
<div id="choice"> 
    <form action="s_getFilename.php" method="post">
        <input type="text" name="seminarId" value="">
        <input type="submit" value="送信">
        <br><br><br>  
    </form>
</div>

</body>
</html>
