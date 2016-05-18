<?php
namespace NoviusChecks;

class TestRepertoire extends TestGenerique
{

    protected $repertoireNom;

    protected $testEcriture;

    public function __construct() {
        $this->repertoireNom = '';
        $this->testEcriture  = false;
        $this->categorieName = 'Repertoires';
    }

    public function Test() {
        if ($this->repertoireNom == '') {
            $this->msg    = 'Manque nom repertoire';
            $this->status = 'error';
            return;
        }
        if (!is_dir($this->repertoireNom)) {
            $this->msg    = 'Le repertoire '.$this->repertoireNom.' n\'existe pas';
            $this->status = 'error';
            return;
        }

        if ($this->testEcriture && !is_writable($this->repertoireNom)) {
            $this->msg    = 'Impossible d\ecrire dans le repertoire '.$this->repertoireNom;
            $this->status = 'error';
            return;
        }
        $this->status = 'ok';
        return;
    }
}
