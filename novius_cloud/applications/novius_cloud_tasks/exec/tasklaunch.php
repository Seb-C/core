<?php

require __DIR__.'/../classes/manager.php';

// Chargement de l'environnement transféré
$pseudoGet = \NC\Tasks\Manager::getPseudoGet(true);

// Sécurité : Vérif des paramètres (bien qu'il n'y a pas de raison qu'il ne soit pas là)
if (!isset($pseudoGet['dirsite'], $pseudoGet['task'])) {
    exit("Fatal error (tasklaunch) : dirsite et/ou task manquant(s).\n");
}
if (!isset($pseudoGet['SERVER_NAME'])) {
    exit("Fatal error (tasklaunch) : SERVER_NAME manquant.\n");
}
$pseudoGet['dirsite'] = rtrim($pseudoGet['dirsite'], '/').'/';

// Config serveur
$pseudoGet = array_merge($pseudoGet, \NC\Tasks\Manager::getServerConfig($pseudoGet['SERVER_NAME']));

// Encapsulation Apache + Novius OS
\NC\Tasks\Manager::setApacheEnv($pseudoGet);
define('NOS_ENTRY_POINT', 'NosTaks');
require_once $pseudoGet['dirsite'].'novius-os/framework/bootstrap.php';
error_reporting(E_ALL);

// Securité : La task à lancer existe, au moins ? C'est une Task valide ?
if (!class_exists($pseudoGet['task'])) {
    echo "\nFatal error (tasklaunch)";
    echo "\n  Site   : ".basename($pseudoGet['dirsite']);
    echo "\n  Erreur : tache ".$pseudoGet['task'].' non trouvée';
    echo "\n";
    exit;
}
if (!is_subclass_of($pseudoGet['task'], 'NC\Tasks\Task')) {
    echo "\nFatal error (tasklaunch)";
    echo "\n  Site   : ".basename($pseudoGet['dirsite']);
    echo "\n  Erreur : tache ".$pseudoGet['task'].' n\'extends pas \Task';
    echo "\n";
    exit;
}
if (!method_exists($pseudoGet['task'], 'config')) {
    echo "\nFatal error (tasklaunch)";
    echo "\n  Site   : ".basename($pseudoGet['dirsite']);
    echo "\n  Erreur : tache ".$pseudoGet['task'].', impossible de lire la config';
    echo "\n";
    exit;
}

// Config de la task
$taskConfig = $pseudoGet['task']::config();

// Création du dossier de log, si besoin
$logsdir = \NC\Tasks\Model_Tasklaunch::getLogsDir();
if (!is_dir($logsdir)) {
    mkdir($logsdir);
}

// Init avant lancement
$launchStatus = 'RUNNING';
$message      = '';
$noError  = true;
$noOutput  = true;
$infos        = false;

$executeTask = true;

// Tache exclusive deja en train de tourner ? On skip
if (!empty($taskConfig['exclusive'])) {
    if (\NC\Tasks\Model_Tasklaunch::isAlreadyRunning($pseudoGet['task'])) {
        $launchStatus = 'SKIPPED';
        $executeTask  = false;
    }
}

// Sauvegarde de la tâche en DB
$taskLaunch = \NC\Tasks\Model_Tasklaunch::forge(array(
    'tala_token'       => $pseudoGet['token'],
    'tala_task'        => '\\'.ltrim($pseudoGet['task'], '\\'),
    'tala_pid'         => getmypid(),
    'tala_launch_from' => $pseudoGet['launch_from'],
    'tala_exclusive'   => (int) !empty($taskConfig['exclusive']),
    'tala_status'      => $launchStatus,
));
try {
    $taskLaunch->save();
} catch (\Database_Exception $e) {
    echo "\nFatal error (tasklaunch)";
    echo "\n  Site   : ".basename($pseudoGet['dirsite']);
    echo "\n  Erreur : tache ".$pseudoGet['task'].', impossible de sauvegarder le log d\'execution en base';
    echo "\n  Note : Le token d'une tache doit etre unique !";
    echo "\n  mysql_error : ".$e->getMessage();
    echo "\n";
    exit;
}


