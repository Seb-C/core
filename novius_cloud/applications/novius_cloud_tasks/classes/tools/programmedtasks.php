<?php

namespace NC\Tasks;

/**
 * Class Tools_Cronconfig
 * Permet de gérer la programmation des tâches sur ce site (cron)
 *
 * @package NC\Tasks
 */
class Tools_ProgrammedTasks
{
    /** @var  array Liste des tâches programmées sur ce site, avec leur config */
    protected static $programmedTasksConfig;

    /** @var null   Liste des tâches présentes sur le site (programmées ou non) */
    protected static $allSiteTasks = null;

    /**
     * Toute la config des tasks est dans le fichier "novius_cloud_tasks::def"
     */
    static public function _init()
    {
        static::$programmedTasksConfig = \Config::load('novius_cloud_tasks::def', true);
    }

    /**
     * Programmer une nouvelle tâche
     *
     * @param $taskIdentifier   string  Identifier de la tâche (exemple : "local::test")
     * @param $config           array   Configuration de la tâche (minutes, heures...)
     *
     * @return bool
     */
    static public function addProg($taskIdentifier, $config)
    {
        $progID    = uniqid();
        $allSiteTasks = static::getAllSiteTasks();
        $className = $allSiteTasks[$taskIdentifier]['className'];

        $programmedTasksConfig = static::getConfig();

        $programmedTasksConfig[$progID] = array_merge(array(
            'progID'        => $progID,
            'className'     => $className,
            'minutes'       => '0',
            'heures'        => '*',
            'jours_semaine' => '*',
            'jours_mois'    => '*',
            'env'           => array(
                'SERVER_NAME' => Manager::getTaskServerName(),
            ),
        ), $config);

        if (!static::saveConfig($programmedTasksConfig, true)) {
            return false;
        }

        return $programmedTasksConfig[$progID];
    }


    /**
     * Programmer une nouvelle tâche
     *
     * @param $progID   int     Identifiant de la programmation
     * @param $config   array   Configuration de la tâche (minutes, heures...)
     *
     * @return bool
     */
    static public function updateProg($progID, $config)
    {
        $programmedTasksConfig = static::getConfig();
        $programmedTasksConfig[$progID] = array_merge($programmedTasksConfig[$progID], $config);

        // On ecrase toujours le SERVER_NAME pour être bien certain qu'il est tjrs à jour.
        $programmedTasksConfig[$progID]['env']['SERVER_NAME'] = Manager::getTaskServerName();

        if (!static::saveConfig($programmedTasksConfig, true)) {
            return false;
        }

        return $programmedTasksConfig[$progID];
    }


    /**
     * Supprimer une programmation d'une
     *
     * @param $progID int     Identifiant de la programmation
     *
     * @return bool
     */
    static public function deleteProg($progID)
    {
        $programmedTasksConfig = static::getConfig();
        if (!empty($programmedTasksConfig[$progID])) {
            unset($programmedTasksConfig[$progID]);
        }

        if (!static::saveConfig($programmedTasksConfig, true)) {
            return false;
        }

        return true;
    }

    /**
     * Récupération des infos d'une tâche à partir d'un progID
     *
     * @param $progID
     *
     * @return bool|array
     */
    static public function getTask($progID)
    {
        $programmedTasks = static::getTasks();
        if (empty($programmedTasks[$progID])) {
            return false;
        }
        return $programmedTasks[$progID];
    }

    /**
     * Retourne un tableau des tâches programmés sur ce site
     *
     * @return array        Liste des tâches programmés, avec leurs infos (config, className...)
     *
     * @throws \Exception
     */
    static public function getTasks()
    {
        // Lecture du fichier sérialisé
        $programmedTasksConfig = static::getConfig();

        // Association avec la liste globale des tasks
        $programmedTasks = array();

        $allSiteTasks   = static::getAllSiteTasks();
        $classToTask = \Arr::assoc_to_keyval($allSiteTasks, 'className', 'identifier');
        foreach ($programmedTasksConfig as $progID => $taskInfos) {

            // Compatibilité ancien format (la classe était la clé, impossible de programmer plusieurs fois une tâche)
            if (empty($taskInfos['className'])) {
                $taskInfos['className'] = $progID;
                $taskInfos['progID']    = $progID;
            }

            // Cette classe existe bien ?
            $taskIdentifier = static::classToTaskIdentifier($taskInfos['className'], '');
            if (empty($taskIdentifier) || empty($allSiteTasks[$taskIdentifier])) {
                continue;
            }
            $programmedTasks[$progID] = $allSiteTasks[$taskIdentifier] + array(
                    'progID' => $progID,
                    'prog'   => $taskInfos
                );
        }

        return $programmedTasks;
    }

