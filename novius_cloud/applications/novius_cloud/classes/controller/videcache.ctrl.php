<?php

namespace NC;

class Controller_Videcache extends \Nos\Controller
{
    public function action_index()
    {
        if (isset($_GET['vide_cache_fuelphp'])) {
            static::videCache('fuelphp');
        }
        if (isset($_GET['vide_cache_pages'])) {
            static::videCache('pages');
        }
        if (isset($_GET['vide_cache_all_sauf_media'])) {
            static::videCacheAll(false);
        }
        if (isset($_GET['vide_cache_media'])) {
            static::videCacheMediaOrData('media');
            static::videCacheMediaOrData('data');
        }
        if (isset($_GET['vide_cache_all'])) {
            static::videCacheAll(true);
        }

        return \View::forge('novius_cloud::videcache/liens');
    }

    static public function videCache($dir)
    {
        // traitement spécifique pour media
        if ($dir == '' || $dir == 'media') {
            dd('erreur');
        }

        static::videPathOuRecree(NOSROOT.'local/cache/'.$dir);
    }

    static protected function videCacheAll($vide_media_and_data = false)
    {
        $dir = NOSROOT.'local/cache/';
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                $path = $dir.$file;
                if (!in_array($file, array('.', '..')) && is_dir($path)) {
                    if (in_array($file, array('media', 'data'))) {
                        if (!$vide_media_and_data) {
                            continue;
                        }
                        static::videCacheMediaOrData($file);
                    } else {
                        static::videPathOuRecree($path);
                    }
                }
            }
            closedir($dh);
        }
    }

    static protected function videCacheMediaOrData($type)
    {
        if (!in_array($type, array('media', 'data'))) {
            dd("$type inconnu");
        }

        // on commence par vider le répertoire public/cache/$type contenant les liens symboliques vers local/cache/$type
        static::videPathOuRecree(NOSROOT.'public/cache/'.$type);

        // on vide le répertoire de cache $type proprement dit
        static::videPathOuRecree(NOSROOT.'local/cache/'.$type);
    }

    static protected function videPathOuRecree($path)
    {
        try {
            d('Vide '.$path, 0, date('H:i:s'));
            // on supprime récursivement le répertoire mais pas le répertoire lui même
            \File::delete_dir($path, true, false);
        } catch (\InvalidPathException $e) {
            d("le répertoire $path n'existait pas, on le recrée", 0, date('H:i:s'));
            mkdir($path);
        } catch (\Exception $e) {
            d($path, 0);
            dd($e, 0);
        }
    }
}
