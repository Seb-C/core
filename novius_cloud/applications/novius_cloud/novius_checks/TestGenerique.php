<?php
namespace NoviusChecks;

abstract class TestGenerique
{

    protected $testName;

    protected $testDescription;

    protected $categorieName;

    protected $status;

    protected $msg;

    public function __construct()
    {
        $this->testName             = '';
        $this->testDescription      = '';
        $this->categorieName        = '';
        $this->status               = 'info';
        $this->msg                  = '';
        $this->consequencesSiErreur = '';
        $this->correctionSiErreur   = '';
    }

    // Le nom du test
    public function GetTestName()
    {
        return $this->testName;
    }
    // La description du test
    public function GetTestDescription()
    {
        return $this->testDescription;
    }

    // Le nom de la categorie
    public function GetCategorieName()
    {
        return $this->categorieName;
    }

    // Le statut retourne par le test
    // ok, info, warning, error
    public function GetStatus()
    {
        return $this->status;
    }

    // Le message a afficher, dans la colonne de droite
    public function GetMsg()
    {
        return $this->msg;
    }

    // Si jamais une erreur (warning ou error) survient, quel message retourner ?
    // Quelles seront les consequences et impacts de cette erreur ?
    public function GetConsequencesSiErreur()
    {
        return $this->consequencesSiErreur;
    }

    // Si jamais une erreur (warning ou error) survient, comment la resoudre ?
    public function GetCorrectionSiErreur()
    {
        return $this->correctionSiErreur;
    }

    // Le test en lui meme
    // @return void
    abstract public function Test();
}
