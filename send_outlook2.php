<?php
require './vendor/autoload.php';

// PHPMailerクラスを使用
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$phpmailer = new PHPMailer(true);               // PHPMailerのインスタンスを作成。trueを渡すことでexceptionsが有効になります。
$phpmailer->isSMTP();                           // SMTPを使用してメールを送信する設定にします。
// $phpmailer->SMTPDebug = SMTP::DEBUG_LOWLEVEL;   // SMTPのデバッグ情報を出力するための設定。デバッグレベルは低いものに設定。
$phpmailer->SMTPAuth   = true;                  // SMTP認証を有効にします。
$phpmailer->Host       = 'dc25.etius.jp';     // SMTPサーバーのホスト名やIPアドレスを設定。
$phpmailer->SMTPSecure = 'tls';                 // セキュリティのためのプロトコルを設定（'tls'または'ssl'）。
$phpmailer->Port       = '587';                 // SMTPサーバーのポート番号を設定。通常は587（TLSの場合）や465（SSLの場合）。
$phpmailer->Username = 'office@jmca-npo.org';// SMTP認証のためのユーザー名（通常はメールアドレス）を設定。
$phpmailer->Password = 'jmca7386';         // SMTP認証のためのパスワードを設定。

$phpmailer->CharSet = 'UTF-8';                  // メールの文字エンコーディングを'UTF-8'に設定します。
$phpmailer->setFrom('office@jmca-npo.org', '矢島重比古');      // 送信者のメールアドレスと名前を設定します。
$phpmailer->addAddress('office@jmca-npo.org', 'NPO日本メディカルライター協会'); // 受信者のメールアドレスと名前を設定します。
$phpmailer->Subject = '請求書ご送付の件';                   // メールの件名を設定します。
$phpmailer->Body    = 'ご応募ありがとうございます。請求書をご送付します。';               // メールの本文を設定します。

$phpmailer->send();
?>