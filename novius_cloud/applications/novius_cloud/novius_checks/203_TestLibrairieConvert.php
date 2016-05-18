<?php
namespace NoviusChecks;

$className = 'TestLibrairieConvert'; // Le nom de la class
class TestLibrairieConvert extends TestGenerique
{
    public function __construct()
    {
        parent::__construct();
        $this->testName             = 'Commande convert (imagemagick)';
        $this->categorieName        = 'Librairies';
        $this->consequencesSiErreur = 'Utilisé pour la retouche et le redimensionnement des images';
    }

    public function Test()
    {
        $imageConfig = \Config::load('image');
        $program  = 'convert';
        $commande = $imageConfig['imagemagick_dir'].$program.' -version 2>&1'; // 2&1 pour avoir le flux d'erreur dans lequel s'affiche la version

        exec($commande, $output, $return_var);
        if ($return_var == 0) {
            $this->testDescription  .= '<br/>Version installée: ' . $output[0];
            $this->status = 'ok';
            return;
        }

        $this->status = ($return_var == 0 ? 'ok' : 'warning');
    }
}
