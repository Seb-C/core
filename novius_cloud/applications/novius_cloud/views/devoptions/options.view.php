<h1>Options de développement</h1>
<style>
    .enabled {
        color : green;
    }

    .disabled {
        color : red;
    }
</style>

<?php 

$isChiba2 = is_dir(NOSROOT.'novius-os/vendor/');
$isAtLeastDubrovka = is_dir(NOSROOT.'novius-os/framework/lang/es/');

if (!$isChiba2) {
    echo '<div style="font-weight: bold; border: 1px solid red; color: red; padding: 10px; margin: 0 10px">Attention, ces options ne fonctionnent que si votre local/config.php est correctement configuré ! Ce n\'est le cas, par défaut, qu\'à partir de Chiba2.</div>';
}

echo '<h2>Gestion du cache</h2>';
if ($nocache) {
    echo '<span class="disabled">Actuellement, le cache est désactivé : </span> <a href="?noviuscloud=devoptions&nocache=0">Activer</a>';
} else {
    echo '<span class="enabled">Actuellement, le cache est activé : </span> <a href="?noviuscloud=devoptions&nocache=1">Désactiver</a>';
}

echo '<h2>Gestion du profiler</h2>';
if (!$profiling) {
    echo '<span class="disabled">Actuellement, le profiler est désactivé : </span> <a href="?noviuscloud=devoptions&profiling=1">Activer</a>';
} else {
    echo '<span class="enabled">Actuellement, le profiler est activé : </span> <a href="?noviuscloud=devoptions&profiling=0">Désactiver</a>';
}
?>

<?php
if ($isAtLeastDubrovka) {
    echo '<h2>Gestion du error_reporting</h2>';
    if ($noerrorall) {
        echo '<span class="disabled">Actuellement, error_reporting(E_ALL | E_STRICT); est désactivé : </span> <a href="?noviuscloud=devoptions&noerrorall=0">Activer</a>';
    } else {
        echo '<span class="enabled">Actuellement, error_reporting(E_ALL | E_STRICT); est activé : </span> <a href="?noviuscloud=devoptions&noerrorall=1">Désactiver</a>';
    }
}
?>

<br /><br /><br /><a href="?noviuscloud">Retour Novius Cloud</a>