<?php

$taskidentifier = \Input::get('taskidentifier', '');
$urlJson        = \NC\Tasks\Controller_Admin_Logs::get_path().'/json';

return array(
    'model' => 'NC\Tasks\Model_Tasklaunch',
    'appdesk' => array(
        'tab'     => array(
            'iconUrl' => 'static/apps/novius_cloud_tasks/img/tasks-log-32.png',
        ),
        'appdesk' => array(
            'grid' => Array(
                'urlJson' => $urlJson
            )
        ),
    ),
    'inspectors' => array('tasks'),
    'search_text' => 'tala_task',
    'i18n' => array(
        'item' => __('tasklaunch'),
        'items' => __('tasklaunch'),
        'showNoItem' => __('No tasklaunch'),
        'showAll' => __('Showing all tasklaunch'),
    ),
);