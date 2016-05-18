<?php

namespace NC\Tasks;

class Controller_Admin_Config extends \Nos\Controller_Admin_Application
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
     * Affichage de la page principale, avec la liste des programmations
     *
     * @return \Fuel\Core\View
     */
    public function action_index()
    {
        return \View::forge('novius_cloud_tasks::admin/config/main', array(
            'allSiteTasks'    => Tools_ProgrammedTasks::getAllSiteTasks(),
            'programmedTasks' => Tools_ProgrammedTasks::getTasks(),
        ), false);
    }

    /**
     * Lancement manuel asynchrone d'une tâche
     *
     * @return string json
     */
    public function action_ajaxLaunch()
    {
        $taskIdentifier = static::getInputTaskIdentifier();

        // lancement de la tâche
        /** @var $className \NC\Tasks\Task */
        $allSiteTasks = Tools_ProgrammedTasks::getAllSiteTasks();
        $className    = $allSiteTasks[$taskIdentifier]['className'];
        $token        = $className::launchAsync();

        \Response::json(array(
            'identifier' => $taskIdentifier,
            'token'      => $token,
        ));
    }

    /**
     * Affichage du formulaire de plannification/modification d'une programmation de tâche
     *
     * @return \Fuel\Core\View
     */
    public function action_ajaxProgForm()
    {
        $progID         = \Input::get('progID', 0);
        $taskIdentifier = static::getInputTaskIdentifier();

        // On va chercher des infos sur la tâches
        // Si jamais elle n'est pas programmée, on aura forcément moins d'infos
        $programmedTask = null;
        if (!empty($progID)) {
            $programmedTask = Tools_ProgrammedTasks::getTask($progID);
        }
        if (empty($programmedTask)) {
            $allSiteTasks   = Tools_ProgrammedTasks::getAllSiteTasks();
            $programmedTask = $allSiteTasks[$taskIdentifier];
        }

        return \View::forge('novius_cloud_tasks::admin/config/sub/panel.progform', array(
            'taskIdentifier' => $taskIdentifier,
            'task'           => $programmedTask,
        ));
    }

    /**
     * Sauvegarde du formulaire de plannification/modification d'une programmation de tâche
     *
     * @return string json
     */
    public function action_ajaxProgSave()
    {
        $progID         = \Input::get('progID', 0);
        $taskIdentifier = static::getInputTaskIdentifier();

        // Vérification du post et affichage des éventuelles erreurs
        $taskconf = array();
        $checkpost = array(
            'heures'        => array(
                'errMsg' => __('Merci de préciser une ou plusieurs heures'),
                'count'  => 24,
            ),
            'minutes'       => array(
                'errMsg' => __('Merci de préciser une ou plusieurs minutes'),
                'count'  => 12,
            ),
            'jours_semaine' => array(
                'errMsg' => __('Merci de préciser un ou plusieurs jour de semaine'),
                'count'  => 7,
            ),
            'jours_mois'    => array(
                'errMsg' => __('Merci de préciser un ou plusieurs jour de mois'),
                'count'  => 31,
            )
        );
        foreach ($checkpost as $key => $infos) {
            if (empty($_POST[$key])) {
                \Response::json(array(
                    'error' => $infos['errMsg']
                ));
            }
            if (count($_POST[$key]) == $infos['count']) {
                $taskconf[$key] = '*';
            } else {
                $taskconf[$key] = implode(',', $_POST[$key]);
            }
        }

        // Pas d'erreur, donc on ajoute la tâche
        if (!empty($progID)) {
            $return = Tools_ProgrammedTasks::updateProg($progID, $taskconf);
        } else {
            $return = Tools_ProgrammedTasks::addProg($taskIdentifier, $taskconf);
        }

        \Response::json($return);
    }

    /**
     * Suppression d'une programmation de tâche
     *
     * @return string json
     */
    public function action_ajaxProgDelete()
    {
        $progID = static::getInput('progID');
        $return = Tools_ProgrammedTasks::deleteProg($progID);
        \Response::json(array('delete' => $return));
    }

    /**
     * Informations sur une execution de tâche à partir de son token
     *
     * @param $token
     *
     * @return string json
     */
    public function action_ajaxTaskLaunchFromToken($token)
    {
        $taskLaunch = Model_Tasklaunch::findByToken($token);
        if (empty($taskLaunch)) {
            \Response::json(array('status' => 404));
        }
        \Response::json(array(
            'tala_id'          => $taskLaunch->tala_id,
            'tala_token'       => $taskLaunch->tala_token,
            'tala_task'        => $taskLaunch->tala_task,
            'tala_status'      => $taskLaunch->tala_status,
            'tala_launch_from' => $taskLaunch->tala_launch_from,
            'tala_message'     => $taskLaunch->tala_message,
        ));
    }

    /**
     * Récupère une taskIdentifier valide via \Input::get(). Si jamais cette clé est empty,
     * retourne un message d'erreur au format JSON.
     *
     * @return string
     */
    protected static function getInputTaskIdentifier()
    {
        $taskIdentifier = static::getInput('taskIdentifier');

        $allSiteTasks = Tools_ProgrammedTasks::getAllSiteTasks();
        if (empty($allSiteTasks[$taskIdentifier])) {
            \Response::json(array(
                'error' => 'Task "'.$taskIdentifier.'" introuvable'
            ));
        }

        return $taskIdentifier;
    }

    /**
     * Récupère une valeur via \Input::get(). Si jamais cette clé est empty, retourne un
     * message d'erreur au format JSON.
     *
     * @param $key  String  La cle du GET
     *
     * @return string
     */
    protected static function getInput($key)
    {
        $value = \Input::get($key);
        if (empty($value)) {
            \Response::json(array(
                'error' => 'Param '.$key.' manquant'
            ));
        }
        return $value;
    }
}