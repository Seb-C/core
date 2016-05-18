<?php

class NC
{
    /** @brief Renvoie l'ip de l'internaute
     *  Permet de ne pas se préoccuper de la présence d'un proxy en frontal (ngnix, double apache...) chez Novius.
     *  Si le proxy n'est pas chez nous, on renvoit néanmoins son adresse car on ne peut pas lui faire confiance
     *
     * @source Publinova
     * @return string     L'ip utilisée de l'internaute.
     */
    static public function remoteIp()
    {
        static $cache_remote_ip = ''; // cache interne de la fonction remoteIp()

        if ($cache_remote_ip) {
            // on utilise le cache interne
            return $cache_remote_ip;
        }

        // en local on écrase $_SERVER['REMOTE_ADDR'] pour ne plus l'utiliser dans les développements, on passe donc par la constante qui contient sa valeur d'origine
        // @todo On fait avec Novius OS ? Ca risque de poser pb avec les apps OpenSource qui utilisent l'ip.
        $REMOTE_ADDR = defined('REMOTE_ADDR_NE_PAS_UTILISER_SAUF_DANS_REMOTE_IP') ? REMOTE_ADDR_NE_PAS_UTILISER_SAUF_DANS_REMOTE_IP : \Arr::get($_SERVER, 'REMOTE_ADDR');

        // liste des clés pouvant contenir l'adresse d'un proxy chez Novius
        // Mais attention, l'info transite dans les entêtes http (ex: X-Forwarded-For ou X-Real-Ip) que l'internaute peut aussi générer, il convient donc de les "valider"
        $server_keys = array(
            'HTTP_X_REAL_IP', // a priori pour Ngnix
            'HTTP_X_FORWARDED_FOR', // double apache, ancienne conf de la FIS uniquement
        );

        foreach ($server_keys as $key) {
            if (isset($_SERVER[$key]) && $_SERVER[$key]) {
                if (self::isIpIn(null, $REMOTE_ADDR)) {
                    // le proxy (dont l'ip est $REMOTE_ADDR) est chez nous, on lui fait confiance

                    // $_SERVER[$key] peut contenir une liste d'ips séparées par des virgules
                    $ips = explode(',', $_SERVER[$key]);
                    $ips = array_map('trim', $ips);

                    /** @todo Si la première IP n'est pas valide, regader les eventuelles suivantes ? */
                    if (!empty($ips[0]) && filter_var($ips[0], FILTER_VALIDATE_IP)) {
                        $cache_remote_ip = $ips[0]; // mise en cache
                        return $cache_remote_ip;
                    }
                }
            }
        }

        // cas standard
        $cache_remote_ip = $REMOTE_ADDR; // mise en cache
        return $cache_remote_ip;
    }

    /** @brief Ecrit un message dans le fichier de log
     *  Le fichier de log se trouve dans le répertoire logs du site
     *
     * @param  $log         string        Le message à écrire
     * @param  $filename    mixed        Nom du fichier de log
     *
     * @return bool                    $log a bien été écrit dans le fichier de log ?
     */
    static public function log($log, $filename = 'novius-os.log')
    {
        $dir      = \Config::get('log_path');
        $filepath = rtrim($dir, DS).DS.ltrim($filename, DS);
        if (is_dir($dir)) {

            // Creates the path to the filename
            $filedir = dirname($filepath);
            if (!is_dir($filedir)) {
                // create non existing dir
                if (!@mkdir($filedir, 0755, true)) {
                    return false;
                }
            }
            
            // Defines the write permission
            $droit = 'a';
            if (is_file($filepath)) {
                $filesize = filesize($filepath);
                if ($filesize > 50000 && $filename == 'novius-os.log') {
                    $droit = 'w';
                } elseif ($filesize > 100000) {
                    $droit = 'w';
                }
            }
            
            // Linearizes the message
            if (is_array($log) || is_object($log)) {
                $log = print_r($log, true);
            }

            // Writes the message
            if (($fp = fopen($filepath, $droit))) {
                @fwrite($fp, date('d/m/Y H:i:s') . ' - ' . reset(static::trace(1, 1)) . ' : ' . $log . "\n");
                @fclose($fp);
                return true;
            }
        }
        return false;
    }

