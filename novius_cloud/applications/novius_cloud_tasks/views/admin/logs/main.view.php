<?php

/**
 * Affichage des détails (stdout, stderr) d'un lancement de tâche
 *
 * @var $taskIdentifier  string  Identifiant de la tâche correspondant à ce log
 * @var $taskLaunch      \NC\Tasks\Model_Tasklaunch  Model lié au lancement de la tâche
 */

?>

<div class="tasks taskslogs page ui-widget" id="<?= $div_id = uniqid('taskmanager_') ?>">
    <div class="line content">
        <div class="col c12">
            <h1 class="title"><?= strtr(__('Détails d\'un lancement de la tâche <em>{{taskIdentifier}}</em> le {{taskLaunchDate}}'), array(
                    '{{taskIdentifier}}' => e($taskIdentifier),
                    '{{taskLaunchDate}}' => e($taskLaunch->tala_created_at),
                )) ?> <span class="status"></span></h1>
        </div>
    </div>
    <div class="line">
        <div class="stdout col c6">
            <h2>Stdout</h2>
            <div class="stdout_content"></div>
        </div>
        <div class="stderr col c6">
            <h2>Stderr</h2>
            <div class="stderr_content"></div>
        </div>
    </div>
</div>



<?php

$jsvars = array(
    'containerID'  => $div_id,
    'appUrl'       => 'admin/novius_cloud_tasks',
    'taskLaunchID' => $taskLaunch->tala_id,
);

?>
<link type="text/css" rel="stylesheet" href="static/apps/novius_cloud_tasks/css/tasks.css" />
<script type="text/javascript">
    require(
        [
            'jquery-nos',
            'wijmo.wijtabs',
            'wijmo.wijgrid',
            'static/apps/novius_cloud_tasks/js/taskslog.js'
        ],
        function($)
        {
            tasks_openlog($, <?= json_encode($jsvars); ?>);
        }
    );
</script>