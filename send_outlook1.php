<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->CharSet = 'UTF-8';
    $mail->isSMTP();
    $mail->Host       = 'dc25.etius.jp';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'office@jmca-npo.org';
    $mail->Password   = 'jmca7386';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('office@jmca-npo.org', 'NPO日本メディカルライター協会');
    $mail->addAddress('office@jmca-npo.org', 'NPO日本メディカルライター協会');

    // 件名を明確に
    $mail->Subject = 'NPO日本メディカルライター協会からの重要なお知らせ';

    // HTML とテキストの両方を用意
    $mail->isHTML(true);
    $mail->Body    = '日本メディカルライター協会からのテストメールです。ご確認ください。';
    $mail->AltBody = '日本メディカルライター協会からのテストメールです。ご確認ください。';

    // 添付ファイル
    $mail->addAttachment('./aaaa/test.pdf');

    // メール送信
    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"; 
}
?>

