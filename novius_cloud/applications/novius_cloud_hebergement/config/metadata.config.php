<?php

return array(
    'name'    => 'Novius Cloud - Hebergement',
    'version' => '0.2',
    'provider' => array(
        'name' => 'Novius Cloud',
    ),
    'namespace' => 'NC\Hebergement',
    'launchers' => array(
        'novius_cloud_hebergement_stats' => array(
            'name' => 'Novius Cloud - Stats',
            'action' => array(
                'action' => 'nosTabs',
                'tab' => array(
                    'label' => 'Novius Cloud - Hébergement',
                    'url' => 'admin/novius_cloud_hebergement/stats',
                    'iconUrl' => 'static/apps/novius_cloud_hebergement/img/32-stats.png',
                ),
            ),
        ),
    ),
    'icons' => array(
        64 => 'static/apps/novius_cloud_hebergement/img/64-stats.png',
        32 => 'static/apps/novius_cloud_hebergement/img/32-stats.png',
        16 => 'static/apps/novius_cloud_hebergement/img/16-stats.png',
    ),
);
