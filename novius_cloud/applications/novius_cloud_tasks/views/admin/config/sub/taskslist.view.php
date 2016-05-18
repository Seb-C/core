<?php

/**
 * Affichage de la liste des tâches du site pour permettre de programmer une nouvelle tâche
 *
 * @var $allSiteTasks       array   Toutes les tâches disponibles
 */

?>

    <h1 class="title"><?= e(__('Programmer une nouvelle tâche')) ?></h1>
<?php
if (empty($allSiteTasks)) {
    $urlDoc = 'http://wiki.lyon.novius.fr/index.php/Novius_Cloud_-_Crons_et_Nos_Tasks';
    echo '<p>', __('Aucune tâche trouvée sur ce site.'), ' <a href="', $urlDoc, '" target="_blank">', __('Comment créer une nouvelle tâche ?'), '</a></p>';
} else {
    ?>
    <table class="tasks_list">
        <thead>
        <tr>
            <td><?= e(__('Tâche')) ?></td>
            <td><?= e(__('Actions')) ?></td>
        </tr>
        </thead>
        <tbody>
        <?php
        ksort($allSiteTasks);
        foreach ($allSiteTasks as $task) {

            // Tâche non valide ? Tâche barrée !
            $tasksAttrs = '';
            if (!empty($task['errors'])) {
                $tasksAttrs = ' class="error" title="Cette tâche n\'est pas valide :'."\n".implode("\n", $task['errors']).'"';
            }
            ?>
            <tr <?= $tasksAttrs ?> data-task-identifier="<?= e($task['identifier']) ?>">
                <td><?= e($task['identifier']) ?></td>
                <td>
                    <a class="js_programmation" href="">Programmer</a>
                    <a class="js_lancement_manuel" href="">Lancement manuel</a>
                </td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>

<?php }