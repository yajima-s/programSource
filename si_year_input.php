<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>受付簿をDBにコンバートするために年度をインプット</title>
   
</head>
<body>
<?php
if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
<h1>年度の下２桁を入力してください</h1>
<div id="choice"> 
    <form action="si_uketukebo_convert.php" method="post">
        <input type="text" name="year" value="">
        <input type="submit" value="送信">
        <br><br><br>  
    </form>
</div>

</body>
</html>