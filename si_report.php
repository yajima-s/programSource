<?php
// サブルーチン
// 和暦への変更
function Wareki($date) {
    $eras = [
        ['name' => '令和', 'start' => '2019-05-01', 'offset' => 2018],
        ['name' => '平成', 'start' => '1989-01-08', 'offset' => 1988],
        ['name' => '昭和', 'start' => '1926-12-25', 'offset' => 1925],
        ['name' => '大正', 'start' => '1912-07-30', 'offset' => 1911],
        ['name' => '明治', 'start' => '1868-01-25', 'offset' => 1867]
    ];
    $dateTime = new DateTime($date);
    foreach ($eras as $era) {
        $start = new DateTime($era['start']);
        if ($dateTime >= $start) {
            $year = $dateTime->format('Y') - $era['offset'];
            $year = ($year == 1) ? '元' : $year;
            return $era['name'] . $year . '年' . $dateTime->format('m月d日');
        }
    }
    return null; // 明治以前の日付には対応していません
}

// セミナー名取得
function getSeminarName($seminarId, &$seminarName) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT name FROM seminar WHERE id = :id");
    $stmt->bindValue(':id', $seminarId, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($records) {
        $seminarName = $records['name'];
    } else {
        echo "セミナーIDが間違っています" . $seminarId;
        exit();
    }
}

//同一名のPDFファイルがあるときは、ファイル名をファイル名＋１にしてアウトプット
function savePDF($pdf, $filename) {
    $path = 'c:/xampp/htdocs/jmca/aaaa/シニアポイント/';
    $fullPath = $path . $filename;

    // 同じ名前のファイルが存在するか確認
    $counter = 1;
    while (file_exists($fullPath)) {
        $fileInfo = pathinfo($filename);
        $newFilename = $fileInfo['filename'] . '_' . $counter . '.' . $fileInfo['extension'];
        $fullPath = $path . $newFilename;
        $counter++;
    }

    // PDFファイルを保存
    if (!file_exists($path)) {
    mkdir($path, 0777, true);
    }
    $pdf->Output($fullPath, 'F');


    }


// メインルーチン
require_once('tcpdf/tcpdf.php');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
$count1 = 0;//シニア会員新規達成者

// 新しいスプレッドシートを作成
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();


// ヘッダーの設定
$sheet->setCellValue('A1', 'メールアドレス');
$sheet->setCellValue('B1', '氏名');
$sheet->setCellValue('C1', 'シニアポイント');
$sheet->setCellValue('D1', '必須Sポイント');
$sheet->setCellValue('E1', 'シニア会員認定達成年度');

