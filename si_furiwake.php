<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['choice'])) {
        $choice = $_POST['choice'];
        $choice = htmlspecialchars($choice, ENT_QUOTES, 'UTF-8');
        if ($choice == "シニア会員ワークファイル取込") {
            header('Location:si_old_convert.php');
            exit();
        }elseif ($choice == "受付簿DB取込"){
            header('Location: si_year_input.php');
            exit();
        }elseif ($choice == "シニアポイントレポート作成") {
            header('Location: si_report.php');
            exit();
        
        } else {
           header('Location: si.html?error=選択肢が選ばれていません。');
          exit(); 
        }
} else {
    header('Location: si.html?error=送信内容がありません。');
          exit(); 
}
}

?> 