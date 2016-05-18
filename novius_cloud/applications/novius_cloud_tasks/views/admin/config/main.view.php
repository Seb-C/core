<?php

/**
 * Tableau de bord des programmations de tâche :
 * - Affichage du tableau des tâches programmées
 * - Affichage de la liste des tâches du site pour permettre de programmer une nouvelle tâche
 *
 * @var $programmedTasks    array   Liste des programmations des tâches
 * @var $allSiteTasks       array   Toutes les tâches disponibles
 *
 * @var $__data             array   Liste de toutes les variables dispo.
 */

?>

<div class="tasks page line ui-widget" id="<?= $div_id = uniqid('taskmanager_') ?>">
    <div class="content col c8">

        <?= \View::forge('novius_cloud_tasks::admin/config/sub/proglist', $__data); ?><br />

        <?= \View::forge('novius_cloud_tasks::admin/config/sub/taskslist', $__data); ?>

    </div>
    <div class="col c4 rightpanel"></div>
</div>

<?php
$jsvars = array(
    'containerID'  => $div_id,
    'appUrl'       => 'admin/novius_cloud_tasks',
    'allSiteTasks' => $allSiteTasks,
);
?>
<script type="text/javascript">
    require(
        [
            'jquery-nos',
            'wijmo.wijtabs',
            'wijmo.wijgrid',
            'static/apps/novius_cloud_tasks/js/tasksconfig.js',
            'link!static/apps/novius_cloud_tasks/css/tasks.css'
        ],
        function($)
        {
            tasksconfig($, <?= json_encode($jsvars); ?>);
        }
    );
</script>