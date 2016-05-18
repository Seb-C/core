<?php

define('NOS_ENV', $_SERVER['NOS_ENV']);
define('EN_PROD', NOS_ENV == 'prod');

define('NC_QUIET', isset($_GET['quiet']));

/**
 * Classe contenant les méthodes principales (ip...)
 */
\Autoloader::add_class('NC', __DIR__.'/classes/nc.php');

/**
 * Forcer l'utilisation du cache sur les properties
 */
\Config::set('novius-os.cache_model_properties', true);

/**
 * Fonctions Novius Cloud (d, dd...)
 */
require __DIR__.'/functions.php';

/**
 * StatD
 */
// Passage par un event pour être appelé APRES le bootstrap du site. Sinon, impossible de changer la config.
\Event::register_function('novius_cloud.local_bootstrap_loaded', function () {
    $config = \Config::load('novius_cloud::config', true);
    if (!empty($config['statsD']['enabled'])) {
        \NC\StatsD::init();
    }
});

/**
 * Email
 */
\NC\Email::init();

/**
 * Imagemagik
 */

\Autoloader::add_class('Image_Imagemagicknc', __DIR__.'/classes/image/imagemagicknc.php');

// Pour Chiba1 et antérieur
#\Config::set('cmd_convert', '/usr/bin/convert');

/**
 * Temp Dir
 */
\Config::set('novius-os.temp_dir', APPPATH.'data/tmp/');

/**
 * Tasks
 */

\Autoloader::addClassAlias('NC\\Tasks\\Task', 'Task');

/**
 * Migrations
 */

// Gestion des exceptions
\Event::register_function('migrate.exception', function($e, &$ignore, $migration) {
    $ignore = Fuel::$env != \Fuel::DEVELOPMENT;
    \NC::log($e->getMessage());
});


/**
 * CSRF
 */
\Event::register_function('admin.csrfFail', function($params) {
    $uriSegments = \Uri::segments();

    \NC::log('[admin.csrfFail] '.implode('/', $uriSegments).' - ip='.\NC::remoteIp().' - passed='.(int)$params['passed']);
});


/**
 * Robots.txt
 */
\Event::register_function('404.start', function ($params) {
    if ($params['url'] == 'robots.txt') {
        if (preg_match('`^h\d{2,5}\.novius\.net$`', $_SERVER['HTTP_HOST'])) {
            header('HTTP/1.0 200 OK');
            header("Content-Type: text/plain");
            echo "User-Agent: *\nDisallow: /";
            exit;
        } else {
            \Event::trigger_function('novius_cloud.robotstxt.notfound', array($params));
        }
    }
});


/**
 * Login History
 */

\Event::register_function('config|novius_loginhistory::config', function (&$config) {
    $config['callback_ip'] = function () {
        return \NC::remoteIp();
    };
});


/**
 * Outils internes
 */
if (isset($_GET['noviuscloud']) && \NC::isIpIn()) {
    if (!empty($_GET['noviuscloud'])  && ($_GET['noviuscloud'] == 'apc')) {
        require_once NOSROOT.'novius-os/novius_cloud/applications/novius_cloud/apc.php';
        exit;
    }
    if (!empty($_GET['noviuscloud'])  && ($_GET['noviuscloud'] == 'opcache')) {
        require_once NOSROOT.'novius-os/novius_cloud/applications/novius_cloud/opcache.php';
        exit;
    }
    \Event::register_function('novius_cloud.local_bootstrap_loaded', function () {
        // ne jamais mettre les pages noviuscloud en cache (cf gerflor)
        header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");                                    // HTTP/1.0

        header('Content-Type: text/html; charset=utf-8');
        error_reporting(-1);
        if (!NC_QUIET) {
            echo \View::forge('novius_cloud::header');
        }

        $action = 'index';
        if (!empty($_GET['noviuscloud']) && ctype_alnum($_GET['noviuscloud'])) {
            $action = $_GET['noviuscloud'];
        }

        echo \NOS\Nos::hmvc('novius_cloud/'.$action);

        if (!NC_QUIET) {
            echo \View::forge('novius_cloud::footer');
        }
        exit;
    });
}
