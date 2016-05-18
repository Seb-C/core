<?php
namespace NoviusChecks;

$className = 'TestLibrairieUnzip'; // Le nom de la class
class TestLibrairieUnzip extends TestGenerique
{
    public function __construct()
    {
        parent::__construct();
        $this->testName             = 'Commande unzip';
        $this->categorieName        = 'Librairies';
        $this->consequencesSiErreur = 'Utilisé pour l\'import de documents / images';
    }

    public function Test()
    {
        $test_gzinflate = function_exists('gzinflate');
        if (!$test_gzinflate) {
            $this->status = 'warning';
            $this->msg    = 'La fonction PHP gzinflate() n\'est pas disponible';
            return;
        }

        /*$test_gzinflate = function_exists('bzdecompress');
        if (!$test_gzinflate) {
            $this->status = 'warning';
            $this->msg    = 'La fonction PHP bzdecompress() n\'est pas disponible';
            return;
        }*/

        $this->status = 'ok';
    }
}
