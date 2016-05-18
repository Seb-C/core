<?php

// Extend de noviusos_appmanager pour être appelé lors du chargement de l'appdesk
$version = \Config::load('nos::version', true);
if (\Str::starts_with(\Arr::get($version, 'fullname'), 'Chiba')) {
    $extends = array(
        'application'          => 'noviusos_appmanager',
        'extend_configuration' => false,
    );
} else {
    $extends = array(
        'noviusos_appmanager',
    );
}

return array(
    'name'      => 'Novius Cloud - Tasks',
    'version'   => '0.2',
    'provider'  => array(
        'name' => 'Novius Cloud',
    ),
    'namespace' => 'NC\Tasks',
    'extends'   => $extends,
    'launchers' => array(
        // Ajouté en live via un event (voir bootstrap)
        /*'novius_cloud_tasks_config' => array(
            'name'   => 'Tasks Config',
            'action' => array(
                'action' => 'nosTabs',
                'tab'    => array(
                    'url' => 'admin/novius_cloud_tasks/config',
                ),
            ),
        ),*/
    ),
    'icons'     => array(
        16 => 'static/apps/novius_cloud_tasks/img/tasks-config-16.png',
        32 => 'static/apps/novius_cloud_tasks/img/tasks-config-32.png',
        64 => 'static/apps/novius_cloud_tasks/img/tasks-config-64.png',
    )
);
