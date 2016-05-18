<?php

// Chargement unique de Novius Cloud
if ( defined('NOVIUS_CLOUD_LOADED') ) {
    return false;
}
define('NOVIUS_CLOUD_LOADED', true);

// Liste des applications Novius Cloud
\Event::register_function('config|nos::applications_repositories', function(&$config) {
    $config['novius_cloud'] = array(
		'path' => NOSROOT.'novius-os/novius_cloud/applications/',
		'visible' => false,
		'native' => true,
    );
    if (is_dir(APPPATH.'applications_cloud/')) {
        $config['applications_cloud'] = array(
            'path'    => APPPATH.'applications_cloud/',
            'visible' => true,
            'native'  => false,
        );
    }
});

\Module::load('novius_cloud');