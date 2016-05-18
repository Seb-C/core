<?php

namespace NC;

class Controller_Serverconfig extends \Nos\Controller
{
    public function action_index()
    {
        // On retourne les cle qui commencent par NOVIUS_, ou NOS_, etc.
        $allowedPrefix = array('NOVIUS', 'NOS', 'FUEL');

        // On garde uniquement une partie de $_SERVER (voir $allowedPrefix)
        $server = array();
        foreach ($_SERVER as $k => $v) {
            if (in_array(current(explode('_', $k)), $allowedPrefix)) {
                $server[$k] = $v;
            }
        }

        $lang = getenv('LANG');
        if (!empty($lang)) {
            $server['LANG'] = $lang;
        }

        if (isset($_GET['debug'])) {
            d($_SERVER, '$_SERVER');
            d($server, '$server');
        }

        exit(serialize($server));
    }
}