<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>年度入力</title>
<style>
body { font-family: sans-serif; }
.container {
    width: 400px;
    margin: 50px auto;
    padding: 20px;
    border: 1px solid #666;
    border-radius: 8px;
}
label, input { font-size: 18px; }
.error { color: red; margin-bottom: 10px; }
</style>
</head>
<body>

<div class="container">
    <h2>年度の入力</h2>

    <?php if (isset($_GET['error'])): ?>
        <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <form action="an_check.php" method="post">
        <p>対象年度（西暦4桁）を入力してください。</p>
        <label>
            <input type="text" name="nendo" maxlength="4" required>
        </label>
        <br><br>
        <input type="submit" value="送信">
    </form>
</div>

</body>
</html>
