<?php

$smtp_host = 'localhost';
$smtp_port = 25;
if (!empty($_SERVER['NOS_SMTP'])) {
    list($smtp_host, $smtp_port) = array_pad(explode(':', trim($_SERVER['NOS_SMTP'])), 2, null);
    $smtp_port = $smtp_port ? : 25;
}

$siteDomain = str_replace('www.', '', !empty($_SERVER['SERVER_ALIAS']) ? $_SERVER['SERVER_ALIAS'] : $_SERVER['SERVER_NAME']);
return array(
    'defaults' => array(
        /**
         * Mail driver (mail, smtp, sendmail)
         */
        'driver'  => 'smtpNoviusCloud',

        /**
         * Whether to send as html, set to null for autodetection.
         */
        'is_html' => null,

        /**
         * Default sender details
         */
        'from'    => array(
            'email' => 'noreply@'.$siteDomain,
            'name'  => $siteDomain,
        ),

        /**
         * Default Return Path
         * @url http://redmine.lyon.novius.fr/issues/6324
         */
        'return_path' => 'sites@novius-cloud.com',

        /**
         * Variables personnalisées
         */
        'useragent' => 'Novius',

        /**
         * Path to sendmail, if you choose sendmail driver
         */
        //'sendmail_path' => '/usr/sbin/sendmail',

        /**
         * SMTP settings, if you choose smtp driver
         */
        'smtp'    => array(
            'host'     => $smtp_host,
            'port'     => $smtp_port,
            'username' => '',
            'password' => '',
            'timeout'  => 5,
        ),
    ),
);