    /**
     * Affichage propre de la backtrace
     *
     * @param int $nb_traces        Nombre de lignes de trace max à afficher
     * @param int $nb_trace_jumps   Nombre de lignes qu'on "saute" dans la backtrace
     *
     * @return array
     */
    static public function trace($nb_traces = 1, $nb_trace_jumps = 0)
    {
        if (empty($nb_traces)) {
            return array();
        }

        $traces = debug_backtrace();
        if ($nb_trace_jumps > 0) {
            $traces = array_slice($traces, $nb_trace_jumps);
        }

        $trace_output       = array();
        $print_line_numbers = count($traces) > 1 && $nb_traces > 1;
        $realpath           = realpath(dirname(NOSROOT)) . '/';
        $i                  = 0;

        foreach ($traces as $trace) {
            $trace_output[$i] = '';
            if ($print_line_numbers) {
                $trace_output[$i] .= ($i + 1) . ': ';
            }

            // Pour notre affichage, on ne s'intéresse qu'aux traces qui contiennent un fichier + num de ligne. En effet,
            // des appels à call_user_function (par ex) génèrent plusieurs traces intérmédiaires (sans ces informations)
            // qu'on souhaite ignorer.
            if (!empty($trace['file']) && !empty($trace['line'])) {
                $trace['file'] = str_replace($realpath, '', $trace['file']);
                $trace_output[$i] .= $trace['file'] . ':' . $trace['line'];

                // Si possible, on affiche le nom de la class::fonction() dans laquelle est appelée la fonction d() / log()
                if (!empty($traces[$i + 1]) && !empty($traces[$i + 1]['class']) && !empty($traces[$i + 1]['function'])) {
                    $trace_output[$i] .= ' - ' . $traces[$i + 1]['class'] . '::' . $traces[$i + 1]['function'] . '()';
                }

                // Certaines lignes de la backtrace n'étant pas pertinentes, on continue jusqu'à ce qu'on ait eu
                // suffisament de lignes qui nous intéressent. Du coup, parfois, on en parcourt 6 pour en afficher 3.
                if (++$i >= $nb_traces) {
                    break;
                }
            }
        }

        return $trace_output;
    }

    /** @brief Test si l'adresse IP de l'internaute appartient à une plage de valeur passée en paramètre
     *
     * @param $array             array      Tableau d'adresse ip. Un masque peut être spécifié en fin d'ip, après un /,
     *                                      pour ne faire porter la vérification que sur une partie de l'ip@n
     *                                      Exemple 212.99.46.0/24 cherchera les ip començant par 212.99.46.
     *                                      Optionnel, si non spécifié la vérification portera sur les Ip de novius
     * @param $my_ip             mixed      Optionnel, NULL par défaut. Si NULL on utilise remoteIp() comme adresse, sinon l'adresse donnée
     *
     * @return                  bool        L'IP appartient à la plage de valeur passée en parametre ?
     */
    static public function isIpIn($array = null, $my_ip = null)
    {
        if (!isset($array)) {
            $array = array(
                '91.194.100.0/24', // Classe IP Novius hébergement
                '212.99.46.0/24', // Classe IP Novius hébergement Completel
                '82.67.34.88', // Freebox Lyon
                '78.192.6.76', // Freebox Paris
                '82.127.106.105', // Livebox Orange
                '109.190.143.183', // SDSL OVH Lyon
                '88.190.14.243', // Zabbix
            );
            // on autorise toutes les classes locales (ip non routables) de la RFC 1918
            $array = array_merge($array, array('192.168.1.0/16', '10.139.30.0/8', '172.16.0.0/12', '127.0.0.1'));
        }
        if (!$my_ip) {
            $my_ip = self::remoteIp();
        }
        foreach ((array) $array as $ipmasque) {
            $e        = explode('/', $ipmasque);
            $ip       = array_shift($e);
            $masque   = !empty($e) ? array_shift($e) : 32;
            $masque   = bindec(str_repeat(1, $masque).str_repeat(0, 32 - $masque));
            $check_ip = ip2long($my_ip) & $masque;
            $match_ip = ip2long($ip) & $masque;
            if ($check_ip == $match_ip) {
                return true;
            }
        }
        return false;
    }

    /**
     * Est-ce que l'utilisateur courant est connecté au BO et est super-admin ?
     * La notion de superadmin est gérée via le champs "user__nc_superadmin" de la table nos_user
     *
     * @return bool
     */
    static public function isSuperAdmin()
    {
        $curUser = Session::user();
        return (!empty($curUser->user__nc_superadmin));
    }
}