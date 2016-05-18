<?php

/**
 * Ajout du launcher 'Tasks Config' à la volée. Ce qui permet de le faire uniquement pour les superadmin.
 */
\Event::register_function('admin.launchers', function (&$launchers) {
    if (\NC::isSuperAdmin()) {
        $task_config_position = \Arr::get($launchers, 'novius_cloud_tasks_config', array());
        $launchers['novius_cloud_tasks_config'] = array_merge($task_config_position, array(
            'name'             => 'Tasks Config',
            'action'           => array(
                'action' => 'nosTabs',
                'tab'    => array(
                    'url'     => 'admin/novius_cloud_tasks/config',
                    'iconUrl' => 'static/apps/novius_cloud_tasks/img/tasks-config-32.png'
                ),
            ),
            'i18n_application' => 'novius_cloud_tasks',
            'application'      => 'novius_cloud_tasks',
            'icon'             => 'static/apps/novius_cloud_tasks/img/tasks-config-64.png'
        ));
    }
});