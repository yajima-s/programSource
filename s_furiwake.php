<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['choice'])) {
        $choice = $_POST['choice'];
        $choice = htmlspecialchars($choice, ENT_QUOTES, 'UTF-8');
        if ($choice == "応募データ処理") {
            header('Location: s_id_in.php');
            exit();
        }elseif ($choice == "セミナー参加費入金処理"){
            header('Location: s_pay_in.php');
            exit();
        }elseif ($choice == "正会員新規入会処理") {
            header('Location: r_register.php');
            exit();
        }elseif ($choice == "年会費入金処理") {
            header('Location: r_pay_in.php');
            exit();
        } else {
           header('Location: jmca.html?error=選択肢が選ばれていません。');
          exit(); 
        }
} else {
    header('Location: jmca.html?error=送信内容がありません。');
          exit(); 
}
}

?> 