<?php

namespace NC\Tasks;

class Model_Tasklaunch extends \Nos\Orm\Model
{

    protected static $_table_name = 'nc_taskslaunch';
    protected static $_primary_key = array('tala_id');

    protected static $_properties = array(
        'tala_id'          => array(
            'default'   => null,
            'data_type' => 'int unsigned',
            'null'      => false,
        ),
        'tala_token'       => array(
            'data_type' => 'varchar',
            'null'      => false,
        ),
        'tala_task'        => array(
            'data_type' => 'varchar',
            'null'      => false,
        ),
        'tala_pid'         => array(
            'data_type' => 'int unsigned',
            'null'      => false,
        ),
        'tala_status'      => array(
            'data_type' => 'enum',
            'null'      => false,
            'options'   => array(
                'RUNNING', 'SUCCESS', 'NOTHING_TO_DO', 'PHP_FATAL', 'USER_ERROR', 'TASK_ERROR', 'SKIPPED'
            ),
            'default'   => 'RUNNING',
        ),
        'tala_launch_from' => array(
            'data_type' => 'varchar',
            'null'      => false,
            'default'   => '',
        ),
        'tala_exclusive'   => array(
            'data_type' => 'tinyint',
            'null'      => false,
            'default'   => '0',
        ),
        'tala_message'     => array(
            'data_type' => 'varchar',
            'null'      => false,
            'default'   => '',
        ),
        'tala_no_error'    => array(
            'data_type' => 'tinyint',
            'null'      => false,
            'default'   => 1,
        ),
        'tala_no_output'   => array(
            'data_type' => 'tinyint',
            'null'      => false,
            'default'   => 1,
        ),
        'tala_infos'       => array(
            'data_type' => 'serialize',
            'null'      => false,
            'default'   => '',
        ),
        'tala_created_at'  => array(
            'data_type' => 'datetime',
            'null'      => true,
        ),
        'tala_updated_at'  => array(
            'data_type' => 'datetime',
            'null'      => true,
        ),
    );

    protected static $_observers = array(
        'Orm\Observer_CreatedAt' => array(
            'mysql_timestamp' => true,
            'property'        => 'tala_created_at'
        ),
        'Orm\Observer_UpdatedAt' => array(
            'mysql_timestamp' => true,
            'property'        => 'tala_updated_at'
        ),
        'Orm\Observer_Typing',
    );

    protected static $_has_one = array();
    protected static $_many_many = array();
    protected static $_has_many = array();
    protected static $_belongs_to = array();
    protected static $_behaviours = array();

    /**
     * Est-ce que cette tâche est déjà en train de tourner ?
     *
     * @param $taskName string  Nom de la classe (exemple \Local\Task_Test)
     *
     * @return bool
     */
    public static function isAlreadyRunning($taskName)
    {
        $taskslaunchs = static::find('all', array(
            'where' => array(
                'tala_task'   => $taskName,
                'tala_status' => 'RUNNING',
            ),
        ));

        $isRunning = false;
        foreach ($taskslaunchs as $launch) {
            if (is_dir('/proc/'.$launch->tala_pid)) {
                $isRunning = true;
            } else {
                $launch->tala_status = 'TASK_ERROR';
                $launch->save();
            }
        }

        return $isRunning;
    }

    /**
     * Retrouver un lancement de Task à partir de son token unique
     *
     * @param $token    string
     *
     * @return Model_Tasklaunch|null
     */
    public static function findByToken($token)
    {
        return static::find('first', array(
            'where' => array(
                'tala_token' => $token
            )
        ));
    }

    /**
     * On supprime de la table nc_tasklaunch les lancement de plus d'un an
     *
     * @return mixed
     */
    public static function purgeTasksLaunch()
    {
        $sql = 'DELETE FROM '.static::$_table_name.' WHERE tala_created_at < NOW() - INTERVAL 1 YEAR';
        return \DB::query($sql)->execute();
    }

    /**
     * On supprime les logs (fichiers du repertoires self::getLogsDir()) qui datent de plus de 3 mois
     *
     * @return bool
     */
    public static function purgeTasksLaunchLogs()
    {
        $time_3_months_before = strtotime('-3 months');

        try {
            $dir = new \DirectoryIterator(static::getLogsDir());
            foreach ($dir as $file) {
                // Suppression des fichiers vieux d'il y a plus de 3 mois
                if ($file->isFile() && $file->getCTime() < $time_3_months_before) {
                    @unlink($file->getPathname());
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return true;
    }

    /**
     * Repertoire de logs
     *
     * @return string
     */
    public static function getLogsDir()
    {
        $config = Manager::config();
        return NOSROOT.$config['logdir'];
    }
}