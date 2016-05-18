<h1>Novius Cloud</h1>

<?php

if (\NC\Controller_Migrations::isDirty()) {
    d('Attention, les migrations ne sont pas à jour !', 0);
}
?>

<ul>
    <li><a href="?noviuscloud=videcache">Vide cache</a></li>
    <li><a href="?noviuscloud=check">Tests serveur</a></li>
    <?php
    if ( \Fuel::$env !== \Fuel::PRODUCTION ) {
    ?>
        <li><a href="?noviuscloud=devoptions">Options Dev</a></li>
    <?php
    }
    ?>
    <li><a href="?noviuscloud=migrations">Migrations</a></li>
    <?php if (function_exists('apc_cache_info')) { ?>
        <li><a href="?noviuscloud=apc">APC Cache</a></li>
    <?php } ?>
    <?php if (extension_loaded('Zend OPcache')) { ?>
        <li><a href="?noviuscloud=opcache">Zend OP Cache</a></li>
    <?php } ?>
    <li><a href="?noviuscloud=phpinfo">PHP Info</a></li>
    <li><a href="?noviuscloud=crons" onclick="return confirm('Attention d\'être sur le bon domaine !')">Générer le fichier de planification des crons</a></li>
</ul>
