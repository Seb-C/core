<?php
namespace NoviusChecks;

$className = 'TestWSPostaNova'; // Le nom de la classe
class TestWSPostaNova extends TestGenerique
{
    public function __construct()
    {
        parent::__construct();
        $this->testName             = 'Webservices de Posta-Nova';
        $this->categorieName        = 'Services';
        $this->testDescription      = 'Test l\'accessibilité des WS de Posta et certaines fonctions';
        $this->consequencesSiErreur = 'Plus de synchro de contacts ou de gabarits Posta-Nova';

    }

    public function Test()
    {
        try {
            \Module::load('lib_postanova');
        } catch (\Exception $e) {
            $this->status = 'warning';
            $this->msg    = "L'application lib_postanova n'est pas installée, impossible de la tester";
            return;
        }

        $contact = new \Lib\PostaNova\Contact();
        $config = $contact->getConfig();

        foreach (array('creation', 'contact', 'envoi') as $type) {
            if ($config[$type]['server_url']) {
                $xml   = @file_get_contents($config[$type]['server_url']);
                if (!$xml) {
                    $this->status = 'warning';
                    $this->msg    = 'Impossible de charger le contenu de ' .  $config[$type]['server_url'];
                    return;
                }
            }
        }


        if ($config['contact']['server_url']) {

            try {
                $listeChamps = $contact->listeChamps();
            } catch (\Lib\Postanova\SoapException $e) {
                // une erreur s'est produite, $listeChamps[0] contient le message
                $this->status = 'warning';
                $this->msg    = $e->getMessage();
                return;
            }

            if (count($listeChamps) == 0) {
                $this->status = 'warning';
                $this->msg    = 'Impossible de récupérer la liste de champs pour le compte de contact ' . $config['contact']['md5'];
                return;
            }
        }

        $this->status = 'ok';
    }
}