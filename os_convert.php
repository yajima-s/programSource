
<?php
//旧受付簿を読込み受付簿DBを作成する
//旧受付簿（exelファイル）のファイル名は、os_seminarid_inputでインプットしてセミナーID（4ケタの数字）＋xlsx
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\IOFactory;

function connect() {
           $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
        } 

function convertDateToExcelSerial($value) {
    // DateTimeオブジェクトを作成
    $dateTime = new DateTime($value);

    // PhpSpreadsheetの関数を使用して、Excelの日付シリアル値に変換
    $excelSerial = PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel($dateTime);

    return $excelSerial;
}

function kubunn_convert(&$id1,$kubun){
         $id1 = 0;
         if (preg_match('/正会員/', $kubun)) {
                  $id1 = 1;
         } elseif (preg_match('/賛助会員（枠内）/', $kubun)) {
                  $id1 = 2;
         } elseif (preg_match('/賛助会員（枠外）/', $kubun)) {
                  $id1 = 3;
         } elseif (preg_match('/一般/', $kubun)) {
                  $id1 = 4;
         } elseif (preg_match('/学生/', $kubun)) {
                  $id1 = 5;
         } else {
                  $id1 = 9;
}
}
         
function seminar_id_check($pdo,&$seminar_id,&$plural,&$seminarname){

if (!isset($_POST['seminar_id'])) {
    if (empty($_POST['seminar_id'])){
    header('Location: os_seminarid_input.php?error=セミナーＩＤが入力されていません');
    exit;      
    }
    }
$value = $_POST['seminar_id'];
$length = mb_strlen($value, 'UTF-8');

if ($length !== 4) {
    header('Location: os_seminarid_input.php?error=セミナーＩＤは半角4桁です');
    exit; 
}
$seminar_id = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');//$seminar_idはここで固定

//セミナーidの存在チェック→存在可否、セミナー名、複数回かを返す

$statement2 = $pdo->prepare('SELECT name,plural FROM seminar WHERE id = :id  ');
$statement2->bindValue(':id', $seminar_id, PDO::PARAM_STR);
$statement2->execute();
$result = $statement2->fetch(PDO::FETCH_ASSOC);
       
if (!$result ){
   header('Location:os_seminarid_input.php?error=セミナーDBにこのセミナーIDは登録されていません。正しいセミナーIDを再インプットしてください');
    exit;
}
$seminarname = $result['name'];//seminarnameはここで固定
$plural = $result['plural'];//pluralはここで固定
}

//請求書、領収書の送付方法を数字1桁に置き換え　１：PDF,2:郵送　３：不要
function searchmeans($bill_org,$receipt_org,&$billmeans,&$receiptmeans){
        if ($bill_org == NULL){
                 $billmeans = 1;
        }
         if ($receipt_org == NULL){
                  $receiptmeans = 1;
         }
        if (preg_match('/郵送/', $bill_org)){
            $billmeans = 2;
        }else{
            $billmeans = 1;
        }
        if (preg_match('/郵送/', $receipt_org)){
            $receiptmeans = 2;
        }elseif (preg_match('/ＰＤＦ/', $receipt_org)){
            $receiptmeans = 1;
        }else {
            $receiptmeans = 3;
        }
    }
         
//会員IDを取得する：正会員、賛助会員共に取得
//正会員かどうかを名前でチェック
function searchkaiinid($pdo,$row,$name,$mail,$office,$kubun,&$kaiinid){
    
if (preg_match('/正会員/', $kubun)) {
    $name = $name;
    $statement1 = $pdo->prepare('SELECT id FROM regular WHERE name = :name ');
    $statement1->bindValue(':name', $name, PDO::PARAM_STR);
    $statement1->execute();

      $recordExists = $statement1->fetch(PDO::FETCH_ASSOC);
      if ($recordExists == true) {
            $kaiinid = $recordExists['id'];
      } else {
         $kaiinid = 9999; 
      } 

    if ($kaiinid == 9999) {
        $statement1 = $pdo->prepare('SELECT id FROM regular WHERE mail = :mail');
        $statement1->bindValue(':mail',$mail,PDO::PARAM_STR);
        $statement1->execute();
        $recordExists = $statement1->fetch(PDO::FETCH_ASSOC);
      if ($recordExists == true) {
            $kaiinid = $recordExists['id'];
      } else {
         $kaiinid = 9999; 
      } 
    }

    
  } elseif (preg_match('/賛助会員/', $kubun)) {

     // "株式会社" ”㈱”　”（株）”を除去

    
    $originalString = $office;
    $removeString = "株式会社";
    $resultString = str_replace($removeString, "", $originalString);
    $originalString = $resultString;
    $removeString = "㈱";
    $resultString = str_replace($removeString, "", $originalString);
    $originalString = $resultString;
    $removeString = "（株）";
    $resultString = str_replace($removeString, "", $originalString);
    
    $statement1 = $pdo->prepare('SELECT id FROM support WHERE name LIKE :name');
    $statement1->bindValue(':name', "%" . $resultString . "%", PDO::PARAM_STR);
    $statement1->execute();

    $recordExists = $statement1->fetch(PDO::FETCH_ASSOC);
    if ($recordExists == true) {
            $kaiinid = $recordExists['id'];
    } else {
        $kaiinid = 9999; 
    }
    }
  }

