<?php

/**
 * Fichier appelé juste avant le "init" de FuelPHP
 * Ce qui permet de faire des modifs au plus haut niveau (en fait, juste après le chargement des config de base)
 * Accessoirement, cela permet d'etre plus optimisé que de passer par des config
 */

// Il est quelle heure ?
define('NOVIUS_CLOUD_MICROTIME_START', microtime(true));

if (isset($_SERVER['FUEL_ENV']) && ($_SERVER['FUEL_ENV'] == 'development') && empty($_COOKIE['noviuscloud_noerrorall'])) {
    // à partir de Dubrovka (qui introduit l'espagnol) uniquement
    if (is_dir(NOSROOT.'novius-os/framework/lang/es/')) {
        error_reporting(E_ALL | E_STRICT);
    }
}

// Nouveau repertoire pour les applications
$config_nos['module_paths'] = array_merge($config_nos['module_paths'], array(
    NOSROOT.'novius-os/novius_cloud/applications/',
    APPPATH.'applications_cloud/'
));

// Quand on charge un fichier de config "de base", on regarde aussi dans la config "de base" Novius Cloud
$config_nos['novius-os']['finder_paths'] = array(
    APPPATH,
    NOSROOT.'novius-os/novius_cloud/',
    NOSPATH,
    COREPATH,
);

// On veut ImageMagick !
$config_nos['cmd_convert'] = '/usr/bin/convert';