    /**
     * Permet d'obtenir, de manière brute, le contenu du fichier serializé contenant la configuration des crons.
     *
     * @return array    La configuration brute des tâches sur le site.
     *
     * @throws \Exception
     */
    static protected function getConfig()
    {
        if (empty(static::$programmedTasksConfig['serialized_filepath'])) {
            throw new \Exception('[novius_cloud_tasks] Config def.serialized_filepath manquante !');
        }
        if (!is_file(static::$programmedTasksConfig['serialized_filepath'])) {
            return array();
        }

        $programmedTasksConfig = unserialize(file_get_contents(static::$programmedTasksConfig['serialized_filepath']));
        if (!is_array($programmedTasksConfig)) {
            return array();
        }

        return $programmedTasksConfig;
    }

    /**
     * Sauvegarde brute de la config dans le fichier sérializé.
     *
     * @param   array   $programmedTasksConfig      Config du serialié
     * @param   bool    $autoCleanFile              On doit automatiquement appliquer static::cleanProgsConfig() ?
     *
     * @throws \Exception
     *
     * @return int
     */
    static protected function saveConfig($programmedTasksConfig, $autoCleanFile = false)
    {
        if ($autoCleanFile) {
            $programmedTasksConfig = static::cleanProgsConfig($programmedTasksConfig);
        }
        if (empty(static::$programmedTasksConfig['serialized_filepath'])) {
            throw new \Exception('[novius_cloud_tasks] Config def.serialized_filepath manquante !');
        }
        return file_put_contents(static::$programmedTasksConfig['serialized_filepath'], serialize($programmedTasksConfig));
    }

    /**
     * Nettoyage du fichier serializé, pour supprimer les tâches qui n'existent plus, etc
     *
     * @param   array   $programmedTasksConfig      Config du serialié
     *
     * @return  array   Config du serialié
     */
    static protected function cleanProgsConfig($programmedTasksConfig)
    {
        foreach ($programmedTasksConfig as $progID => $taskInfos) {

            // Compatibilité ancien format (la classe était la clé, impossible de programmer plusieurs fois une tâche)
            if (empty($taskInfos['className'])) {
                $taskInfos['className'] = $progID;
                $taskInfos['progID']    = $progID;
            }

            // Cette classe n'existe pas ? On supprime.
            $taskIdentifier = static::classToTaskIdentifier($taskInfos['className'], '');
            if (empty($taskIdentifier)) {
                unset($programmedTasksConfig[$progID]);
            }
        }
        return $programmedTasksConfig;
    }

