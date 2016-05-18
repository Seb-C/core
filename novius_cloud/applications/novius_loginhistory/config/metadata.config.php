<?php
/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2011 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

$version = \Config::load('nos::version', true);
if (\Str::starts_with(\Arr::get($version, 'fullname'), 'Chiba')) {
    $extends = array(
        'application' => 'local',
        'extend_configuration' => false,
    );
} else {
    $extends = array(
        'local',
    );
}
return array(
    'name'    => 'Login history',
    'version' => 'chiba.2.4.',
    'i18n_file' => 'novius_loginhistory::metadata',
    'permission' => array(),
    'provider' => array(
        'name' => 'Novius OS',
    ),
    'extends' => $extends,
    'namespace' => 'Novius\Loginhistory',
);
