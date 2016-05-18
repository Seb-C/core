<?php

namespace NC\Tasks;

abstract class Task extends \Nos\Task
{
    /**
     * Config de base, qui sera surchargée par :
     * - un éventuelle fichier de config associée à la classe
     * - une declaration de "static $config" dans la Task
     *
     * La config finale de la Task sera un array_merge($_config_default, $config, $fichierConfig)
     *
     * @var array Config de la Task
     */
    protected static $config = array();
    private static $_config_default = array(
        'exclusive' => true,
        'mail_from' => '',
        'mail_tos'  => 'dossierpublic@novius.fr',
        'success'   => 'mail',
        'failure'   => 'mail',
        'skipped'   => '',
    );

    // Pour sauvegarder la config mergée de chaque task
    private static $_config_nctasks = array();

    /**
     * Permet de lancer la tâche de manière asynchrone
     *
     * @param int|null $specificID      Pour passer un identiant spécifique, qui sera accessible dans la tâche (via getSpecificID())
     *
     * @return string   L'ID unique (alphanumeric) du l'execution
     */
    public static function launchAsync($specificID = null)
    {
        $env = array();
        if (!empty($specificID)) {
            $env['task_specificid'] = (int) $specificID; // on veut du chiffre, pour la ligne de commande !
        }

        return \NC\Tasks\Manager::launch(get_called_class(), $env);
    }

    /**
     * Récupération du token unique de lancement, au niveau de la tâche.
     * Pour rappel, ce token est retourné par la méthode launchAsync() au déclenchement de la tâche. Cela permet donc à la
     * tâche de sauvegarder des informations liées à son execution dans une autre table/fichier, pour ensuite permettre de
     * les récupérer facilement.
     */
    protected function getToken()
    {
        return !empty($_SERVER['TOKEN']) ? $_SERVER['TOKEN'] : null;
    }

    /**
     * Récupération de l'ID spécifique. C'est un ID qui a été passé en paramètre lors d'un déclenchement asynchrone (donc via
     * launchAsync, cela ne marche pas en mode "cron"). On peut ensuite le récupérer dans la tâche : c'est pratique pour savoir
     * quel fichier traiter, quelle image redimentionner, etc...
     * Si besoin d'infos supplémentaire, à vous de les sauvegarder de votre coté (dans une table par exemple). Cet ID étant transféré
     * via une execution en ligne de commande, c'est en effet délicat de passer beaucoup d'infos.
     */
    protected function getSpecificID()
    {
        return !empty($_SERVER['TASK_SPECIFICID']) ? $_SERVER['TASK_SPECIFICID'] : null;
    }

    /**
     * Méthode automatiquement appelée, une fois l'execution de la tâche terminée
     * Cette méthode ne doit pas être appelée ailleurs
     *
     * @param $execStatus
     * @param $trace
     *
     * @return bool
     */
    public static function afterRun($execStatus, $trace)
    {
        $params = array(
            'taskClass'  => get_called_class(),
            'execStatus' => $execStatus,
            'trace'      => $trace,
            'config'     => static::config(),
        );

        \Event::trigger('novius_cloud.tasks.afterRun', array($params));

        foreach (explode(',', $params['config'][$execStatus]) as $todo) {
            $action = 'afterRun'.ucfirst($todo);
            if (!empty($todo) && is_callable('static::'.$action)) {
                static::$action($params);
            }
        }
        return true;
    }

    /**
     * Envoi d'un email
     *
     * @param $params array Liste des parametres de la task (voir afterRun() pour plus de détails)
     */
    protected static function afterRunMail($params)
    {
        $subject = '[TASK]['.basename(NOSROOT).']['.$params['execStatus'].'] '.$params['taskClass'];
        $content = str_replace("\n", '<br />', $params['trace']);

        if ($content) {
            $content .= "\n\n";
        }
        $content .= ob_get_contents();

        $mail = \Email::forge();
        if (!empty($params['config']['mail_from'])) {
            $mail->from($params['config']['mail_from']);
        }
        $mail->subject($subject);
        $mail->html_body($content);

        $tos = $params['config']['mail_tos'];
        if (!is_array($tos)) {
            $tos = preg_split('`[,|;]`', $tos);
        }
        $mail->to($tos);

        try {
            $mail->send();
        } catch (\Exception $e) {
            echo 'ERREUR MAIL'.$e->getMessage();
        }
    }

    /**
     * A appeler pour indiquer que tout s'est bien passé.
     * Appelé automatiquement par cronlaunch, qui suppose que tout s'est bien passé par défaut.
     * Fait un exit
     *
     * @param string $message
     */
    public function success($message = '')
    {
        $this->exitTask(array(
            'type'    => 'success',
            'message' => $message,
        ));
    }

    /**
     * A appeler pour indiquer qu'il n'y avait rien à faire.
     * Fait un exit
     *
     * @param string $message
     */
    public function nothingToDo($message = '')
    {
        $this->exitTask(array(
            'type'    => 'nothing_to_do',
            'message' => $message,
        ));
    }

    /**
     * A appeler pour indiquer un échec lors de l'éxécution.
     * Fait un exit
     *
     * @param string $message
     */
    final public function error($message = '')
    {
        $this->exitTask(array(
            'type'    => 'failure',
            'message' => $message,
        ));
    }

    /**
     * Privé. Sort de la task (et renvoit l'usage mémoire à l'appelant).
     * Fait un exit
     *
     * @param array $array
     */
    private function exitTask(array $array)
    {
        exit(md5('%%%').serialize(array_merge($array, array(
                'memory_usage'      => memory_get_usage(true),
                'memory_peak_usage' => memory_get_peak_usage(true),
            ))));
    }

    /**
     * Lecture de la configuration de la tâche courante
     * @return array
     */
    public static function config()
    {
        $class = get_called_class();
        static::loadConfig();
        if (array_key_exists($class, self::$_config_nctasks)) {
            return self::$_config_nctasks[$class];
        }
        return array();
    }

    /**
     * Chargement de la config associée. Cette config est mergée avec $config
     */
    private static function loadConfig()
    {
        static $configLoaded = false;
        if ($configLoaded) {
            return false;
        }

        $class = get_called_class();
        $configLoaded = true;
        list($application, $relative_path) = \Config::configFile(str_replace('Tasks\\', 'Tasks_', get_called_class()));
        self::$_config_nctasks[$class] = array_merge(self::$_config_default, static::$config, \Config::loadConfiguration($application, $relative_path));
    }
}