<?php
session_start();

// POSTチェック
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: nendo_input.php?error=不正なアクセスです");
    exit;
}

// 入力チェック
if (!isset($_POST['nendo']) || !is_numeric($_POST['nendo'])) {
    header("Location: nendo_input.php?error=年度は数字4桁で入力してください");
    exit;
}

$nendo = intval($_POST['nendo']);

if ($nendo < 2023 || $nendo > 2055) {
    header("Location: nendo_input.php?error=年度は2023〜2055の間で入力してください");
    exit;
}

// セッション保存
$_SESSION['nendo'] = $nendo;

// 一覧画面へ
header("Location: an_list.php");
exit;
