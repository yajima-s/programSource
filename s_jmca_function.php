<?php
//セミナーIDのチェック
function seminarId_check(&$seminarId){
if (!isset($_POST['seminarId'])) {
    header('Location: s_id_in.php?error=セミナーＩＤが入力されていません');
    exit;      
    }
$value = $_POST['seminarId'];
$length = mb_strlen($value, 'UTF-8');

if ($length !== 4) {
    header('Location: s_id_in.php?error=セミナーＩＤは半角4桁です');
    exit; 
}
$seminarId = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');//$seminarIdはここで固定

}

//セミナーidの存在チェック→存在可否、セミナー名、複数回かを返す
function seminarId_dbcheck($pdo,$seminarId,&$plural,&$seminarName){

$statement2 = $pdo->prepare('SELECT name,plural FROM seminar WHERE id = :id  ');
$statement2->bindValue(':id', $seminarId, PDO::PARAM_STR);
$statement2->execute();
$result = $statement2->fetch(PDO::FETCH_ASSOC);
    
    
if (!$result ){
   header('Location:s_id_in.php?error=正しいセミナーIDを再インプットしてください');
    exit;
}
$seminarName = $result['name'];//seminarNameはここで固定
$plural = $result['plural'];//pluralはここで固定
}

//会員IDを取得する：正会員、っ賛助会員共に取得
//正会員かどうかを名前でチェック
function searchkaiinid($pdo,$data,&$kaiinId,&$regularFee,&$supportFee){
    $kaiinId = 0000;//正会員、賛助会員以外は0000
if (preg_match('/正会員/', $data[5])) {
    $name = $data[2];
    $name = str_replace(' ', '', $name);
    $statement1 = $pdo->prepare('SELECT id,fee1 FROM regular WHERE name = :name AND withdraw = 1');
    $statement1->bindValue(':name', $name, PDO::PARAM_STR);
    $statement1->execute();

      $recordExists = $statement1->fetch(PDO::FETCH_ASSOC);
      if ($recordExists == true) {
            $kaiinId = $recordExists['id'];
           if ($recordExists['fee1'] === "0000-00-00" || is_null($recordExists['fee1'])) {
                $regularFee = 1;
            }
      } else {
         $kaiinId = 9999; 
      } 

    if ($kaiinId == 9999) {
        $statement1 = $pdo->prepare('SELECT id,fee1 FROM regular WHERE mail = :mail AND withdraw = 1');
        $statement1->bindValue(':mail',$data[1],PDO::PARAM_STR);
        $statement1->execute();
        $recordExists = $statement1->fetch(PDO::FETCH_ASSOC);
      if ($recordExists == true) {
            $kaiinId = $recordExists['id'];
            if ($recordExists['fee1'] === "0000-00-00" || is_null($recordExists['fee1'])) {
                $regularFee = 1;
            }
      } else {
         $kaiinId = 9999; 
      } 
    }
    
  } elseif (preg_match('/賛助会員/', $data[5])) {
    //"　"," "を除去
    $office = $data[3];
    $office = str_replace(' ', '', $office);
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
    
    $statement1 = $pdo->prepare('SELECT id,fee1  FROM support WHERE name LIKE :name AND withdraw = 1');
    $statement1->bindValue(':name', "%" . $resultString . "%", PDO::PARAM_STR);
    $statement1->execute();

    $recordExists = $statement1->fetch(PDO::FETCH_ASSOC);
    if ($recordExists == true) {
            $kaiinId = $recordExists['id'];
            if ($recordExists['fee1'] === "0000-00-00" || is_null($recordExists['fee1'])) {
                $supportFee = 1;
            }
    } else {
        $kaiinId = 9999; 
    }
  }
}
//$kaiinIdはここで固定
    
