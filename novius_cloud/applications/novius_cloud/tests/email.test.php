<?php

d('Test Email');


$to     = !empty($_GET['to']) && ctype_alnum($_GET['to']) ? $_GET['to'] : 'guyomard';
$params = array(
    'from'    => 'guyomard@novius.fr',
    'to'      => $to.'@novius.com',
    'subject' => '[Novius Cloud] Test Email',
    'content' => '<strong>'.date(DATE_RSS).'</strong>',
);

d($params, '$params');


$mail = \Email::forge();

$mail->from($params['from']);
$mail->to($params['to']);
if (!empty($params['cc'])) {
    $mail->cc(array_filter(explode(',', $params['cc'])));
}
if (!empty($params['bcc'])) {
    $mail->bcc(array_filter(explode(',', $params['bcc'])));
}

$mail->subject($params['subject']);
$mail->html_body($params['content']);

$send = $mail->send();
d($send, '$mail->send()');