// On va lancer !
if ($executeTask) {

    $start = microtime(true);

    if (!empty($taskConfig['serverVars'])) {
        $pseudoGet = array_merge($pseudoGet, $taskConfig['serverVars']);
    }

    // Calcul de l'environnement à passer à la ligne de commande
    $cmd = \NC\Tasks\Manager::cmdize('taskexecute', $pseudoGet);

    // Ne pas trop faire attention aux commentaires ici... Ne fonctionne pas vraiment et on s'en fout en fait.
    $descriptorspec = array(
        0 => array('pipe', 'r'), // stdin est un pipe où le processus va lire
        1 => array('pipe', 'w'), // stdout est un pipe où le processus va écrire
        2 => array('pipe', 'w'), // stderr est un pipe où le processus va écrire
        //2 => array('file', '/dev/stderr', 'a'), // stderr est LE fichier d'erreur standard (pour ne pas affiche les erreurs dans la sortie "standard" stdout
    );

    // Ecriture live dans un fichier, pour avoir les résultats en direct
    $liveFiles = array(
        $logsdir.$taskLaunch->tala_id.'_stdout.log.live',
        $logsdir.$taskLaunch->tala_id.'_stderr.log.live',
    );
    $liveFp = array();
    foreach ($liveFiles as $file) {
        $liveFp[] = fopen($file, 'w+');
    }

    // Execute le script CRON dans un sous-processus qui hérite de l'environnement du processus courant
    $process = proc_open($cmd, $descriptorspec, $pipes);
    if (is_resource($process)) {

        $write  = null;
        $except = null;

        $results = array('', '');
        $sockets = array($pipes[1], $pipes[2]);

        // Lecture parallèle des flux d'entrée et de sortie
        while (count($sockets) > 0) {
            $read          = $sockets;
            $stream_select = stream_select($read, $write, $except, 10);
            if (false === $stream_select) {
                break;
            }
            foreach ($read as $current) {
                $i    = array_search($current, $sockets);
                $meta = stream_get_meta_data($current);
                if ($meta['eof']) {
                    unset($sockets[$i]);
                } else {
                    $unread   = max($meta['unread_bytes'], 2);
                    $contents = stream_get_contents($current, $unread);
                    $results[$i] .= $contents;

                    // Ecriture live dans un fichier (en live, sans buffer)
                    fwrite($liveFp[$i], $contents);
                    fflush($liveFp[$i]);
                }
            }
        }

        list($stdout, $stderr) = $results;

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]); // Pas besoin pour stderr si /dev/stderr
        proc_close($process);

        // Log !
        $time         = number_format(microtime(true) - $start, 3);
        $launchStatus = false;

        list($stdout, $outputStatus) = array_pad(explode(md5('%%%'), $stdout, 2), 2, null);
        if (!empty($stdout)) {
            file_put_contents('compress.zlib://'.$logsdir.$taskLaunch->tala_id.'_stdout.log.gz', $stdout);
            $noOutput = false;
        }
        if (!empty($stderr)) {
            file_put_contents('compress.zlib://'.$logsdir.$taskLaunch->tala_id.'_stderr.log.gz', $stderr);
            $noError = false;
        }
        if (!empty($outputStatus)) {
            $outputStatus = unserialize($outputStatus);
            if (!empty($outputStatus)) {
                $outputStatus['time'] = $time;

                $message = $outputStatus['message'];
                unset($outputStatus['message']);

                if ($outputStatus['type'] == 'success') {
                    $launchStatus = 'SUCCESS';
                } elseif ($outputStatus['type'] == 'nothing_to_do') {
                    $launchStatus = 'NOTHING_TO_DO';
                } elseif ($outputStatus['type'] == 'failure') {
                    $launchStatus = 'USER_ERROR';
                }
                unset($outputStatus['type']);
                $infos = $outputStatus;
            }
        }
        if (empty($infos)) {
            $infos = array('time' => $time);
        }
        if ($launchStatus == false) {
            $launchStatus = 'PHP_FATAL';
            $message      = __LINE__;
        }
    } else {
        $launchStatus = 'CRON_ERROR';
        $message      = __LINE__;
    }

    // Fin des logs live
    foreach ($liveFp as $fp) {
        fclose($fp);
    }
    foreach ($liveFiles as $file) {
        unlink($file);
    }
}

// Mise à jour de la tâche
$taskLaunch->set(array(
    'tala_status'    => $launchStatus,
    'tala_message'   => $message,
    'tala_no_error'  => (int) $noError,
    'tala_no_output' => (int) $noOutput,
    'tala_infos'     => $infos,
));
$taskLaunch->save();

$execStatus = 'failure';
if (in_array($launchStatus, array('SUCCESS', 'NOTHING_TO_DO'))) {
    $execStatus = 'success';
} else if (in_array($launchStatus, array('SKIPPED'))) {
    $execStatus = 'skipped';
}

// Trace
$listStatus = array(
    'RUNNING'       => "En cours d'éxécution",
    'SUCCESS'       => 'Exécution terminée avec succès',
    'NOTHING_TO_DO' => 'Aucune tâche à effectuer',
    'PHP_FATAL'     => 'Erreur PHP fatale : le cron a sans doute été arrêté prématurément',
    'USER_ERROR'    => 'Script arrêté de manière contrôlée par le développeur',
    'CRON_ERROR'    => 'Erreur critique : le lanceur de cron (niveau 2) a planté',
    'SKIPPED'       => 'Non exécuté : tâche exclusive avec une autre instance en cours',
);

if (!isset($listStatus[$launchStatus])) {
    $launchStatus = 'CRON_ERROR';
    $message      = __LINE__;
}

$trace   = array();
$trace[] = 'SITE = '.basename($pseudoGet['dirsite']);
$trace[] = 'CODE = '.$launchStatus.' '.$listStatus[$launchStatus];
if (!empty($message)) {
    $trace[] = 'MESSAGE = '.$message;
}
if (!empty($outputStatus)) {
    $trace[] = "STATUT\n".print_r($outputStatus, true);
}
if (!empty($stderr)) {
    $trace[] = "STERR\n".$stderr;
}
if (!empty($stdout)) {
    $trace[] = "STDOUT\n".$stdout;
}
$trace = implode("\n\n", $trace);

$pseudoGet['task']::afterRun($execStatus, $trace);

// Un peu de ménage
\NC\Tasks\Model_Tasklaunch::purgeTasksLaunch();
\NC\Tasks\Model_Tasklaunch::purgeTasksLaunchLogs();

exit;