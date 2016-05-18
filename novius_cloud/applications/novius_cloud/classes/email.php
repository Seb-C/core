<?php

namespace NC;

class Email
{
    static protected $INIT_OK = false;

    static public function init()
    {
        // On ne peut charger qu'une seule fois cette classe.
        if (self::$INIT_OK) {
            return false;
        }
        self::$INIT_OK = true;

        // On veut utiliser notre classe SMTP
        \Autoloader::add_classes(array(
            'Email_Driver_Smtpnoviuscloud'	=> __DIR__.'/email/driver/smtpNoviusCloud.php',
        ));

        // Ajout d'xheaders sur les mails
        \Event::register('email.before_send', function ($email) {
            if (EN_PROD) {
                $email->header('X-SiteID', $_SERVER['SERVER_NAME']);
            } else {
                $email->header('X-RepID', substr($_SERVER['SERVER_NAME'], 0, strpos($_SERVER['SERVER_NAME'], '.')));
            }
        });

        // Log en cas d'erreur
        \Event::register('email.error', function ($params) {
            $error = $params['exception'];
            if (is_object($error)) {
                \NC::log($error->getMessage(), 'email.errlog');
            }
            \NC\StatsD::increment('email.errors');
        });

        // Log en cas de mail envoyé
        \Event::register('email.after_send', function ($email) {
            if (method_exists($email, '__get')) { // Compatibilité Chiba1
                $data = array_filter(array(
                    'from'      => $email->config['from']['email'],
                    'to'        => implode(', ', array_keys((array) $email->to)),
                    'cc'        => implode(', ', array_keys((array) $email->cc)),
                    'bcc'       => implode(', ', array_keys((array) $email->bcc)),
                    'subject'   => $email->subject,
                    'body_size' => mb_strlen($email->body.$email->alt_body),
                ));
                \NC::log($data, 'email.log');
            }
            \NC\StatsD::increment('email.count');

            // Stats d'Hebergement (SQLite3)
            $confHeberg = \Config::load('novius_cloud_hebergement::def', true);
            if (!empty($confHeberg['SQLITE_HEBERGEMENT']) && is_file($confHeberg['SQLITE_HEBERGEMENT']) && class_exists('SQLite3')) {
                $sqLite = new \SQLite3($confHeberg['SQLITE_HEBERGEMENT']);
                if (!empty($sqLite)) {
                    $result  = $sqLite->query('PRAGMA user_version');
                    $data    = $result->fetchArray();
                    $version = $data['user_version'];

                    // On verifie que la version du fichier est supérieure à 1 pour y écrire les logs (version réalisée en juillet 2008)
                    if ($version >= 1) {
                        $date   = date('Ymd');
                        $octets = method_exists($email, '__get') ? mb_strlen($email->body.$email->alt_body) : 0;

                        $result = $sqLite->query('SELECT lo_octets_out, lo_compte FROM logs WHERE lo_date='.$date.' AND lo_type=\'mail\'');
                        $data   = $result->fetchArray();
                        if (!empty($data)) {
                            $sqLite->query('UPDATE logs SET lo_octets_out=lo_octets_out+'.$octets.', lo_compte=lo_compte+1 WHERE lo_date='.$date.' AND lo_type=\'mail\'');
                        } else {
                            $sqLite->query('INSERT INTO logs (lo_date, lo_type, lo_octets_out, lo_compte) VALUES ('.$date.', \'mail\', '.$octets.', 1)');
                        }
                    }
                    $sqLite->close();
                }
            }
        });
    }

    /**
     * @deprecated
     * @return mixed
     */
    static public function config()
    {
        logger(\Fuel::L_WARNING, '\NC\Email::config is deprecated (voir local/config/email.config.php). La config Novius Cloud est maintenant mergée automatiquement.');

        return include NOSROOT.'novius-os/novius_cloud/config/email.config.php';
    }
}