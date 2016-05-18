<?php
namespace NoviusChecks;

$className = 'TestRepertoireCache'; // Le nom de la class
class TestRepertoireCache extends TestRepertoire
{
    public function __construct()
    {
        parent::__construct();
        $this->testName      = 'repertoire public/cache (lecture et ecriture)';
        $this->repertoireNom = NOSROOT.'public/cache';
        $this->testEcriture  = true;
    }
}
