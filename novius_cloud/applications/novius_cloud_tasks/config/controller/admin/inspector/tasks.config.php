<?php

$allSiteTasks = \NC\Tasks\Tools_ProgrammedTasks::getAllSiteTasks();

$data = array();
foreach ($allSiteTasks as $task) {
    $data[] = array(
        'id'    => $task['identifier'],
        'title' => $task['identifier']
    );
}

return array(
    'data'    => $data,
    'input'   => array(
        'key'   => 'tala_task',
        'query' => function ($taskIdentifier, $query) use ($allSiteTasks) {
                if (!empty($allSiteTasks[$taskIdentifier]['className'])) {
                    $query->where('tala_task', '\\'.ltrim($allSiteTasks[$taskIdentifier]['className'], '\\'));
                }
                return $query;
            },
    ),
    'appdesk' => array(
        'vertical' => true,
        'label' => __('Tasks'),
        'inputName' => 'tala_task',
        'url'  => 'admin/novius_cloud_tasks/inspector/tasks/list',
        'grid' => array(
            'columns' => array(
                'id' => array(
                    'headerText' => 'Identifier',
                    'visible'    => false,
                    'dataKey'    => 'id'
                ),
                'title' => array(
                    'headerText' => 'Tasks',
                    'visible'    => true,
                    'dataKey'    => 'title'
                )
            )
        )
    )
);