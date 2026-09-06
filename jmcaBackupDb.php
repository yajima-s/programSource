<?php
if (session_status() == PHP_SESSION_NONE) {
    // セッションを開始
    session_start();
}

// バックアップ処理が進行中かどうかを確認するフラグ
if (isset($_SESSION['backup_in_progress']) && $_SESSION['backup_in_progress'] === true) {
    echo "バックアップ処理が進行中です。しばらくお待ちください。";
    exit;
}

$_SESSION['backup_in_progress'] = true; // バックアップ処理中フラグを設定

// データベース接続情報
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'jmca';

$backupDir = 'C:/backup/';
date_default_timezone_set('Asia/Tokyo'); // 標準時間を日本に合わせる
$now = date('Y-m-d-H-i-s'); 
$backupFile = $backupDir . 'jmca_backup_' . $now . '.sql';
$mysqldumpPath = 'C:/xampp/mysql/bin/mysqldump.exe';

// パスワードが空でない場合のみ、-pオプションを追加
$command = "\"$mysqldumpPath\" -h$dbHost -u$dbUser" . ($dbPass !== '' ? " -p$dbPass" : "") . " $dbName > \"$backupFile\"";

// プロセスを開始
$process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

if (is_resource($process)) {
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // エラーチェック
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $return_var = proc_close($process);

    if ($return_var === 0) {
        echo "データベースバックアップが正常に完了しました: $backupFile";
    } else {
        echo "バックアップ中にエラーが発生しました。";
        echo "エラーメッセージ: $errors";
    }
} else {
    echo "バックアップの実行に失敗しました。";
}

// フラグをリセット
$_SESSION['backup_in_progress'] = false;

header('Location: jmcaBackupXampp.php');
exit();

?>
