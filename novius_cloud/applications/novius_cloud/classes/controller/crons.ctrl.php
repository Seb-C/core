<?php

namespace NC;

class Controller_Crons extends \Nos\Controller
{
    public function action_index()
    {
        $config_exemple = array(
            '\\Local\\Task_Test' => array(
                'minutes'       => '0,5,10,15,20,25,30,35,40,45,50,55',
                'heures'        => '09,10,11,12,13,14,15,16,17,18,19',
                'jours_semaine' => '1,2,3,4,5',
                'jours_mois'    => '9',
                'env'           => array(
                    'SERVER_NAME' => $_SERVER['SERVER_NAME']
                ),
            ),
        );

        $config = \Config::load('local::crons', true);

        if ( empty($config) ) {
            d('Oups, pas de config "crons" définie sur ce site !!', 0);
        } else {
            file_put_contents(APPPATH.'/data/cron.serialized', serialize($config), 0);
            d('Nouvelle config sauvegardée !', 0);
            d($config, 0);
        }

        echo '<hr />';

        d('Exemple de config, à placer dans le fichier local/config/crons.config.php :', 0);
        d("<?php\n\nreturn ".var_export($config_exemple, true).';', 0);

        exit;
    }
}