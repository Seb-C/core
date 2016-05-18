<?php
namespace NoviusChecks;

$className = 'TestLibrairieGd'; // Le nom de la class
class TestLibrairieGd extends TestGenerique
{
    public function __construct()
    {
        parent::__construct();
        $this->testName             = 'Librairie GD';
        $this->categorieName        = 'Librairies';
        $this->consequencesSiErreur = 'Utilis� pour la g�n�ration de QR Code';
    }

    public function Test()
    {
        $this->status = extension_loaded('gd') ? 'ok' : 'warning';
    }
}
