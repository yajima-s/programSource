<?php
function copyDirectory($src, $dst) {
    if (!file_exists($dst)) {
        mkdir($dst, 0755, true);
    }
    
    $files = scandir($src);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $srcFilePath = $src . DIRECTORY_SEPARATOR . $file;
        $dstFilePath = $dst . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($srcFilePath)) {
            set_time_limit(900); // 最大実行時間を900秒に延長
            copyDirectory($srcFilePath, $dstFilePath);
        } else {
            copy($srcFilePath, $dstFilePath);
        }
    }
}

// 日時のフォーマット
date_default_timezone_set('Asia/Tokyo');// 標準時間を日本に設定
$dateTime = date('Y-m-d-H-i-s');

// コピー元と日時付きコピー先のパスを指定
$sourceDir = 'C:/xampp';
$destinationDir = 'C:/backup/xampp_' . $dateTime;

copyDirectory($sourceDir, $destinationDir);


echo "バックアップが完了しました！".$destinationDir;
?>


