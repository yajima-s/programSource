<?php
//

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<pre>";
echo "session_id = " . session_id() . "\n";
print_r($_COOKIE);
print_r($_SESSION);
echo "</pre>";

// 外部サブルーティンの読み込み
require_once './s_jmca_function.php';

$seminarId = $_SESSION['seminarId'];
$count = $_SESSION['count'];
echo "セミナーID＝".$seminarId;
echo "カウント＝".$count;
exit();

?>
