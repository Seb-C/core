<?php

/**
 * Affichage du tableau des tâches programmées
 *
 * @var $programmedTasks    array   Liste des programmations des tâches
 */

?>

<h1 class="title"><?= e(__('Tâches programmées')) ?></h1>
<?php
if (empty($programmedTasks)) {
    echo '<p>', __('Aucune tâche programmée sur ce site.'), '</p>';
} else {
    ?>
    <table class="tasks_list">
        <thead>
        <tr>
            <td><?= e(__('Tâche')) ?></td>
            <td><?= e(__('Dernière execution')) ?></td>
            <td><?= e(__('Heures')) ?></td>
            <td><?= e(__('Minutes')) ?></td>
            <td><span title="<?= e(__('Quel jour de la semaine ? Lundi, mardi...')) ?>"><?= e(__('Jour')) ?></span></td>
            <td><?= e(__('Jour mois')) ?></td>
            <td><?= e(__('Actions')) ?></td>
        </tr>
        </thead>
        <tbody>
        <?php

        $progPatterns = array(
            'heures'        => array(
                '*' => 'Toutes',
            ),
            'minutes'       => array(
                '*' => 'Toutes',
            ),
            'jours_semaine' => array(
                '*'         => 'Tous',
                '1,2,3,4,5' => 'Du Lundi au Vendredi',
            ),
            'jours_mois'    => array(
                '*' => 'Tous',
            )
        );
        $progReplaces = array(
            'jours_semaine' => array(
                '*' => 'Tous',
                '0' => 'Dimanche',
                '1' => 'Lundi',
                '2' => 'Mardi',
                '3' => 'Mercredi',
                '4' => 'Jeudi',
                '5' => 'Vendredi',
                '6' => 'Samedi',
            ),
        );

        ksort($programmedTasks);
        foreach ($programmedTasks as $task) {

            // Tâche non valide ? Tâche barrée !
            $tasksAttrs = '';
            if (!empty($task['errors'])) {
                $tasksAttrs = ' class="error" title="Cette tâche n\'est pas valide :'."\n".implode("\n", $task['errors']).'"';
            }

            // Dernière execution
            $derniereExecution = 'Jamais';
            $lastLaunch        = \NC\Tasks\Model_Tasklaunch::find('last', array(
                'where' => array(
                    'tala_task' => $task['className'],
                )
            ));
            if (!empty($lastLaunch)) {
                $derniereExecution = $lastLaunch->tala_created_at.' ('.$lastLaunch->tala_status.')';
            }

            // Programmation
            $progView = array();
            foreach ($task['prog'] as $key => $str) {
                if (is_string($str)) {
                    $value = $str;
                    if (!empty($progPatterns[$key]) && !empty($progPatterns[$key][$str])) {
                        $value = $progPatterns[$key][$str];
                    } else {
                        $tmpArr = explode(',', $str);
                        if (!empty($progReplaces[$key])) {
                            $tmpArr = array_map(function ($value) use ($progReplaces, $key) {
                                return strtr($value, $progReplaces[$key]);
                            }, $tmpArr);
                        }
                        $value = implode(', ', $tmpArr);
                    }

                    $progView[$key] = $value;
                }
            }

            ?>
            <tr <?= $tasksAttrs ?> data-prog-id="<?= e($task['progID']) ?>" data-task-identifier="<?= e($task['identifier']) ?>">
                <td><?= e($task['identifier']) ?></td>
                <td style="text-align: center;"><?= $derniereExecution ?></td>
                <td style="text-align: center;"><?= $progView['heures'] ?></td>
                <td style="text-align: center;"><?= $progView['minutes'] ?></td>
                <td style="text-align: center;"><?= str_replace(', ', '<br />', $progView['jours_semaine']) ?></td>
                <td style="text-align: center;"><?= $progView['jours_mois'] ?></td>
                <td>
                    <a class="js_logs" href="">Logs</a>
                    <a class="js_programmation" href="">Modifier</a>
                    <a class="js_suppression" href="">Supprimer</a>
                </td>
            </tr>
        <?php
        }
        ?>
        </tbody>
    </table>

<?php }