//「2024/06/05 8:30:45 午前 GMT+9」の形式の日付データを、PHPで Y-m-d H:i:s の形式（例: 2024-06-05 08:30:45）に変換する
function convertJapaneseDateToStandard($date_str) {
    // 「午前」を "AM"、「午後」を "PM" に置き換え
    $converted_date_str = str_replace(['午前', '午後'], ['AM', 'PM'], $date_str);
    
    // 「2024/06/05 8:30:45 AM GMT+9」の形式で日時をパース
    $date = DateTime::createFromFormat('Y/m/d g:i:s A T', $converted_date_str);
    
    // フォーマットが正しければ、指定の形式に変換
    return $date ? $date->format('Y-m-d H:i:s') : false;
}

//日付のフォーマットが  2024/06/05 8:30:45 午前 GMT+9かチェック 
 function checkJapaneseDateFormat($date_str) {
    // $date_strがnullかどうかを確認
    if ($date_str === null) {
        return false;
    }

    // 「2024/06/05 8:30:45 午前 GMT+9」の形式にマッチする正規表現
    $pattern = '/^\d{4}\/\d{2}\/\d{2} \d{1,2}:\d{2}:\d{2} (午前|午後) GMT\+\d+$/';
    
    // 正規表現で形式をチェック
    return preg_match($pattern, $date_str) === 1;
}

//メインルーチンーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー
//DBと接続する
try {
            $pdo = connect();
     } catch (PDOException $e) {
            echo "DB接続に失敗しました。".$e; 
            exit;}

//セミナーID処理
$seminar_id = 0;
$plural = 0;
$seminarname = "";
seminar_id_check($pdo,$seminar_id,$plural,$seminarname);

$reader = new Xlsx();
try {
    $spreadsheet = $reader->load('./aaaa/旧受付簿/'.$seminar_id.'.xlsx');//セミナーID＋xlsx名のファイルを複数処理可能
} catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
    echo 'ファイルの読み込みに失敗しました: ', $e->getMessage();
}

$sheet = $spreadsheet->getSheetByName('Sheet1');
         