//料金を検索
function searchFee($pdo, $data, $plural,&$notax,&$tax,&$withtax,&$id1,&$id2,$kubun) {
//id1:区分（正会員当）　id2:複数回セミナーで何回受講するか
    if (preg_match('/正会員/', $kubun ?? "")) {
        $id1 = 1;
    } elseif (preg_match('/賛助会員（枠内）/', $kubun ?? "")) {
        $id1 = 2;
    } elseif (preg_match('/賛助会員（枠超過）/', $kubun ?? "")) {
        $id1 = 3;
    } elseif (preg_match('/一般/', $kubun ?? "")) {
        $id1 = 4;
    } elseif (preg_match('/学生/', $kubun ?? "")) {
        $id1 = 5;
    } else {
        $id1 = 9;
    }
    $id2 = 1;
//2026/3/24　4回しリーズ対応修正
    if ($plural == 2) {
        if (preg_match('/のみ/', $data[6])) {
            $id2 = 1;
        } elseif (preg_match('/２回分まとめて/', $data[6])) {
            $id2 = 2;
        } elseif (preg_match('/３回分まとめて/', $data[6])) {
            $id2 = 3;
        } elseif (preg_match('/４回分まとめて/', $data[6])) {
            $id2 = 4;
        } elseif (preg_match('/１回目、２回目、３回目|１回目、２回目、４回目|１回目、３回目、４回目|２回目、３回目、４回目/', $data[6])) {
            $id2 = 3;
        } elseif (preg_match('/１回目、２回目|１回目、３回目|１回目、４回目|２回目、３回目|２回目、４回目|３回目、４回目/', $data[6])) {
            $id2 = 2;
        }
        }
    $id = $id1.$id2;
    //$id1＝区分　＄id2＝参加回数
    // 料金を取り込む
    $notax = 0;
    $tax = 0;
    $withtax = 0;
    if ($id1 !== 9){
    $statement2 = $pdo->prepare('SELECT notax, tax, withtax FROM feetable WHERE id = :id');
    $statement2->bindValue(':id', $id, PDO::PARAM_STR);
    $statement2->execute();
    $result = $statement2->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $notax = $result["notax"];
        $tax = $result["tax"];
        $withtax = $result["withtax"];
    }else{
        $notax =0;
        $tax = 0;
        $withtax =0;  
        echo "feeテーブルに料金が設定されていません".$id.$kubun.$data[6];
    }
          
    }
        
    }


//請求書、領収書の送付方法を数字1桁に置き換え　１：PDF,2:郵送　３：不要
function searchmeans($data,$plural,&$billmeans,&$receiptmeans){

    if  ($plural == 2) {
        //pluralが２の時
        if (preg_match('/郵送/', $data[7])){
            $billmeans = 2;
        }else{
            $billmeans = 1;
        }
        if (preg_match('/郵送/', $data[8])){
            $receiptmeans = 2;
        }elseif (preg_match('/PDF/', $data[8])){
            $receiptmeans = 1;
        }elseif (preg_match('/不要/', $data[8])){
            $receiptmeans = 3;
        }
    //pluralが１の時
    }else{
        if (preg_match('/郵送/', $data[6])){
            $billmeans = 2;
        }else{
            $billmeans = 1;
        }
        if (preg_match('/郵送/', $data[7])){
            $receiptmeans = 2;
        }elseif(preg_match('/PDF/', $data[7])){
            $receiptmeans = 1;
        }elseif (preg_match('/不要/', $data[8])){
            $receiptmeans = 3;
        }
    }
}
    


