<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['choice'])) {
        $choice = htmlspecialchars($_POST['choice'], ENT_QUOTES, 'UTF-8');
        
        if ($choice == "excel") {
            header('Location: jmcaBackupExel.php');
            exit();
        } elseif ($choice == "db") {
            header('Location: jmcaBackupDb.php');
            exit();
        } elseif ($choice == "xampp") {
            header('Location: jmcaBackupXampp.php');
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