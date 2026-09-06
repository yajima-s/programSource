<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['choice'])) {
        $choice = htmlspecialchars($_POST['choice'], ENT_QUOTES, 'UTF-8');
        
        if ($choice == "excel") {
            header('Location: GeneralOubo_exelToDb.php');
            exit();
        } elseif ($choice == "adddb") {
            header('Location: GeneralOubo_nendoIn.php');
            exit();
        } elseif ($choice == "exceloutput") {
            header('Location: GeneralOubo_exelOutput.php');
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
