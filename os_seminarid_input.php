<!-- 旧受付簿（exelファイル）のファイル名を決定するため、セミナーID（数字4桁）をインプット -->

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>旧受付簿をDBにコンバート</title>
</head>
<body>
<?php
if (isset($_GET['error'])) {
    echo '<p style="color:red;">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
?>
<h1>セミナーID半角４桁を入力してください</h1>
<p>旧フォーマットの受付簿を読込むプログラムで、新フォーマットには対応していません</p>
<p>読込む旧受付簿から同一キー（名前）は、最初のレコードが優先され、後のレコードは読み飛ばされる</p>
<p>金額、請求書の発送日、領収書の発送日は事前に入れておいてください</p>
<p>以上の準備ができていない場合はやめてください</p>
<p>当処理では請求書は発行、警告リストの発行はしません</p>
<div id="choice"> 
    <form action="os_convert.php" method="post">
        <input type="text" name="seminar_id" value="">
        <input type="submit" value="送信">
        <br><br><br>  
    </form>
</div>

</body>
</html>
