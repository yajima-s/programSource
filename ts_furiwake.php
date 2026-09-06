<?php
session_start();

// POSTチェック
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: ts.html?error=METHODがPOSTになっていません。');
    exit();
}

// 年度チェック
if (!isset($_POST['nendo']) || !is_numeric($_POST['nendo'])) {
    header('Location: ts.html?error=年度は数字４桁です');
    exit();
}

$nendo = (int) $_POST['nendo'];

if ($nendo < 2023 || $nendo > 2055) {
    header('Location: ts.html?error=年度は2023~2055の間です');
    exit();
}

$_SESSION['nendo'] = $nendo;

// 処理選択チェック
if (!isset($_POST['choice'])) {
    header('Location: ts.html?error=処理を選択してください');
    exit();
}

$choice = $_POST['choice'];

if ($choice === "期末繰越処理") {
    header('Location: ts_delete_mibarai.php');
    exit();

} elseif ($choice === "期末レポート処理") {
    header('Location: ts_regular.php');
    exit();

} else {
    header('Location: ts.html?error=処理を選択してください');
    exit();
}

