<?php

namespace NC\Tasks;

class Manager
{
    static $config = null;

    public static function launch($task, $arrEnv = array())
    {
        // Securite pour éviter les boucles infinies (avec un launch() dans le bootstrap du site par exemple !)
        // Car launch() charge une page avec ?noviuscloud pour charger la config serveur
        if(!empty($_GET['noviuscloud'])) {
            echo 'Impossible de lancer launch() avec un GET noviuscloud';
            return false;
        }

        $arrEnv = array_merge(array(
            'dirsite'     => NOSROOT,
            'SERVER_NAME' => static::getTaskServerName(),
            'HTTP_HOST' => static::getTaskServerName(),
        ), (array) $arrEnv);

        $arrEnv['task']  = $task;
        $arrEnv['token'] = static::generateToken();

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $arrEnv['REMOTE_ADDR'] = \NC::remoteIp();
        }

        // Verbose
        if(isset($arrEnv['v'])) {
            echo 'Exec : '.static::cmdize('tasklaunch', $arrEnv, true), "\n";
        }

        system(static::cmdize('tasklaunch', $arrEnv, true));

        return $arrEnv['token'];
    }

    /**
     * Retourne le "SERVER_NAME" a utiliser
     *
     * Sur certain sites (comme la cinemateque), Novius OS est intégré dans des sous-dossiers. Donc besoin de taper sur
     * un autre domaine pour récupérer la conf serveur notamment.
     * Sinon, on utilise HTTP_HOST, qui est plus adapté dans le cas d'un nginx+apache
     *
     * @return mixed
     */
    public static function getTaskServerName()
    {
        return (!empty($_SERVER['NOS_TASKS_SERVERNAME']) ? $_SERVER['NOS_TASKS_SERVERNAME'] : $_SERVER['HTTP_HOST']);
    }

    public static function cmdize($exec, $arrParams = array(), $detach = false)
    {
        $config = static::config();

        if (empty($config['paths'][$exec])) {
            throw new \Exception('Commande '.$exec.' introuvable');
        }

        $params = '';
        foreach ($arrParams as $k => $v) {
            $params .= ' -'.$k.'='.str_replace('\\', '\\\\', $v);
        }

        // On doit se détacher (et donc utiliser '&' et rediriger les sorties)
        if ($detach) {
            /*
             * ==>  Ce bout de code est Hervé-Certified  <==
             * On écrit dans un fichier temporaire d'erreur
             * Si jamais ce fichier n'est pas writable par l'user courant, cela provoquera une "non-execution de la commande"
             * On écrit alors dans un fichier unique
             */
            $errLogPath = '/tmp/nos_task_error.log';
            if(is_file($errLogPath) && !is_writable($errLogPath)) {
                $errLogPath = '/tmp/nos_task_error.'.date('Ymd-His').'.'.uniqid().'.log';
            }
            $detach = '1>/dev/null 2>>'.$errLogPath.' &';
        } else {
            $detach = '';
        }

        // en php-cli, le error_log=/dev/stderr semble être par défaut, mais on laisse car c'était forcé sur Publi...
        return $config['cmd_php'].' -d error_log=/dev/stderr '.$config['paths'][$exec].' '.$params.' '.$detach;
    }

    public static function getServerConfig($serverName)
    {
        $urlConfig    = 'http://'.$serverName.'/?noviuscloud=serverconfig&quiet';
        $serverConfig = unserialize(file_get_contents($urlConfig));

        // Erreur fatale. On est trop "haut" pour faire un log propre...
        if (!is_array($serverConfig)) {
            throw new \Exception('Task :: impossible de charger la config serveur ('.$urlConfig.')');
        }

        return $serverConfig;
    }

    public static function generateToken()
    {
        return sha1(uniqid('', true));
    }

    public static function getPseudoGet($addInfos = false)
    {
        $pseudoGet = array();
        foreach ($_SERVER['argv'] as $arg) {
            if (preg_match('`^\s*-([^=]*)=?(.*)$`i', $arg, $matches)) {
                $pseudoGet[$matches[1]] = $matches[2] ? $matches[2] : true;
            }
        }

        // S'il manque des infos, on auto-complete
        if ($addInfos) {
            if (empty($pseudoGet['launch_from'])) {
                $pseudoGet['launch_from'] = 'manuel';
            }
            if (!isset($pseudoGet['token'])) {
                $pseudoGet['token'] = \NC\Tasks\Manager::generateToken();
            }
        }

        return $pseudoGet;
    }

    public static function setApacheEnv($pseudoGet)
    {
        // Emulation de l'environnement apache
        foreach ($pseudoGet as $k => $v) {
            $_SERVER[strtoupper($k)] = $v;
        }

        // Dans le cas d'une éxécution planifiée, les variables d'environnement du sous-processus du cronjob sont
        // réinitialisées, la variable d'environnement LANG définie par le système n'est donc pas héritée, le setlocale
        // dans le processus PHP qui exécute le script CRON est donc défini par défaut ("C" au lieu de "fr_FR.UTF-8")
        // ce qui peut causer des erreurs d'encodage (par ex. pour les catacteres encodés en UTF-8 sur plusieurs octets)
        if (!empty($pseudoGet['LANG'])) {
            // Défini LANG dans l'environnement système du processus courant afin que le sous-processus
            // (cf. proc_open ci-dessous) l'hérite et définisse le bon setlocale
            putenv('LANG='.$pseudoGet['LANG']);
            // Mise à jour de $_ENV au cas ou il est utilisé (car putenv() ne met pas à jour $_ENV)
            $_ENV['LANG'] = $pseudoGet['LANG'];
            setlocale(LC_ALL, $pseudoGet['LANG']);
        }

        // Autres variables
        if (empty($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = '';
        }
	if (empty($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        }
    }

    /**
     * Lecture de la config
     * @return null
     */
    public static function config()
    {
        if (!isset(static::$config)) {
            // On utilise surtout pas \Config, Novius-OS n'étant pas forcément chargé !
            $dirConfig      = __DIR__.'/../config/def.config.php';
            static::$config = (array) include($dirConfig);
        }
        return static::$config;
    }

    /**
     * Est-ce le bon moment pour lancer cette tâche ?
     *
     * @param $task     array   Date(s) de lancement de la tâche
     * @param $compare  array   Date de reference (en général, la date actuelle)
     *
     * @return bool
     */
    public static function cronGoodTime($task, $compare)
    {
        $execution = true;
        $execution = $execution && static::_cronGoodTime($task['minutes'], $compare['minute']);
        $execution = $execution && static::_cronGoodTime($task['heures'], $compare['heure']);
        $execution = $execution && static::_cronGoodTime($task['jours_semaine'], $compare['jour_semaine']);
        $execution = $execution && static::_cronGoodTime($task['jours_mois'], $compare['jour_mois']);
        return $execution;
    }

    /**
     * Permet de savoir si
     *
     * $range == liste séparée par des virgules
     * 1,2,3,etc.
     * 4-9 représente l'intervalle 4 à 9
     * *\/7 représente 0, 7, 14, 21, 28, etc...
     * Combinaisons possibles : *\/10,27,15-19,44
     */
    protected static function _cronGoodTime(&$range, $compare)
    {
        if ($range == '') {
            return false;
        }
        if ($range == '*') {
            return true;
        }
        $valeurs = array();
        // Toutes les virgules sont des séparateurs
        $range2 = explode(',', $range);
        foreach ($range2 as $r) {
            // */5 = toutes les 5 minutes
            $e = explode('/', $r);
            if (count($e) == 2) {
                if ($e[1] <= $compare) {
                    foreach (range(0, $compare, $e[1]) as $v) {
                        $valeurs[] = $v;
                    }
                } else {
                    $valeurs[] = 0;
                }
            } else {
                // 5-15 = entre 5 et 15 (inclus)
                $e = explode('-', $r);
                if (count($e) == 2) {
                    foreach (range($e[0], $e[1]) as $v) {
                        $valeurs[] = $v;
                    }
                } else {
                    // valeur simple
                    $valeurs[] = $r;
                }
            }
        }
        // TESTS
        // asort($valeurs);
        // echo implode(',', array_unique($valeurs));

        return in_array($compare, $valeurs);
    }
}