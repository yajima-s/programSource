<?php

// セミナーIDが4桁の半角数字かチェック
function isFourDigitNumber($value) {
    return preg_match('/^\d{4}$/', $value) === 1;
}


$value = $_POST['seminarId'];
    if (isFourDigitNumber($value)) {
        $seminarId = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    } else {
        header('Location: s_id_in.php?error=セミナーＩＤは半角4桁です');
        exit; 
    }

?>

