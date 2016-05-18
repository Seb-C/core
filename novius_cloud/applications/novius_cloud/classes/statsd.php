<?php

namespace NC;

class StatsD
{
    static protected $INIT_OK = false;

    static public $front_hit = false;
    static public $times_start;
    static public $key = '';

    static protected $config;

    static public function init()
    {
        // On ne peut charger qu'une seule fois cette classe.
        if (self::$INIT_OK) {
            return false;
        }
        self::$INIT_OK = true;

        // statD activé ?
        static::$config = \Config::load('novius_cloud::config', true);
        static::$config = static::$config['statsD'];

        if (empty(static::$config['enabled'])) {
            return false;
        }

        // Génération de la clé
        // @TODO Modifier les valeurs par defaut de $server
        if (EN_PROD) {
            $server = !empty($_SERVER['NOVIUS_SERVER']) ? $_SERVER['NOVIUS_SERVER'] : 'w6';
        } else {
            $server = !empty($_SERVER['NOVIUS_SERVER']) ? $_SERVER['NOVIUS_SERVER'] : 'lnx3';
        }
        static::$key = $server.'.'.basename(NOSROOT).'.testing_nos';

        static::$times_start = microtime(true);

        // On veut savoir quand on est en front
        \Event::register_function('front.start', function() {
            \NC\StatsD::$front_hit = true;
        });

        // Avant de quitter, on envoit quelques stats
        register_shutdown_function(function () {

            // Nombre de requetes SQL
            if (\DB::$query_count > 0) {
                \NC\StatsD::updateStats('mysql.count', \DB::$query_count);
            }

            // Nombre de hit du front
            if (\NC\StatsD::$front_hit) {
                \NC\StatsD::increment('page_front');
            }

            // Temps total du front
            if (\NC\StatsD::$front_hit && defined('NOVIUS_CLOUD_MICROTIME_START')) {
                \NC\StatsD::timing('page_microtime',  intval((microtime(true) - NOVIUS_CLOUD_MICROTIME_START)*1000));
            }

        });

        return true;
    }

    static public function timing($stat, $time, $sampleRate = 1)
    {
        static::send(array($stat => $time.'|ms'), $sampleRate);
    }

    static public function increment($stats, $sampleRate = 1)
    {
        static::updateStats($stats, 1, $sampleRate);
    }

    static public function decrement($stats, $sampleRate = 1)
    {
        static::updateStats($stats, -1, $sampleRate);
    }

    static public function unique($stat, $key)
    {
        static::send(array($stat => $key.'|s'), 1);
    }

    static public function updateStats($stats, $delta = 1, $sampleRate = 1)
    {
        $stats = (array) $stats;
        $data  = array();
        foreach ($stats as $stat) {
            $data[$stat] = $delta.'|c';
        }
        static::send($data, $sampleRate);
    }

    static protected function send($data, $sampleRate = 1)
    {
        $sampledData = array();
        if ($sampleRate < 1) {
            foreach ($data as $stat => $value) {
                if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
                    $sampledData[$stat] = $value.'|@'.$sampleRate;
                }
            }
        } else {
            $sampledData = (array) $data;
        }

        if (empty($sampledData)) {
            return false;
        }
        if (empty(static::$config['url']) || empty(static::$config['port'])) {
            return false;
        }

        $fp = @fsockopen("udp://".static::$config['url'], static::$config['port'], $errno, $errstr);

        if (empty($fp)) {
            return false;
        }

        foreach ($sampledData as $stat => $value) {



            // Log ?
            $logMe    = false;
            $logInfos = '';
            if (!empty(static::$config['log_all'])) {
                // On log absolument tout
                $logMe = true;
            } elseif (!empty(static::$config['log'])) {
                // Log conditionnel. Pour éviter de tout avoir, on applique des règles, au cas par cas
                switch ( $stat ) {
                    case 'page_front' :
                        // on évite les logs dans ce cas "standard"
                        $logMe = false;
                        break;
                    case 'mysql.count' :
                        // MySQL, on log que s'il y a trop de requetes
                        $logMe = intval($value) > 50;
                        break;
                    case 'page_microtime' :
                        // Cas spécial. On va juste logger les pages lentes
                        $logMe = (!empty(static::$config['log_timelimit_ms']) && intval($value) > static::$config['log_timelimit_ms']);
                        break;
                    default :
                        $logMe = true;
                }
            }

            if ($logMe) {
                if ($stat == 'page_microtime') {
                    // On ajoute l'URL de la page, c'est plutôt pratique
                    $logInfos = '('.$_SERVER['REQUEST_URI'].')';
                }
                \NC::log($stat.':'.$value.' '.$logInfos, 'statsd.log');
            }

            $stat = static::$key.'.'.$stat;
            fwrite($fp, $stat.':'.$value);
        }
        fclose($fp);

        return true;
    }
}