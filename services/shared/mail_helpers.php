<?php

require_once __DIR__ . '/../app_bootstrap.php';

function app_send_best_effort_email(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $altBody = null
): bool {
    $toEmail = trim($toEmail);
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return false;
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = getenv('SMTP_HOST') ?: 'localhost';
        $mail->SMTPAuth = filter_var(getenv('SMTP_AUTH') ?: false, FILTER_VALIDATE_BOOLEAN);

        if ($mail->SMTPAuth) {
            $mail->Username = getenv('SMTP_USER') ?: '';
            $mail->Password = getenv('SMTP_PASS') ?: '';
        }

        $secure = trim((string) (getenv('SMTP_SECURE') ?: ''));
        if ($secure !== '') {
            $mail->SMTPSecure = $secure;
        }

        $mail->Port = getenv('SMTP_PORT') ? (int) getenv('SMTP_PORT') : 25;
        $mail->setFrom(getenv('SMTP_FROM') ?: 'noreply@example.com', 'Famagusta Dart Club');
        $mail->addAddress($toEmail, trim($toName) !== '' ? $toName : $toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody ?? trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody)));
        $mail->send();

        return true;
    } catch (Throwable $exception) {
        return false;
    }
}
