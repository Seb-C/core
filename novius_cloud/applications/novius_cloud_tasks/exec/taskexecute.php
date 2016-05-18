<?php

require __DIR__.'/../classes/manager.php';

// Chargement de l'environnement transféré
$pseudoGet = \NC\Tasks\Manager::getPseudoGet();

// Secuite
if (!isset($pseudoGet['dirsite'], $pseudoGet['task'])) {
    exit("\nFatal error (taskexecute) : site et / ou tache manquant(s)\n");
}

// Pas de coloration "console" par Fuel sur les erreurs PHP
require $pseudoGet['dirsite'].'novius-os/fuel-core/classes/cli.php';
class Cli extends \Fuel\Core\Cli
{
    static function color($text, $foreground, $background = null) {
        return $text;
    }
}

// Encapsulation Apache + Novius OS
\NC\Tasks\Manager::setApacheEnv($pseudoGet);
define('NOS_ENTRY_POINT', 'NosTaks');
require_once $pseudoGet['dirsite'].'novius-os/framework/bootstrap.php';

// Securité : La task à lancer existe, au moins ?
if (!class_exists($pseudoGet['task'])) {
    echo "\nFatal error (tasklaunch)";
    echo "\n  Site   : ".basename($pseudoGet['dirsite']);
    echo "\n  Error : task ".$pseudoGet['task'].' not found';
    exit;
}

$task = new $pseudoGet['task']();

// Surtout pas de buffering, sinon on aura jamais les logs en live !
if (ob_get_level()) {
    ob_end_clean();
}

$task->run();
$task->success();