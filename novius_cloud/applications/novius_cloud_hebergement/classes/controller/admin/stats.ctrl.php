<?php

namespace NC\Hebergement;

class Controller_Admin_Stats extends \Nos\Controller_Admin_Application
{
    public function action_index()
    {
        // Lecture de la config
        $config = \Config::load('novius_cloud_hebergement::def', true);

        return \View::forge('novius_cloud_hebergement::admin/layout', array(
            'form_action' => \Uri::current(),
            'view'        => array(
                'view'   => 'novius_cloud_hebergement::admin/stats',
                'params' => array(
                    'config'         => $config,
                    //'autre_variable' => 'cool !',
                )
            )
        ));
    }
}