//DB接続
try {
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

// 今日の和暦を取得
$today = new DateTime();
$todayString = $today->format('Y-m-d');
$wareki = Wareki($todayString);


try {
    $stmt = $pdo->prepare("SELECT * FROM sinior ORDER BY mail ASC, seminar_id ASC");
    $stmt->execute();
    $beforeName = "";
    $beforeMail = "";
    $count = 0;
    $spTotal = 0;
    $essSpTotal = 0;
    $tasseiNenndo = 0000;
    $row = 2;

    while ($record = $stmt->fetch(PDO::FETCH_ASSOC)) {
        //正会員かチェック
        $stmt2 = $pdo->prepare("SELECT 1 FROM regular WHERE (name = :name OR mail = :mail) AND withdraw = 1 LIMIT 1");
        $stmt2->bindValue(':name', $record['name'], PDO::PARAM_STR);
        $stmt2->bindValue(':mail', $record['mail'], PDO::PARAM_STR);

        $stmt2->execute();
        $exists = (bool) $stmt2->fetchColumn(); // より明確な書き方

        if (!$exists) {
            continue;  //正会員マスターにない（名前かメールアドレスのどちらかが一致でかつwithdraw＝１を満たさない人）
        }

        $mail = $record['mail'];
        $name = $record['name'];
        $name = str_replace([' ', '　'], '', $name);//氏名のスペースを削除
        $name = trim($name);//文字列を前詰め
        if ($record['mail'] !== $beforeMail) {
            //メールアドレスが変わった場合、名前が同じでも別人と判断する
           // if ($record['name'] !== $beforeName){ 
            if ($count !== 0) {
                // 合計行作成＋PDFアウトプット
                $html .= '<tr>
                    <td>' . "" . '</td>
                    <td>' . "" . '</td>
                    <td>' . "" . '</td>
                    <td>' . "合計" . '</td>
                    <td>' . $spTotal . '</td>
                    <td>' . $essSpTotal . '</td>
                </tr>';

                if ($spTotal >= 20 && $essSpTotal >= 8) {
                    // シニア会員認定ポイント達成
                    
                    
                //sinior_tasseiTBの更新
                //メールアドレスか名前が一致するレコードが既にあれば、更新しない
                $stmt1 = $pdo->prepare('SELECT * FROM sinior_tassei WHERE mail = :mail OR name = :name');
                $stmt1->bindValue(':mail',$beforeMail,PDO::PARAM_STR);
                $stmt1->bindValue(':name',$beforeName,PDO::PARAM_STR);
                $stmt1->execute();
                $records1 = $stmt1->fetch(PDO::FETCH_ASSOC);
                    if (!$records1){
                    $stmt1 = $pdo->prepare('INSERT INTO sinior_tassei (mail,name,tassei_year) 
                    VALUES (:mail,:name,:tassei_year)');
                    //追加
                    $stmt1->bindValue(':mail',$beforeMail,PDO::PARAM_STR);
                    $stmt1->bindValue(':name',$beforeName,PDO::PARAM_STR);
                    $stmt1->bindValue(':tassei_year',$tasseiNenndo,PDO::PARAM_INT);
                    $stmt1->execute();
                    $html .= '<tr>
                        <td>' . "" . '</td>
                        <td>' . "" . '</td>
                        <td>' . "****" . '</td>
                        <td>' . "シニア会員認定ポイント達成　！" . '</td>
                        <td>' . "別途手続きメールをお送りします。" . '</td>
                        <td>' . "" . '</td>
                    </tr>';
                    $count1++; //今期シニアポイント達成者の数
                    echo "新規達成者名：".$beforeName."<br>新規達成者メール：".$beforeMail;

                }
                }

// HTMLをPDFにアウトプット
                // PDFのイニシャル設定
                $pdf = new TCPDF();
                $pdf->AddPage();

                // フォントの追加
                $fontPath = './tcpdf/fonts/ipaexg.ttf'; // フォントファイルのパスを指定
                $fontname = TCPDF_FONTS::addTTFfont($fontPath, 'TrueTypeUnicode', '', 96);
                $pdf->SetFont($fontname, '', 10);

                $pdf->writeHTML($html);
                $filename = 'sppdf_' . $beforeName . '様.pdf';
                savePDF($pdf, $filename);//同一名のPDFファイルが存在していたら、ファイル名に１を加えたファイル名でoutput
                
                // EXCELファイルにメールAD,氏名記載
                $sheet->setCellValue('A' . $row, $beforeMail);
                $sheet->setCellValue('B' . $row, $beforeName);
                $sheet->setCellValue('C' . $row, $spTotal);
                $sheet->setCellValue('D' . $row, $essSpTotal);
                $sheet->setCellValue('E' . $row, $tasseiNenndo);
                $tasseiNenndo = 0;
                $row++;
            

            // PDFのタイトル作成＋mail+nameが変わった後の最初の１レコード目の処理
            $nenndo = "20" . substr($record['seminar_id'],0,2);
            $seminarId = $record['seminar_id'];
            $seminarName = "";
            getSeminarName($seminarId, $seminarName);
            $spTotal = $record['point'];
            $essSpTotal = $record['essential'];
            $tasseiNenndo = 0;
            $html = '<table border="1" cellpadding="4">
                <tr>
                    <th>氏名</th>
                    <th>メールAD</th>
                    <th>年度</th>
                    <th>セミナー名</th>
                    <th>シニアP</th>
                    <th>必須シニアP</th>
                </tr>';
            $html .= '<tr>
                <td>' . $name . '</td>
                <td>' . $record['mail'] . '</td>
                <td>' . $nenndo . '</td>
                <td>' . $seminarName . '</td>
                <td>' . $record['point'] . '</td>
                <td>' . $record['essential'] . '</td>
            </tr>';
            $beforeName = $name;
            $beforeMail = $record['mail'];
            $count = 1;
            continue;
        }
        
       // 最初の１レコード目（$count = 0)の処理
            $nenndo = "20" . substr($record['seminar_id'],0,2);
            $seminarId = $record['seminar_id'];
            $seminarName = "";
            getSeminarName($seminarId, $seminarName);
            $spTotal = $record['point'];
            $essSpTotal = $record['essential'];
            $html = '<table border="1" cellpadding="4">
                <tr>
                    <th>氏名</th>
                    <th>メールAD</th>
                    <th>年度</th>
                    <th>セミナー名</th>
                    <th>シニアP</th>
                    <th>必須シニアP</th>
                </tr>';
            $html .= '<tr>
                <td>' . $name . '</td>
                <td>' . $record['mail'] . '</td>
                <td>' . $nenndo . '</td>
                <td>' . $seminarName . '</td>
                <td>' . $record['point'] . '</td>
                <td>' . $record['essential'] . '</td>
            </tr>';
            $beforeName = $name;
            $beforeMail = $record['mail'];
            $count = 1;
            continue; 
        }
            
    
        // メールADが同じ場合
        $spTotal = $spTotal + $record['point'];
        $essSpTotal = $essSpTotal + $record['essential'];
        $nenndo = "20" . substr($record['seminar_id'],0,2);
        $seminarId = $record['seminar_id'];
        if ($spTotal >= 20 && $essSpTotal >= 8 ){
            if ($tasseiNenndo == 0){
               $tasseiNenndo = $nenndo; 
            }
        }
        $seminarName = "";
        getSeminarName($seminarId, $seminarName);
        $html .= '<tr>
            <td>' . "" . '</td>
            <td>' . "" . '</td>
            <td>' . $nenndo . '</td>
            <td>' . $seminarName . '</td>
            <td>' . $record['point'] . '</td>
            <td>' . $record['essential'] . '</td>
        </tr>';
        $beforeName = $record['name'];
        $beforeMail = $record['mail'];
        $count = 1;
    }

    // EXCELファイルを保存
    $writer = new Xlsx($spreadsheet);
    $filename = "./aaaa/シニアポイント/sppdf.xlsx";
    $writer->save($filename);

    // DBの処理がすべてうまくいきました
} catch (Exception $e) {
    echo 'エラーが発生しました: ' . $e->getMessage();
}
echo "シニア会員新規達成者　＝　" .$count1;
?>
