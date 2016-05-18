<?php

namespace NC;


class Controller_Migrations extends \Nos\Controller
{
    public function action_index()
    {
        try {
            $migrations = \Nos\Application::migrateAll();
        } catch(\Exception $e) {
            d('Erreur lors de la migration : '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine(), 0);
            d('Backtrace de la migration :', 0);
            \Debug::dump($e->getTrace());

            // On force en DEV pour avoir un affichage de l'erreur, via le catch de hmvc()
            \Fuel::$env = \Fuel::DEVELOPMENT;
            d('Erreur retournée par hmvc() :', 0);
            throw $e;
        }

        return \View::forge('novius_cloud::migrations/list', array(
            'migrations' => $migrations
        ));
    }

    public static function isDirty()
    {
        $applications = \Nos\Application::search_all();
        foreach ($applications as $app) {
            if ($app->is_installed()) {
                if ($app->is_dirty()) {
                    return true;
                }
            }
        }

        if (\Nos\Application::areNativeApplicationsDirty()) {
            return true;
        }

        if (\Nos\Application::forge('local')->is_dirty()) {
            return true;
        }

        return false;
    }
}