    /**
     * Retourne l'ensemble des tâches disponibles sur le site et ses applications
     *
     * @return array    Liste des tasks
     */
    static public function getAllSiteTasks()
    {
        if (isset(static::$allSiteTasks)) {
            return static::$allSiteTasks;
        }
        $tasksDir   = 'classes/task/';

        // Chemin des applications installées puis concaténation des chemins vers les tasks
        $applicationsPaths = array(realpath(NOSPATH) => 'nos');
        $tasksPaths = array();
        foreach (array_keys(\Nos\Config_Data::get('app_installed')) as $module) {
            $path = \Module::exists($module);
            if (!empty($path)) {
                $applicationsPaths[realpath($path)] = $module;
                if (is_dir($path.$tasksDir)) {
                    $paths = \File::read_dir($path.$tasksDir);
                    $paths = static::flattenPaths($paths);
                    array_walk($paths, function(&$p) use($path, $tasksDir) {
                        $p = array($path.$tasksDir => $p);
                    });
                    $tasksPaths = array_merge($tasksPaths, $paths);
                }
            }
        }

        // Namespace des applications en question
        $applicationNamespaces          = \Nos\Config_Data::load('app_namespaces', true);
        $applicationNamespaces['local'] = 'Local';

        // Affichage propre
        $tasks = array();
        foreach ($tasksPaths as $taskInfo) {
            $taskAppDir = key($taskInfo);
            $taskPath = reset($taskInfo);
            $taskErrors = array();

            // Les Tasks de FuelPHP ne nous intéressent pas
            if (\Str::starts_with($taskAppDir, COREPATH)) {
                continue;
            }

            // Nom de l'application ?
            $applicationPath = realpath(substr($taskAppDir, 0, -strlen($tasksDir)));

            $applicationName = 'local';
            if (isset($applicationsPaths[$applicationPath])) {
                $applicationName = $applicationsPaths[$applicationPath];
            }

            // Infos sur la Task, et gestion des erreurs
            $taskIdentifier = $applicationName.'::'.$taskPath;

            /** @var $className \NC\Tasks\Task */
            $namespace  = $applicationNamespaces[$applicationName];
            $fileName = dirname($taskPath).'/'.pathinfo($taskAppDir.$taskPath, PATHINFO_FILENAME);
            $className  = '\\'.$namespace.'\\'.\Inflector::classify('task_'.str_replace('/', '_', trim($fileName, '/.')), false);
            $taskConfig = array();

            if (!class_exists($className)) {
                $sitedir      = basename(NOSROOT);
                $partialpath  = substr($taskPath, strpos($taskPath, $sitedir));
                $taskErrors[] = '- Impossible de trouver la classe '.$className.' dans le fichier '.$partialpath.'.';
            } elseif (!is_subclass_of($className, '\\NC\\Tasks\\Task')) {
                $taskErrors[] = '- La classe '.$className.' doit étendre \Task pour être une task valide.';
            } elseif (!method_exists($className, 'run')) {
                $taskErrors[] = '- La méthode publique obligatoire '.$className.'->run() est introuvable.';
            } elseif (!method_exists($className, 'config')) {
                $taskErrors[] = '- La méthode '.$className.'::config() est introuvable. Problème !';
            } else {
                $taskConfig = $className::config();
            }

            $tasks[$taskIdentifier] = array(
                'identifier' => $taskIdentifier,
                'className'  => $className,
                'config'     => $taskConfig,
                'errors'     => $taskErrors,
            );
        }

        static::$allSiteTasks = $tasks;
        return $tasks;
    }

    /**
     * "Mise à plat" d'un tableau mutlidimensionnel contenant les paths obtenu avec la méthode \File::read_dir()
     *
     * @param $paths
     * @param array $parents
     * @return array
     */
    public static function flattenPaths($paths, $parents = array()) {
        $flattened_paths = array();
        foreach ($paths as $dir => $path) {
            if (is_array($path)) {
                $flattened_paths = \Arr::merge($flattened_paths, static::flattenPaths($path, \Arr::merge($parents, array(rtrim($dir, DS)))));
            } else {
                $flattened_paths[] = ltrim(implode(DS, $parents).DS.$path, DS);
            }
        }
        return $flattened_paths;
    }

    /**
     * Identifier d'une tâche à partir de sa classe
     *
     * @param   string          $className  Class de la Task
     * @param   string|null     $default    Valeur par défaut si on ne trouve pas d'identifier
     *
     * @return  string      Identifier de la task (ex : local::test) ou $className si on ne trouve pas l'identifier
     */
    static public function classToTaskIdentifier($className, $default = null)
    {
        static $classToTask = null;
        if (!isset($classToTask)) {
            $allSiteTasks = static::getAllSiteTasks();
            $classToTask  = \Arr::assoc_to_keyval($allSiteTasks, 'className', 'identifier');
        }

        $className = '\\'.ltrim($className, '\\');
        if (!isset($default)) {
            $default = $className;
        }
        return !empty($classToTask[$className]) ? $classToTask[$className] : $default;
    }
}
