<?php

namespace NC;

/**
 * Tools_Geoip
 * Permet de récupérer des infos sur l'emplacement physique de l'utilisateur.
 * Utilise le service interne GeoIP de Novius (URL définie dans la config ou via Apache)
 * Utilisation basique : $pays = \NC\Tools_Geoip::getUserCountry();
 *
 * Pour les tests : possibilité de forcer l'adresse IP via le paramètre get "force_ip".
 * Exemple : toto.fr/titi.html?force_ip=123.123.123.123.
 * Une fois ceci fait, l'IP forcée est stockée en session, à condition que vous ayez fait un
 * session_start de votre coté
 *
 * Pour supprimer l'IP forcée ou pour la changer, remplir le paramètre GET reset_ip.
 * Exemple :
 * toto.fr/titi.html?force_ip=123.123.123.123&reset_ip=pouet
 * toto.fr/titi.html?reset_ip=tartempion
 *
 * @author Agnès Haasser <haasser@novius.fr>    - Classe initiale Publinova
 * @author Julien                               - Portage pour Novius OS
 */
class Tools_Geoip
{
    const SESSION_KEY = 'geoip';

    protected static $structure = array(
        'countryCode'  => false,
        'countryCode3' => false,
        'countryName'  => false,
        'region'       => false,
        'city'         => false,
        'postalCode'   => false,
        'latitude'     => false,
        'longitude'    => false,
        'areaCode'     => false,
        'dmaCode'      => false,
        'ip'           => false,
    );

    protected static $ipInfos = array();

    /**
     * Retourne le code pays à 2 lettres de l'utilisateur.
     * Toujours en majuscules.
     *
     * @param   string|null $ip Pour forcer une IP donnée. Si non définie, l'IP de l'utilisateur sera utilisée
     *
     * @return  string
     */
    public static function getUserCountryName($ip = null)
    {
        return self::getInfo('countryName', $ip);
    }

    /**
     * Retourne le nom complet du pays (en anglais) de l'utilisateur.
     * Toujours en majuscules.
     *
     * @param   string|null $ip Pour forcer une IP donnée. Si non définie, l'IP de l'utilisateur sera utilisée
     *
     * @return  string
     */
    public static function getUserCountryCode($ip = null)
    {
        return self::getInfo('countryCode', $ip);
    }

    /**
     * Retourne le nom de la ville de l'utilisateur.
     * Parfois vide.
     *
     * @param   string|null $ip Pour forcer une IP donnée. Si non définie, l'IP de l'utilisateur sera utilisée
     *
     * @return  string
     */
    public static function getUserCity($ip = null)
    {
        return self::getInfo('city', $ip);
    }

    /**
     * Retourne un tableau associatif contenant la latitude et la longitude
     * de l'utilisateur, si disponibles.
     *
     * @param   string|null $ip Pour forcer une IP donnée. Si non définie, l'IP de l'utilisateur sera utilisée
     *
     * @return  array
     */
    public static function getUserLocation($ip = null)
    {
        $location = array(
            'latitude'  => static::getInfo('latitude', $ip),
            'longitude' => static::getInfo('longitude', $ip),
        );
        return $location;
    }

    /**
     * Retourne une info extraite de la base GeoIP concernant
     * l'IP de l'utilisateur.
     *
     * @param   string|null $key La clé. Si non définie, un array de toutes les infos est retourné
     * @param   string|null $ip  Pour forcer une IP donnée. Si non définie, l'IP de l'utilisateur sera utilisée
     *
     * @return  string|array
     */
    public static function getInfo($key = null, $ip = null)
    {
        $ip = static::getIp($ip);

        // Reset IP
        if (isset($_GET['reset_ip']) && self::canForceIp()) {
            static::setCache($ip, null);
            unset($_GET['reset_ip']);
        }

        // Cache ?
        $ipInfos = static::getCache($ip);
        if (!isset($ipInfos)) {
            $ipInfos = self::fetchIpInfo($ip);
        }

        if (empty($key)) {
            // Pas de clé demandée ? On retourne tout le tableau
            return $ipInfos;
        }
        if (!empty($ipInfos[$key])) {
            // On retourne une clé particulière
            return $ipInfos[$key];
        }
        // Cette clé n'existe pas
        return false;
    }

    /**
     * Retourne l'IP de l'utilisateur, en la forçant si besoin à partir du GET.
     *
     * @param string|null $ip Seulement si on veut forcer une IP via l'appel PHP. Laisser vide sinon
     *
     * @return string
     */
    public static function getIp($ip = null)
    {
        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        if (!empty($_GET['force_ip']) && self::canForceIp() && filter_var($_GET['force_ip'], FILTER_VALIDATE_IP)) {
            return $_GET['force_ip'];
        }
        if (!empty($_SESSION['force_ip']) && self::canForceIp() && filter_var($_SESSION['force_ip'], FILTER_VALIDATE_IP)) {
            return $_SESSION['force_ip'];
        }
        return \NC::remoteIp();
    }

    /**
     * Est-ce que l'utilisateur peut forcer l'IP via un paramètre GET ?
     *
     * @return boolean
     */
    protected static function canForceIp()
    {
        return (\Fuel::$env == \Fuel::DEVELOPMENT) || \NC::isIpIn();
    }

    /**
     * Récupère les infos sur l'IP fournie en paramètre et les stocke en session.
     *
     * @param $ip
     *
     * @return boolean
     */
    protected static function fetchIpInfo($ip)
    {
        if (!isset(static::$ipInfos[$ip])) {
            $url    = static::getGeoipUrl($ip);
            $xml    = simplexml_load_file($url);
            $ipInfo = self::$structure;
            if ($xml !== false && empty($xml->message)) {
                // On a un XML bien parsé et sans erreur (message)
                foreach ($xml as $key => $value) {
                    $ipInfo[$key] = (string) $value;
                }
            }
            static::$ipInfos[$ip] = $ipInfo;
            static::setCache($ip, $ipInfo);
        }
        return static::$ipInfos[$ip];
    }

    /**
     * URL du service
     *
     * @param $ip
     *
     * @return string url
     */
    protected static function getGeoipUrl($ip)
    {
        $config = \Config::load('novius_cloud::config', true);
        return str_replace('{{ip}}', $ip, $config['geoip']['url']);
    }

    protected static function getCache($ip)
    {
        if (!empty($_SESSION[self::SESSION_KEY][$ip])) {
            return $_SESSION[self::SESSION_KEY][$ip];
        }
        return null;
    }

    protected static function setCache($ip, $sessionInfo)
    {
        $_SESSION[self::SESSION_KEY][$ip] = $sessionInfo;
    }
}