$count = 0;        
foreach ($sheet->getRowIterator() as $row){
         //タイトルを読み飛ばし
    $count++;
    if ($count == 1){
        continue;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM uketukebo WHERE name = :name AND seminarid = :seminarid');
    $cell = $sheet->getCell('C' . $row->getRowIndex());//参加者名
    $value = $cell->getValue();
    if ($value == NULL || $value == "") { //空のレコードがありうるための対応
    break;
    }

    $name = str_replace([' ', '　'], '', $value); // 氏名のスペースを削除
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':seminarid', $seminar_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // レコードが存在するか確認
    if ($stmt->fetchColumn() !== 0) {
        //同一キーのレコードが存在する
        continue;//1件読み飛ばし：氏名＋セミナーIDが同一のキーが受付簿TBに存在した場合トランザクションは読み飛ばす。訂正入力は読み飛ばす。
    }
//同一キーのレコードが存在しない            
    $statement = $pdo->prepare('INSERT INTO uketukebo(name, seminarid,created,modified,mail,
    office,busho,kubun,billmeans,receiptmeans,atena,post,address,bilname,plural,billsend,receiptsend,
    paid,multi,notax,tax,withtax,kaiinid,note,seminarname,timestamp) VALUES
    (:name,:seminarid,CURRENT_TIMESTAMP, CURRENT_TIMESTAMP,:mail,:office,:busho,:kubun,:billmeans,:receiptmeans,
    :atena,:post,:address,:bilname,:plural,:billsend,:receiptsend,:paid,:multi,:notax,:tax,:withtax,:kaiinid,
    :note,:seminarname,:timestamp)');
    
         // トランザクションのタイムスタンプ 2024/06/05 8:30:45 午前 GMT+9形式でなければ00000-00-00 00:00:00を入れる
         $cell = $sheet->getCell('A' . $row->getRowIndex());
         $date_str = $cell->getValue();
         if (checkJapaneseDateFormat($date_str))  {
             $converted_date = convertJapaneseDateToStandard($date_str);
             if ($converted_date) {
                $timestamp = $converted_date;
             } else {
                $timestamp = "0000-00-00 00:00:00";
             } 
         } else {
            $timestamp = "0000-00-00 00:00:00";;
         }

         
        
         $statement->bindValue(':timestamp', $timestamp, PDO::PARAM_STR);
    
         //その他のカラムのバインド
         $statement->bindValue(':seminarid',$seminar_id, PDO::PARAM_INT);
         $cell = $sheet->getCell('B' . $row->getRowIndex());
         $mail = $cell->getValue();
         $statement->bindValue(':mail',$mail, PDO::PARAM_STR);
         $cell = $sheet->getCell('C' . $row->getRowIndex()); 
         $value = $cell->getValue();
         $name = str_replace([' ', '　'], '', $value); // 氏名のスペースを削除
         $statement->bindValue(':name',$name, PDO::PARAM_STR); 
         $cell = $sheet->getCell('D' . $row->getRowIndex());
         $office = $cell->getValue();
         $statement->bindValue(':office',$office, PDO::PARAM_STR);
         $cell = $sheet->getCell('E' . $row->getRowIndex());
         $value = $cell->getValue();
         $statement->bindValue(':busho',$value, PDO::PARAM_STR);
         $cell = $sheet->getCell('F' . $row->getRowIndex());
         $kubun = $cell->getValue();
         kubunn_convert($id1,$kubun);
         $statement->bindValue(':kubun',$id1, PDO::PARAM_STR);
         $kaiinid = 0;
         searchkaiinid($pdo,$row,$name,$mail,$office,$kubun,$kaiinid);
         $statement->bindValue(':kaiinid',$kaiinid, PDO::PARAM_INT);
         $statement->bindValue(':plural',$plural, PDO::PARAM_INT);
         $statement->bindValue(':seminarname',$seminarname, PDO::PARAM_INT);
         if ($plural == 2){
                //複数回セミナーの時
                  $cell = $sheet->getCell('G' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':multi',$value, PDO::PARAM_STR);
                  $cell = $sheet->getCell('H' . $row->getRowIndex());
                  $bill_org = $cell->getValue();
                  $cell = $sheet->getCell('I' . $row->getRowIndex());
                  $receipt_org = $cell->getValue();
                  searchmeans($bill_org,$receipt_org,$billmeans,$receiptmeans);
                  $statement->bindValue(':billmeans',$billmeans, PDO::PARAM_INT);
                  $statement->bindValue(':receiptmeans',$receiptmeans, PDO::PARAM_INT);
                  $cell = $sheet->getCell('J' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':atena',$value, PDO::PARAM_STR);
                  $cell = $sheet->getCell('K' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':post',$value, PDO::PARAM_STR);
                  $cell = $sheet->getCell('L' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':address',$value, PDO::PARAM_STR);
                  $cell = $sheet->getCell('M' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':bilname',$value, PDO::PARAM_STR);
                  //その他＋備考をnoteへ
                  $cell = $sheet->getCell('N' . $row->getRowIndex());
                  $value1 = $cell->getValue();
                  $cell = $sheet->getCell('S' . $row->getRowIndex());
                  $value2 = $cell->getValue();
                  $value = $value1.$value2;
                  $statement->bindValue(':note',$value, PDO::PARAM_STR);
                   $cell = $sheet->getCell('O' . $row->getRowIndex());
                  $value = $cell->getValue();
                  if (is_int($value)) {
                      $dateValue = Date::excelToDateTimeObject($value);
                      if ($dateValue !== null) {
                          $formattedDate = $dateValue->format('Y-m-d');
                          if ($formattedDate == '0000-00-00') {
                              $statement->bindValue(':billsend', NULL);
                          } else {
                              $statement->bindValue(':billsend', $formattedDate);
                          }
                      } else {
                          $statement->bindValue(':billsend', NULL);
                      }
                  } else {
                      $statement->bindValue(':billsend', NULL);
                  }

                   $cell = $sheet->getCell('P' . $row->getRowIndex());
                  $value = $cell->getValue();
                 if (is_int($value)) {
                    $dateValue = Date::excelToDateTimeObject($value);
                     if ($dateValue !== null) {
                         $formattedDate = $dateValue->format('Y-m-d');
                         if ($formattedDate == '0000-00-00') {
                             $statement->bindValue(':receiptsend', NULL);
                    } else {
                             $statement->bindValue(':receiptsend', $formattedDate);
                         }
                     } else {
                         $statement->bindValue(':receiptsend', NULL);
                     }
                    } else {
                     $statement->bindValue(':receiptsend', NULL);
                    }

                  $cell = $sheet->getCell('Q' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':withtax',$value, PDO::PARAM_INT);
                  $value = floatval($value);
                  $notax = $value/1.1;
                  $tax = $value - $notax;
                  $statement->bindValue(':notax',$notax, PDO::PARAM_INT);
                  $statement->bindValue(':tax',$tax, PDO::PARAM_INT);

                    $cell = $sheet->getCell('R' . $row->getRowIndex());
                    $value = $cell->getValue();

                    if (is_numeric($value) && $value > 0) { // シリアル値のみ処理（負の値を除外）
                        $dateValue = Date::excelToDateTimeObject($value);
                        if ($dateValue !== false) { // 有効な日付か確認
                            $formattedDate = $dateValue->format('Y-m-d');
                            $statement->bindValue(':paid', $formattedDate);
                        } else {
                            $statement->bindValue(':paid', NULL);
                        }
                    } else {
                        $statement->bindValue(':paid', NULL);
                    }

         }else{
            //plural ＝１（単発セミナー）の時
                  $statement->bindValue(':multi',"", PDO::PARAM_INT);
                  $cell = $sheet->getCell('G' . $row->getRowIndex());
                  $bill_org = $cell->getValue();
                  $cell = $sheet->getCell('H' . $row->getRowIndex());
                  $receipt_org = $cell->getValue();
                  searchmeans($bill_org,$receipt_org,$billmeans,$receiptmeans);
                  $statement->bindValue(':billmeans',$billmeans, PDO::PARAM_INT);
                  $statement->bindValue(':receiptmeans',$receiptmeans, PDO::PARAM_INT);
                  $cell = $sheet->getCell('I' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':atena',$value, PDO::PARAM_STR);
                  $cell = $sheet->getCell('J' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':post',$value, PDO::PARAM_STR);
                  $cell = $sheet->getCell('K' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':address',$value, PDO::PARAM_STR);
                  $cell = $sheet->getCell('L' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':bilname',$value, PDO::PARAM_STR);
                  //その他＋備考をnoteへ
                  $cell = $sheet->getCell('M' . $row->getRowIndex());
                  $value1 = $cell->getValue();
                  $cell = $sheet->getCell('R' . $row->getRowIndex());
                  $value2 = $cell->getValue();
                  $value = $value1.$value2;
                  $statement->bindValue(':note',$value, PDO::PARAM_STR);
                  $cell = $sheet->getCell('N' . $row->getRowIndex());
                  $value = $cell->getValue();
                  if (is_int($value)) {
                      $dateValue = Date::excelToDateTimeObject($value);
                      if ($dateValue !== null) {
                          $formattedDate = $dateValue->format('Y-m-d');
                          if ($formattedDate == '0000-00-00') {
                              $statement->bindValue(':billsend', NULL);
                          } else {
                              $statement->bindValue(':billsend', $formattedDate);
                          }
                      } else {
                          $statement->bindValue(':billsend', NULL);
                      }
                  } else {
                      $statement->bindValue(':billsend', NULL);
                  }

                   $cell = $sheet->getCell('O' . $row->getRowIndex());
                  $value = $cell->getValue();
                  if (is_int($value)) {
                      $dateValue = Date::excelToDateTimeObject($value);
                      if ($dateValue !== null) {
                          $formattedDate = $dateValue->format('Y-m-d');
                          if ($formattedDate == '0000-00-00') {
                              $statement->bindValue(':receiptsend', NULL);
                          } else {
                              $statement->bindValue(':receiptsend', $formattedDate);
                          }
                      } else {
                          $statement->bindValue(':receiptsend', NULL);
                      }
                  } else {
                      $statement->bindValue(':receiptsend', NULL);
                  }

                  $cell = $sheet->getCell('P' . $row->getRowIndex());
                  $value = $cell->getValue();
                  $statement->bindValue(':withtax',$value, PDO::PARAM_INT);
                  $notax = $value/1.1;
                  $tax = $value - $notax;
                  $statement->bindValue(':notax',$notax, PDO::PARAM_INT);
                  $statement->bindValue(':tax',$tax, PDO::PARAM_INT);
                  $cell = $sheet->getCell('Q' . $row->getRowIndex());
                    $value = $cell->getValue();

                    if (is_numeric($value) && $value > 0) { // シリアル値のみ処理（負の値を除外）
                        $dateValue = Date::excelToDateTimeObject($value);
                        if ($dateValue !== false) { // 有効な日付か確認
                            $formattedDate = $dateValue->format('Y-m-d');
                            $statement->bindValue(':paid', $formattedDate);
                        } else {
                            $statement->bindValue(':paid', NULL);
                        }
                    } else {
                        $statement->bindValue(':paid', NULL);
                    }

         }
                  
          $statement->execute();        
                  
         }
$pdo = NULL;


?>
