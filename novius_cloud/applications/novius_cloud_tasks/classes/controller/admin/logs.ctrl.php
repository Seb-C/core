<?php

namespace NC\Tasks;

class Controller_Admin_Logs extends \Nos\Controller_Admin_Appdesk
{
    public $bypass = true; // Pas de permissions "Novius OS"

    /**
     * Gestion des droits sur cette classe : application réservée aux superadmin
     */
    static public function _init()
    {
        if (!\NC::isSuperAdmin()) {
            \Response::json(array(
                'error' => 'Interface réservée aux superadmins seulement.',
            ));
        }
    }

    /**
     * Affichage de l'interface des logs d'une execution
     *
     * @param $tasklaunchID     int     ID du lancement de la tâche
     *
     * @return \Fuel\Core\View
     */
    public function action_openlog($tasklaunchID)
    {
        if (empty($tasklaunchID)) {
            \Response::json(array(
                'error' => 'ID du log manquant',
            ));
        }

        // Récupération du lancement de la tâche, pour ensuite afficher ses logs
        $taskLaunch = Model_Tasklaunch::find($tasklaunchID);
        if (empty($taskLaunch)) {
            \Response::json(array(
                'error' => 'Impossible de se trouver ce log',
            ));
        }

        $taskIdentifier = Tools_ProgrammedTasks::classToTaskIdentifier($taskLaunch->tala_task);

        return \View::forge('novius_cloud_tasks::admin/logs/main', array(
            'taskIdentifier' => $taskIdentifier,
            'taskLaunch'     => $taskLaunch,
        ), false);
    }

    /**
     * Récupération du contenu des fichiers de log (live ou gzip) pour une execution de tâche donnée.
     *
     * @param   $tasklaunchID       int     ID du lancement de la tâche
     *
     * @return  string              json
     */
    public function action_ajaxLog($tasklaunchID)
    {
        if (empty($tasklaunchID)) {
            exit('<p>ID manquant</p>');
        }

        $json = array();

        // La tâche existe ?
        $taskLaunch = Model_Tasklaunch::find($tasklaunchID);
        if (!empty($taskLaunch)) {
            $json['status'] = $taskLaunch->tala_status;
        }

        // Lecture des fichiers de log
        $files = array('stdout', 'stderr');

        $logsdir      = \NC\Tasks\Model_Tasklaunch::getLogsDir();
        $json['logs'] = array();
        foreach ($files as $filename) {
            $infos = array(
                'content' => ''
            );

            // Lecture du fichier de log, en fonction du status de la tâche.
            switch ($taskLaunch->tala_status) {

                // Version "live". On lit le fichier au fur et à mesure.
                case 'RUNNING' :
                    $seek     = \Input::get('seek_'.$filename, 0);
                    $filepath = $logsdir.$tasklaunchID.'_'.$filename.'.log.live';

                    $infos['nextseek'] = $seek;
                    if (is_file($filepath)) {
                        $infos['found']    = 1;
                        $infos['mode']     = 'live';
                        $infos['nextseek'] = filesize($filepath);
                        if ($seek < $infos['nextseek']) {
                            $fp = fopen($filepath, 'r');
                            if (!empty($fp) && fseek($fp, $seek) >= 0) {
                                while (!feof($fp)) {
                                    $infos['content'] .= fread($fp, 4096);
                                }
                                $infos['content'] = nl2br($infos['content']);
                            }
                        }
                    }
                    break;

                // Version gzippée
                default :
                    $filepath = $logsdir.$tasklaunchID.'_'.$filename.'.log.gz';
                    if (is_file($filepath)) {
                        $infos['content'] = nl2br(file_get_contents('compress.zlib://'.$filepath));

                        // Un problème d'encodage ? json_encode retournera "null" (JSON_ERROR_UTF8)
                        // donc on essaye de les gérer.
                        if (!empty($infos['content'])) {
                            if (json_encode($infos['content']) === 'null') {
                                $infos['content'] = utf8_encode($infos['content']);
                                $infos['content'] = d('[[Caractères non-utf8 détéctés. utf8_encode() fait automatiquement]]<br /><br />', array('print' => false)).$infos['content'];
                            }
                        }

                        $infos['found']   = 1;
                        $infos['mode']    = 'gzip';
                    } else {
                        $infos['content'] = '<p class="noresult">Aucun fichier de log <em>'.$filename.'</em></p>';
                        $infos['found']   = 0;
                        $infos['mode']    = 'gzip';
                    }
                    break;
            }

            $json['logs'][$filename] = $infos;
        }

        \Response::json($json);
    }
}