//jmcaDBを開きます
function connect() {
    try{
    $pdo = new PDO('mysql:host=localhost; dbname=jmca; charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
    }catch (PDOException $e){
        echo 'Connection failed:'.$e->getMessage();
        exit;
    }
}

//応募データ1件処理
function oubo_1kenshori($pdo,$data,$plural,$notax,$tax,$withtax,$id1,$seminarId,$kaiinId,$billmeans,
                        $receiptmeans,$shortname,$seminarName,$mail,$kubun,&$paid,&$duplicate){
$stmt = $pdo->prepare('SELECT * FROM uketukebo WHERE name = :name and seminarid = :seminarid  ');
$stmt->bindValue(':name', $shortname, PDO::PARAM_STR);
$stmt->bindValue(':seminarid', $seminarId, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

//タイムスタンプが、トランザクションと受付簿DBで一致しているか確認するための準備
// 受付簿DBのタイムスタンプ
$timestamp1 = null;
if ($result) {
    $timestamp1 = $result['timestamp'];

}
// トランザクションのタイムスタンプ
$timestamp = $data[0];
$timestamp = str_replace(['午後', '午前'], ['PM', 'AM'], $timestamp);
// DateTimeオブジェクトを作成
if (!empty($timestamp)) {
    $date = new DateTime($timestamp, new DateTimeZone('Asia/Tokyo'));
    $timestamp2 = $date->format('Y-m-d H:i:s'); 
}

if ($result) {
    if ($timestamp1 < $timestamp2){    
    //名前、セミナーIDが一致しておりかつタイムスタンプが大きい場合、既にあるレコードを新しいレコードに更新する
    $paid = $result['paid'];   
    $receiptsend = $result['receiptsend'];
    updateUketukebo($data,$pdo,$shortname,$mail,$kubun,$seminarId,$id1,$billmeans,$receiptmeans,$plural,$notax,$tax,$withtax,$kaiinId,$seminarName,$paid,$receiptsend);    
    }else{
        $duplicate = 1;//重複レコード
    }
}
if (!$result) {
    //トランザクションと同じキーのレコードがuketukeboDBにない場合、追加処理をする
    addUketukebo($data,$pdo,$shortname,$mail,$kubun,$seminarId,$id1,$billmeans,$receiptmeans,$plural,$notax,$tax,$withtax,$kaiinId,$seminarName);
} 
}


//uketukeboTBにレコードを追加します

function addUketukebo($data,$pdo,$shortname,$mail,$kubun,$seminarId,$id1,$billmeans,$receiptmeans,$plural,$notax,$tax,$withtax,$kaiinId,$seminarName) {
$statement = $pdo->prepare('INSERT INTO uketukebo(name, seminarid, created, modified, mail,
office, busho, kubun, billmeans, receiptmeans, atena, post, address, bilname, plural, billsend, 
receiptsend, paid, multi, notax, tax, withtax, kaiinid, note,seminarName,timestamp) 
VALUES(:name, :seminarid, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :mail, :office, :busho, :kubun,
:billmeans, :receiptmeans, :atena, :post, :address, :bilname, :plural, :billsend, :receiptsend,
:paid, :multi, :notax, :tax, :withtax, :kaiinid, :note, :seminarName,:timestamp)');

$statement->bindValue(':name', $shortname, PDO::PARAM_STR);
$statement->bindValue(':seminarid', $seminarId, PDO::PARAM_INT);
$statement->bindValue(':mail', $mail, PDO::PARAM_STR);
$statement->bindValue(':office', $data[3], PDO::PARAM_STR);
$statement->bindValue(':busho', $data[4], PDO::PARAM_STR);
$statement->bindValue(':kubun', $id1, PDO::PARAM_INT);
$statement->bindValue(':billmeans', $billmeans, PDO::PARAM_INT);
$statement->bindValue(':receiptmeans', $receiptmeans, PDO::PARAM_INT);
$statement->bindValue(':plural', $plural, PDO::PARAM_INT);
$statement->bindValue(':billsend', NULL, PDO::PARAM_NULL);
$statement->bindValue(':receiptsend', NULL, PDO::PARAM_NULL);
$statement->bindValue(':paid', NULL, PDO::PARAM_NULL);
$statement->bindValue(':notax', $notax, PDO::PARAM_INT);
$statement->bindValue(':tax', $tax, PDO::PARAM_INT);
$statement->bindValue(':withtax', $withtax, PDO::PARAM_INT);
$statement->bindValue(':kaiinid', $kaiinId, PDO::PARAM_INT);
    
if ($plural ==2){
$statement->bindValue(':multi', $data[6], PDO::PARAM_STR);
$statement->bindValue(':atena', $data[9], PDO::PARAM_STR);
$statement->bindValue(':post', $data[10], PDO::PARAM_STR);
$statement->bindValue(':address', $data[11], PDO::PARAM_STR);
$statement->bindValue(':bilname', $data[12], PDO::PARAM_STR);
$statement->bindValue(':note', $data[13], PDO::PARAM_STR);
}else {
$statement->bindValue(':multi', "", PDO::PARAM_STR);
$statement->bindValue(':atena', $data[8], PDO::PARAM_STR);
$statement->bindValue(':post', $data[9], PDO::PARAM_STR);
$statement->bindValue(':address', $data[10], PDO::PARAM_STR);
$statement->bindValue(':bilname', $data[11], PDO::PARAM_STR);
$statement->bindValue(':note', $data[12], PDO::PARAM_STR);
}
$statement->bindValue(':seminarName', $seminarName, PDO::PARAM_STR);
$timestamp = $data[0];
$timestamp = str_replace(['午後', '午前'], ['PM', 'AM'], $timestamp);
// DateTimeオブジェクトを作成
$date = new DateTime($timestamp, new DateTimeZone('Asia/Tokyo'));

// 24時間フォーマットを指定して出力（タイムゾーン情報を除く）
$formattedDate = $date->format('Y-m-d H:i:s');
$statement->bindValue(':timestamp', $formattedDate, PDO::PARAM_STR);
$statement->execute();

}



//uketukeboTBの既存レコードを置き換える
function updateuketukebo($data,$pdo,$shortname,$mail,$kubun,$seminarId,$id1,$billmeans,$receiptmeans,$plural,$notax,$tax,$withtax,$kaiinId,$seminarName,$paid,$receiptsend){
    $statement = $pdo->prepare('UPDATE uketukebo SET modified = CURRENT_TIMESTAMP,mail = :mail,office = 
    :office,busho = :busho,kubun = :kubun,billmeans = :billmeans,receiptmeans = :receiptmeans,atena = :atena,post = 
    :post,address = :address,bilname = :bilname,billsend = :billsend,receiptsend = :receiptsend,paid = :paid,multi = 
    :multi,notax = :notax,tax = :tax,withtax = :withtax,kaiinid = :kaiinid,note = :note,seminarName = :seminarName,timestamp = :timestamp WHERE name = :name and seminarid = :seminarid');
$statement->bindValue(':name', $shortname, PDO::PARAM_STR);
$statement->bindValue(':seminarid', $seminarId, PDO::PARAM_INT);
$statement->bindValue(':mail', $mail, PDO::PARAM_STR);
$statement->bindValue(':office', $data[3], PDO::PARAM_STR);
$statement->bindValue(':busho', $data[4], PDO::PARAM_STR);
$statement->bindValue(':kubun', $id1, PDO::PARAM_INT);
$statement->bindValue(':billmeans', $billmeans, PDO::PARAM_INT);
$statement->bindValue(':receiptmeans', $receiptmeans, PDO::PARAM_INT);
$statement->bindValue(':billsend', NULL, PDO::PARAM_NULL);
$statement->bindValue(':receiptsend', $receiptsend, PDO::PARAM_STR);
$statement->bindValue(':paid', $paid, PDO::PARAM_STR);
$statement->bindValue(':notax', $notax, PDO::PARAM_INT);
$statement->bindValue(':tax', $tax, PDO::PARAM_INT);
$statement->bindValue(':withtax', $withtax, PDO::PARAM_INT);
$statement->bindValue(':kaiinid', $kaiinId, PDO::PARAM_INT);
if ($plural ==2){
$statement->bindValue(':multi', $data[6], PDO::PARAM_STR);   
$statement->bindValue(':atena', $data[9], PDO::PARAM_STR);
$statement->bindValue(':post', $data[10], PDO::PARAM_STR);
$statement->bindValue(':address', $data[11], PDO::PARAM_STR);
$statement->bindValue(':bilname', $data[12], PDO::PARAM_STR);
$statement->bindValue(':note', $data[13], PDO::PARAM_STR);
}else {
$statement->bindValue(':multi',"", PDO::PARAM_STR);  
$statement->bindValue(':atena', $data[8], PDO::PARAM_STR);
$statement->bindValue(':post', $data[9], PDO::PARAM_STR);
$statement->bindValue(':address', $data[10], PDO::PARAM_STR);
$statement->bindValue(':bilname', $data[11], PDO::PARAM_STR);
$statement->bindValue(':note', $data[12], PDO::PARAM_STR);
}
$statement->bindValue(':seminarName', $seminarName, PDO::PARAM_STR);
$timestamp = $data[0];
$timestamp = str_replace(['午後', '午前'], ['PM', 'AM'], $timestamp);
// DateTimeオブジェクトを作成
$date = new DateTime($timestamp, new DateTimeZone('Asia/Tokyo'));

// 24時間フォーマットを指定して出力（タイムゾーン情報を除く）
$formattedDate = $date->format('Y-m-d H:i:s');
$statement->bindValue(':timestamp', $formattedDate, PDO::PARAM_STR);
$statement->execute();
 
}






            
?>