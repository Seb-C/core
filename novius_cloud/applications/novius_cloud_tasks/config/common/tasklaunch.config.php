<?php

$allSiteTasks = \NC\Tasks\Tools_ProgrammedTasks::getAllSiteTasks();
$classToTask  = \Arr::assoc_to_keyval($allSiteTasks, 'className', 'identifier');

return array(
    'controller'   => 'logs',
    'data_mapping' => array(
        'tala_created_at'  => array(
            'title' => __('Date'),
            'value' => function ($item) {
                    return $item->tala_created_at;
                },
        ),
        'tala_task' => array(
            'title' => __('Task Identifier'),
            'value' => function ($item) use ($classToTask) {
                    return \Arr::get($classToTask, $item->tala_task, $item->tala_task);
                },
        ),
        'tala_status'      => array(
            'title'          => __('Status'),
            'value'          => function ($item) {
                    $colors = array(
                        'SUCCESS'       => 'green',
                        'NOTHING_TO_DO' => 'orange',
                        'RUNNING'       => 'orange',
                        'PHP_FATAL'     => 'red',
                        'TASK_ERROR'    => 'red',
                        'USER_ERROR'    => 'red',
                    );
                    return '<span style="color: '.\Arr::get($colors, $item->tala_status, 'inherit').'">'.$item->tala_status.'</span>';
                },
            'isSafeHtml'     => true,
            'cellFormatters' => array(
                'center' => array(
                    'type' => 'css',
                    'css'  => array('text-align' => 'center'),
                ),
            ),
        ),
        'tala_message'     => array(
            'title' => __('Message')
        ),
        'tala_launch_from' => array(
            'title' => __('Mode')
        ),
    ),
    'query'        => array(
        'model'    => 'NC\Tasks\Model_Tasklaunch',
        'order_by' => array('tala_id' => 'DESC'),
        'limit'    => 20,
        'callback' => array(),
    ),
    'actions' => array(
        'add'       => false,
        'edit'      => false,
        'visualise' => false,
        'delete'    => false,
        'openlog' => array(
            'action'      => array(
                'action' => 'nosTabs',
                'tab'    => array(
                    'url'     => '{{controller_base_url}}openlog/{{_id}}',
                    'label'   => 'Log',
                    'iconUrl' => 'static/apps/novius_cloud_tasks/img/tasks-log-16.png'
                ),
            ),
            'label'       => __('Ouvrir les logs'),
            'primary'     => true,
            'iconClasses' => 'nos-icon16 nos-icon16-eye',
            'red'         => true,
            'targets'     => array(
                'grid'         => true,
                'toolbar-edit' => false,
            ),
            'disabled'    => function ($item) {
                    return $item->tala_status == 'SUCCESS' && $item->tala_no_output == 1;
                },
            'visible'     => function ($params) {
                    return true;
                },
        ),
    ),
);