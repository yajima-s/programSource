pdfFileOutput($html,$baseDir,$baseName,$prefix){

$pdf->writeHTML($html);

// 基本ファイル名（n を付ける前）
$baseName = $record['name'] . '様';

// 保存先フォルダと prefix
if ($record['billmeans'] == 1){
    $baseDir = 'c:/xampp/htdocs/jmca/aaaa/年会費請求書/pdf/';
    $prefix  = 'billpdf_';
} else {
    $baseDir = 'c:/xampp/htdocs/jmca/aaaa/年会費請求書/yuubin/';
    $prefix  = 'billyuubin_';
}

// n を付けて同名チェック
$n = 0;
while (true) {

    // n が 0 のときは付けない
    if ($n === 0) {
        $fileName = $prefix . $baseName . '.pdf';
    } else {
        $fileName = $prefix . $baseName . $n . '.pdf';
    }

    $fullPath = $baseDir . $fileName;

    // 同名ファイルが存在しなければ採用
    if (!file_exists($fullPath)) {
        break;
    }

    // 存在したら n を増やす
    $n++;
}

// PDF 保存（同名がないことが保証されている）
$pdf->Output($fullPath, 'F');

}