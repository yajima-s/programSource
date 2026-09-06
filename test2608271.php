<?php

//セッッションスタート
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<pre>";
echo "session_id = " . session_id() . "\n";
print_r($_COOKIE);
print_r($_SESSION);
echo "</pre>";



require './vendor/autoload.php';


$_SESSION['seminarId'] = 2606;
$_SESSION['count'] = 100;
echo '<p>該当番号を1つ選択してください。</p>';
echo '<form action="test2608272.php" method="post">';
echo '<p>番号(小文字）</p>';
echo '<input type="text" name="count" value="">';
echo '<input type="submit" value="送信">';
echo '<br><br><br>';
echo '</form>';
 
?>

