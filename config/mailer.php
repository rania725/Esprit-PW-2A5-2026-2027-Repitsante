<?php
declare(strict_types=1);

function sendPasswordResetCode(string $email, string $code): bool
{
    $from = getenv('REPIT_MAIL_FROM');
    if ($from === false || trim($from) === '') {
        $from = (string) ini_get('sendmail_from');
    }

    if (
        filter_var($email, FILTER_VALIDATE_EMAIL) === false
        || filter_var($from, FILTER_VALIDATE_EMAIL) === false
        || preg_match('/\A[0-9]{6}\z/D', $code) !== 1
    ) {
        error_log('Password reset email not sent; verify the local mail configuration.');
        return false;
    }

    $subject = '=?UTF-8?B?' . base64_encode('Code de réinitialisation Répit Santé') . '?=';
    $body = "Ton code de réinitialisation est : {$code}\n\n"
        . "Il est valable pendant 10 minutes et ne peut être utilisé qu’une seule fois.\n"
        . "Si tu n’es pas à l’origine de cette demande, ignore cet e-mail.";
    $headers = [
        'From: =?UTF-8?B?' . base64_encode('Répit Santé') . '?= <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];

    if (!@mail($email, $subject, $body, implode("\r\n", $headers))) {
        error_log('Password reset email not sent; verify the local mail configuration.');
        return false;
    }
    return true;
}
