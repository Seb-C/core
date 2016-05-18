<?php

namespace NoviusChecks;

$className = 'TestRepertoireConfig'; // Le nom de la class
class TestRepertoireConfig extends TestRepertoire
{
    public function __construct()
    {
        parent::__construct();
        $this->testName      = 'repertoire local/config (lecture seule)';
        $this->repertoireNom = NOSROOT.'local/config';
        $this->testEcriture  = false;
    }
}
