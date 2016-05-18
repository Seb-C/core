<?php
namespace NoviusChecks;

$className = 'TestLibrairieSoap'; // Le nom de la class
class TestLibrairieSoap extends TestGenerique
{
    public function __construct()
    {
        parent::__construct();
        $this->testName             = 'Librairie Soap';
        $this->categorieName        = 'Librairies';
        $this->consequencesSiErreur = 'Pas de connexion possible à Posta-Nova';
    }

    public function Test()
    {
        if (!class_exists('\SoapClient')) {
            $this->status = 'warning';
            $this->msg    = 'Impossible de charger la librairie SOAP';
            return;
        }
        $this->status = 'ok';
    }
}

