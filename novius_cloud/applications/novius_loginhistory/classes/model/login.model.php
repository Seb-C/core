<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2011 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

namespace Novius\Loginhistory;

class Model_Login extends \Nos\Orm\Model
{
    protected static $_table_name = 'novius_login_history';
    protected static $_primary_key = array('logi_id');

    protected static $_properties = array(
        'logi_id' => array(
            'data_type' => 'int unsigned',
            'null' => false,
            'default' => null,
        ),
        'logi_created_at' => array(
            'data_type' => 'timestamp',
            'null' => false,
        ),
        'logi_driver' => array(
            'data_type' => 'varchar',
            'null' => true,
            'default' => null,
            'convert_empty_to_null' => true,
        ),
        'logi_login' => array(
            'data_type' => 'varchar',
            'null' => false,
        ),
        'logi_user_id' => array(
            'data_type' => 'int unsigned',
            'null' => true,
            'default' => null,
        ),
        'logi_action' => array(
            'data_type' => 'varchar',
            'null' => false,
            'default' => null,
        ),
        'logi_method' => array(
            'data_type' => 'varchar',
            'null' => true,
            'default' => null,
            'convert_empty_to_null' => true,
        ),
        'logi_state' => array(
            'data_type' => 'varchar',
            'null' => true,
            'default' => null,
            'convert_empty_to_null' => true,
        ),
        'logi_ip' => array(
            'data_type' => 'varchar',
            'null' => true,
            'default' => null,
            'convert_empty_to_null' => true,
        ),
        'logi_user_agent' => array(
            'data_type' => 'varchar',
            'null' => true,
            'default' => null,
            'convert_empty_to_null' => true,
        ),
    );

    protected static $_observers = array(
        'Orm\Observer_CreatedAt' => array(
            'mysql_timestamp' => true,
            'property'=>'logi_created_at'
        ),
    );

    /**
     * Add a login attempt
     *
     * @param $action
     * @param array $infos
     * @return mixed
     */
    public static function add($action, array $infos = array())
    {
        return Model_Login::forge()->set(array(
            'logi_login'        => \Arr::get($infos, 'login'),
            'logi_user_id'      => \Arr::get($infos, 'user_id'),
            'logi_ip'           => \Arr::get($infos, 'ip', static::getIp()),
            'logi_user_agent'   => \Arr::get($infos, 'user_agent', \Input::user_agent()),
            'logi_driver'       => \Arr::get($infos, 'driver'),
            // Login action (eg. login, logout, lostpassword, updatepassword...)
            'logi_action'       => $action,
            // Method (eg. form, cookie, switch...)
            'logi_method'       => \Arr::get($infos, 'method'),
            // Action's state (success/false)
            'logi_state'        => \Arr::get($infos, 'state'),
        ))->save();
    }

    /**
     * Get the user IP
     *
     * @return mixed
     */
    protected static function getIp() {
        $config = \Config::load('novius_loginhistory::config', true);
        return \Fuel::value(\Arr::get($config, 'callback_ip'));
    }
}
