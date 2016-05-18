<?php

/**
 * Vue permettant d'afficher le panel de programmation
 *
 * @var $task               array   Des infos sur la tâche, incluant parfois les infos de la programmation dans le cas d'une modification
 * @var $taskIdentifier     string  Identifier de la tâche (exemple: "local::test")
 */

?>

<a href="#" class="close js_close">x</a>

<h1 class="title">Programmer la tâche <em><?= $task['identifier'] ?></em></h1>
<form class="js_programmation" method="post" data-prog-id="<?= \Arr::get($task, 'progID', 0) ?>" data-task-identifier="<?= $taskIdentifier ?>">
    <div class="line">
        <div class="col c3">
            <?= \View::forge('novius_cloud_tasks::admin/config/sub/panel.progform.select', array(
                'task'      => $task,
                'title'     => 'Heures',
                'name'      => 'heures',
                'values'    => range(0, 23)
            )); ?>
        </div>
        <div class="col c3">
            <?= \View::forge('novius_cloud_tasks::admin/config/sub/panel.progform.select', array(
                'task'      => $task,
                'title'     => 'Minutes',
                'name'      => 'minutes',
                'values'    => range(0, 55, 5)
            )); ?>
        </div>
        <div class="col c3">
            <?= \View::forge('novius_cloud_tasks::admin/config/sub/panel.progform.select', array(
                'task'      => $task,
                'title'     => 'Jour semaine',
                'name'      => 'jours_semaine',
                'values'    => array(
                    1 => 'Lundi',
                    2 => 'Mardi',
                    3 => 'Mercredi',
                    4 => 'Jeudi',
                    5 => 'Vendredi',
                    6 => 'Samedi',
                    0 => 'Dimanche'
                )
            )); ?>
        </div>
        <div class="col c3">
            <?= \View::forge('novius_cloud_tasks::admin/config/sub/panel.progform.select', array(
                'task'      => $task,
                'title'     => 'Jour mois',
                'name'      => 'jours_mois',
                'values'    => range(1, 31)
            )); ?>
        </div>
    </div>
    <div style="text-align: right">
        <input type="submit" value="Programmer"/>
    </div>
</form>