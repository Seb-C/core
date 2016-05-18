<?php
namespace NoviusChecks;

$className = 'TestLibrairieCurl'; // Le nom de la class
class TestLibrairieCurl extends TestGenerique
{
    public function __construct()
    {
        parent::__construct();
        $this->testName             = 'Commande curl';
        $this->categorieName        = 'Librairies';
        $this->consequencesSiErreur = 'Utilisé pour appels http distants';
    }

    public function Test()
    {
        $test_curl = function_exists('curl_init');
        if (!$test_curl) {
            $this->status = 'warning';
            $this->msg    = 'La fonction PHP curl_init() n\'est pas disponible';
            return;
        }

        $this->status = 'ok';
    }
}
