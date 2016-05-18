<?php

namespace NC;


class Controller_Devoptions extends \Nos\Controller
{
    public function action_index()
    {
        $nocache   = !empty($_COOKIE['noviuscloud_nocache']);
        $profiling = !empty($_COOKIE['noviuscloud_profiling']);
        $noerrorall = !empty($_COOKIE['noviuscloud_noerrorall']);

        if ( \Fuel::$env === \Fuel::PRODUCTION ) {
            dd('Les options dev ne sont pas disponibles en prod', 0);
        }

        // Action sur le cache
        if (isset($_GET['nocache'])) {
            $nocache = (bool) $_GET['nocache'];
            if (!empty($_GET['nocache'])) {
                d('Cache désactivé pour 4h', 0);
                setcookie('noviuscloud_nocache', 1, time()+4*3600, '/');
                Controller_Videcache::videCache('pages');
            } else {
                d('Cache activé', 0);
                setcookie('noviuscloud_nocache', 0, time(), '/');
            }
        }

        // Action sur le profiler
        if (isset($_GET['profiling'])) {
            $profiling = (bool) $_GET['profiling'];
            if (!empty($_GET['profiling'])) {
                d('Profiler activé', 0);
                setcookie('noviuscloud_profiling', 1, time()+365*24*3600, '/');
            } else {
                d('Profiler desactivé', 0);
                setcookie('noviuscloud_profiling', 0, time(), '/');
            }
        }

        // Action sur error_reporting
        if (isset($_GET['noerrorall'])) {
            $noerrorall = (bool) $_GET['noerrorall'];
            if (!empty($_GET['noerrorall'])) {
                d('error_reporting(E_ALL | E_STRICT);  désactivé pour 4h', 0);
                setcookie('noviuscloud_noerrorall', 1, time()+4*3600, '/');
            } else {
                d('error_reporting(E_ALL | E_STRICT); activé', 0);
                setcookie('noviuscloud_noerrorall', 0, time(), '/');
            }
        }

        return \View::forge('novius_cloud::devoptions/options', array(
            'nocache'   => $nocache,
            'profiling' => $profiling,
            'noerrorall' => $noerrorall,
        ));
    }
}