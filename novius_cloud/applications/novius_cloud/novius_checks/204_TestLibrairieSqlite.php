<?php
namespace NoviusChecks;

$className = 'TestLibrairieSqlite'; // Le nom de la class
class TestLibrairieSqlite extends TestGenerique
{
    public function __construct()
    {
        parent::__construct();
        $this->testName             = 'Librairie Sqlite (PDO)';
        $this->categorieName        = 'Librairies';
        $this->consequencesSiErreur = '';
    }

    public function Test()
    {
        $this->status = in_array('sqlite', \PDO::getAvailableDrivers()) ? 'ok' : 'warning';
    }
}
