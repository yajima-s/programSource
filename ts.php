<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>年度集計</title>
</head>
<body>

<?php
if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
?>

<h1>対象年度のインプットと、処理を１つ選んでください</h1>

<div id="choice"> 
    <form action="ts_furiwake.php" method="post">
        <p>(1) 終わった年度または終わろうとしている年度を４桁の半角数字で入力してください。</p>
        <label><input type="text" name="nendo" value=""></label><br>

        <p>(2) 処理を選択してください。（繰り越し処理は1年に1回のみ。レポート作成は何回でも実行できます。）</p>

        <label><input type="radio" name="choice" value="期末繰越処理"> 期末繰越処理</label><br>
        <label><input type="radio" name="choice" value="期末レポート処理"> 期末レポート処理</label><br>

        <input type="submit" value="選択">
    </form>
</div>

</body>
</html>
