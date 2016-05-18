<?php

return array(
    'statsD' => array(
        'enabled'          => !empty($_SERVER['NOVIUS_MONIT']),
        'url'              => !empty($_SERVER['NOVIUS_MONIT']) ? $_SERVER['NOVIUS_MONIT'] : '',
        'port'             => 8125,
        'log'              => true,
        'log_timelimit_ms' => 30 * 1000, // Si le temps d'une page front depasse ce tps en MILLI-SECONDES, on log
        'log_all'          => false,
    ),
    'geoip'  => array(
        'url' => !empty($_SERVER['NOVIUS_GEOIP_URL']) ? $_SERVER['NOVIUS_GEOIP_URL'] : 'http://geoip.novius.net/?ip={{ip}}',
    )
);