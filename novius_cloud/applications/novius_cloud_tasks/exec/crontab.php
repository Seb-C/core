<?php

require __DIR__.'/../classes/manager.php';

// Chargement de l'environnement
$pseudoGet = \NC\Tasks\Manager::getPseudoGet();

// Securité
if (!isset($pseudoGet['dirsites'])) {
    exit("\nFatal error (crontab) : dirsites manquant\n");
}
$pseudoGet['dirsites'] = rtrim($pseudoGet['dirsites'], '/').'/';


if (!empty($pseudoGet['site']) && !empty($pseudoGet['task'])) {
    // Lancement d'une seule tâche sur un seule site ?
    if (!isset($pseudoGet['SERVER_NAME'])) {
        exit("Fatal error (tasklaunch) : SERVER_NAME nécéssaire si une task est forcée.\n");
    }
    $pseudoGet['dirsite'] = $pseudoGet['dirsites'].$pseudoGet['site'].'/';
    \NC\Tasks\Manager::launch($pseudoGet['task'], $pseudoGet);
} else {
    // Lancement de plusieurs tâches, sur tous les sites
    $now     = time() + 30; // Des fois ça s'éxécute à 58 secondes...
    $compare = array(
        'jour_semaine' => date('w', $now), // 0 pour dimanche à 6 pour samedi
        'jour_mois'    => date('j', $now),
        'heure'        => date('G', $now),
        'minute'       => (int) (date('i', $now) / 5) * 5, // Acceptable dans une période de 5 minutes
    );

    // Debug
    echo "\n", '$compare = ', print_r($compare, true);
    echo "\n", '$pseudoGet = ', print_r($pseudoGet, true);

    // Launch from
    if (empty($pseudoGet['launch_from'])) {
        $pseudoGet['launch_from'] = 'crontab';
    }

    // On cherche des crons
    if (!is_dir($pseudoGet['dirsites'])) {
        exit("\nFatal error (crontab) : ".$pseudoGet['dirsites']." n'est pas un dirsites valide\n");
    }

    // Lancement des crons pour tous les sites, si besoin
    $dir_sites = new DirectoryIterator($pseudoGet['dirsites']);
    foreach ($dir_sites as $site) {
        // Chaque dossier est un site
        if (!$site->isDot() && $site->isDir()) {
            $dirsite = $site->getPathname();

            // On doit le faire pou un seul site ? Dans ce cas, on passe
            // TODO Améliorer ça, c'est moche d'itérer pour rien
            if (!empty($pseudoGet['site']) && $pseudoGet['site'] != (string) $site) {
                continue;
            }

            // Si le dossier contient "-asupp", on le zappe (nomenclature Hervé pour passer un site en suppression)
            if (strpos($site->getBasename(), '-asupp') !== false) {
                echo $dirsite." zappé (pattern '-asupp')\n";
                continue;
            }

            // On recherche un fichier précis
            $search = $dirsite.'/local/data/cron.serialized';
            if (is_file($search)) {
                echo $search." found\n";

                // Qui doit avoir une forme précise
                if (($tasks = unserialize(file_get_contents($search))) && is_array($tasks)) {
                    foreach ($tasks as $taskName => $task) {

                        // Dans la v2 du crontab, la class est un argument (et non la clé), ce qui permet
                        // de programmer plusieurs fois une même tâche.
                        if (!empty($task['className'])) {
                            $taskName = $task['className'];
                        }

                        echo $taskName." found\n";

                        // Erreur : tâche mal configurée
                        if (!isset($task['minutes'], $task['heures'], $task['jours_semaine'], $task['jours_mois'])) {
                            echo "\n";
                            echo "Warning: Mauvaise configuration d'une tache (horaire non définie, ne sait pas quand la demarrer)";
                            echo '  Site   : '.$site."\n";
                            echo '  Tache  : '.$taskName."\n";
                            echo "\n";
                        }

                        // C'est le moment !
                        if (\NC\Tasks\Manager::cronGoodTime($task, $compare)) {
                            echo $site.' : '.$taskName." lancé\n";

                            // Possibilité d'ajouter des infos sur une task donnée
                            $pseudoGetTask = $pseudoGet;
                            if (!empty($task['env'])) {
                                $pseudoGetTask = array_merge($task['env'], $pseudoGetTask);
                            }
                            $pseudoGetTask['dirsite'] = $pseudoGetTask['dirsites'].$site.'/';
                            \NC\Tasks\Manager::launch($taskName, $pseudoGetTask);
                        } else {
                            echo $site.' : '.$taskName." launched\n";
                        }
                    }
                } else if (false === $tasks) {
                    echo "\n";
                    echo "Error: Format de /priv/cron.serialized incorrect\n";
                    echo '  Site   : '.$pseudoGet['site']."\n";
                    echo "\n";
                }
            }
        }
    }

}

exit("FIN\n");