<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>読込む受付簿の年度と削除する年度をインプット</title>
    <style>
        .error {
            color: red;
        }
        .input-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<?php
if (isset($_GET['error'])) {
    echo '<p class="error">' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
}
?>

<h3>受付簿の何年度以降のデータを対象に、一般での応募者のリストを作成しますか。年度を半角数字2桁でインプットしてください。/h3>

<form action="GeneralOubo_addDb.php" method="post">
    <div class="input-group">
        <label for="nendo">年度（下2桁）：</label>
        <input type="text" id="nendo" name="nendo" value="">
    </div>
    
    <input type="submit" value="送信">
</form>

</body>
</html>

