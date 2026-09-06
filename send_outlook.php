<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // GmailのSMTPサーバー設定
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';  // GmailのSMTPサーバー
    $mail->SMTPAuth   = true;
    $mail->Username   = 'yajima73862618@gmail.com';  // Gmailアドレス
    $mail->Password   = 'elag agfj xllr sgoh';     // アプリパスワード
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // メール送信者と受信者の設定
    $mail->setFrom('yajima73862618@gmail.com', '矢島重比古');  // 送信元
    $mail->addAddress('office@jmca-npo.org', 'NPO日本メディカルライター協会');  // 受信者

    // メール内容の設定
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from PHP';
    $mail->Body    = 'This is a <b>test email</b> sent from PHP using Gmail SMTP server.';
    $mail->AltBody = 'This is a test email sent from PHP using Gmail SMTP server.';

    // メール送信
    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>

