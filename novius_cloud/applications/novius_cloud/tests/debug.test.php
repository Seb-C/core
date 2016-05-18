<?php

// Fonction d() / dd() / ...

echo '<h1>d(), v()...</h1>';

d(array(
    'test' => true,
    'machin' => 'chose',
    'int' => 15,
    'html' => '<strong>test</strong>',
));
v(array(
    'test' => true,
    'machin' => 'chose',
    'int' => 15,
    'html' => '<strong>test</strong>',
), 'tab');
d(true);
d(12, 3);
d('Une chaine de caracteres', 'machaine');

echo '<h1>Avec des classes, on a les noms des fonctions</h1>';
class Test
{
    public function __construct() {
        d('Test->init');
    }
    public function go() {
        d('Test->go()', 8);
    }
}

class AutreTest extends Test
{
    public function go() {
        d('AutreTest->go()', 8);
        parent::go();
    }
    public function go2() {
        static::go();
    }
}

$d = new Test;
$d->go();

$d = new AutreTest;
$d->go2();

// Remote IP
echo '<h1>Remote IP</h1>';
d(NC::remoteIp());

// Trace
echo '<h1>Trace</h1>';
d(\NC::trace(8));

// Bug #950
echo '<h1 id="bug-950">Bug #950 - d() avec trace vide</h1>';
$i = 1;
d($i, 0);
d($i, '$i', 0);

// Null
echo '<h1>d(null)</h1>';
d(null);

// Log
echo '<h1>Logs</h1>';
NC::log('test : '.mt_rand());
$r = NC::log('test - '.mt_rand(), 'fichier.log');
d($r, 'log');