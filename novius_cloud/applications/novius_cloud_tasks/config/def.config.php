<?php

/*
 * Commande PHP à lancer
 * En DEV, sur les versions nosl, la commande PHP n'est pas la même qu'en prod
 */
$cmdPhp = '/opt/php/cur/cur/bin/php';
if (class_exists('Fuel') && Fuel::$env === Fuel::DEVELOPMENT) {
    $cmdPhp = PHP_BINDIR.'/php';
}

return array(
    'cmd_php'             => $cmdPhp,
    'logdir'              => 'logs/fuel/tasks/',
    'paths'               => array(
        'tasklaunch'  => realpath(__DIR__.'/../exec/').'/tasklaunch.php',
        'taskexecute' => realpath(__DIR__.'/../exec/').'/taskexecute.php',
    ),
    'serialized_filepath' => APPPATH.'/data/cron.serialized'
);
