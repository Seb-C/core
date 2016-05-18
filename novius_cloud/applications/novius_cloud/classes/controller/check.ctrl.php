<?php

namespace NC;



class Controller_Check extends \Nos\Controller
{
    public function action_index()
    {

        $this->_loadclass('TestGenerique');
        $this->_loadclass('TestRepertoire');

        $params = array(
            'ajax'  => !empty($_GET['ajax']),
            'tests' => array(),
        );

        // Liste des checks
        $checks = self::_genereListeChecks();
        if ($params['ajax']) {
            $checks = array_intersect_key($checks, array($_GET['ajax'] => 1));
        }

        // Lancement de chaque test
        foreach ($checks as $key => $filename) {
            $className = $this->_loadclass($filename);
            if (!empty($className) ) {
                $className = 'NoviusChecks\\' . $className;
                $test = new $className;
                $test->Test();
                $params['tests'][$test->GetCategorieName()][$key] = $test;
            }
        }

        // Affichage
        if ($params['ajax']) {
            return \View::forge('novius_cloud::check/item', array(
                'key'   => $key,
                'test'  => $test,
            ), false);
        }
        return \View::forge('novius_cloud::check/list', $params, false);
    }

    /**
     * Liste des tests, dans l'ensemble des repertoires a tester (novius_check)
     * @return array    Liste des tests
     */
    protected function _genereListeChecks()
    {
        // ajout des tests génériques de Novius-OS
        $checks = self::_listeChecksDansRepertoire(NOSROOT.'novius-os/novius_cloud/applications/novius_cloud/novius_checks/', '-nos');

        // ajout des tests du site
        $checks = array_merge($checks, self::_listeChecksDansRepertoire(NOSROOT.'local/novius_checks/', '-site'));

        // ajout des tests de chaque application
        $dir = NOSROOT.'local/applications/';
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file != '..' && is_dir($dir . $file . '/novius_checks')) {
                    $checks = array_merge($checks, self::_listeChecksDansRepertoire($dir . $file . '/novius_checks/', '-app-'.$file));
                }
            }
            closedir($dh);
        }

        // on trie par ordre des clés (le prefixe 200_xxx sert à ça, on mélange les tests génériques et spécifiques
        ksort($checks);

        return $checks;
    }

    /**
     * Liste des tests dans UN repertoire donné
     *
     * @param string    $dir
     * @param string    $suffixe
     *
     * @return array    Liste des tests
     */
    protected function _listeChecksDansRepertoire($dir, $suffixe = '')
    {
        if (!is_dir($dir)) {
            return array();
        }
        $checks = array();
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (is_file($dir . $file) && preg_match('/^([0-9]+.*)\.php$/', $file)) {
                    $checks[$file.$suffixe] = $dir . $file;
                }
            }
            closedir($dh);
        }
        return $checks;
    }

    /**
     * Chargement d'une classe "noviusCheck"
     * @param $filepath
     *
     * @return string
     */
    protected function _loadclass($filepath)
    {
        if (ctype_alnum($filepath)) {
            $filepath = __DIR__.'/../../novius_checks/'.$filepath.'.php';
        }
        require_once $filepath;
        if (empty($className)) {
            $className = '';
        }
        return $className;
    }
}