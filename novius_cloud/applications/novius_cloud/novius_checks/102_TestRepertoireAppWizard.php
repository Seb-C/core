<?php

namespace NoviusChecks;

$className = 'TestApplicationAppwizard'; // Le nom de la class
class TestApplicationAppwizard extends TestGenerique
{
    public function __construct()
    {
        parent::__construct();
        $this->testName             = 'Application noviusos_appwizard non installée';
        $this->categorieName        = 'Applications';
        $this->consequencesSiErreur = 'Risque d\'erreur si passage en prod!!!';
    }

    public function Test()
    {
        if (is_dir(NOSROOT.'public/static/apps/noviusos_appwizard')) {
            $this->status = 'warning';
            $this->msg    = 'Attention, noviusos_appwizard installée';
            return;
        }
        $this->status = 'ok';
    }
}
