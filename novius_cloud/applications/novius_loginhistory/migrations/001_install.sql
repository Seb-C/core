/**
 * NOVIUS OS - Web OS for digital communication
 *
 * @copyright  2011 Novius
 * @license    GNU Affero General Public License v3 or (at your option) any later version
 *             http://www.gnu.org/licenses/agpl-3.0.html
 * @link http://www.novius-os.org
 */

CREATE TABLE IF NOT EXISTS `novius_login_history` (
  `logi_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `logi_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logi_login` varchar(255) DEFAULT NULL,
  `logi_user_id` varchar(100) DEFAULT NULL,
  `logi_driver` varchar(255) DEFAULT NULL,
  `logi_action` varchar(50) NOT NULL,
  `logi_method` varchar(50) DEFAULT NULL,
  `logi_state` varchar(50) DEFAULT NULL,
  `logi_ip` varchar(45) DEFAULT NULL,
  `logi_user_agent` varchar(512) DEFAULT NULL,
  PRIMARY KEY (`logi_id`),
  KEY `logi_login` (`logi_login`),
  KEY `logi_ip` (`logi_ip`),
  KEY `logi_action` (`logi_action`)
) DEFAULT CHARSET=utf8 ;
