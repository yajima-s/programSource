<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['choice'])) {
        $choice = $_POST['choice'];
        $choice = htmlspecialchars($choice, ENT_QUOTES, 'UTF-8');
        if ($choice == "セミナー請求書再発行") {
            header('Location: s_bill.php');
            exit();
        }elseif ($choice == "正会員年会費請求書再発行"){
            header('Location: re_startid_in.php');
            exit();
        
        } else {
           header('Location: re_process.html?error=選択肢が選ばれていません。');
          exit(); 
        }
} else {
    header('Location: re_process.html?error=送信内容がありません。');
          exit(); 
}
}

?> 