<?php
$msg_confirm = 'Etes vous sur de vider le cache des médias?\n\nCela peut ralentir le serveur car les miniatures devront être regénérées.';
$msg_attention = 'Attention';
if (strpos(NOSROOT, 'nos_sites_work') !== false) {
    $msg_confirm .= '\n\nAttention, cela affecte aussi les médias du site principal dans nos_sites';
    $msg_attention = 'Attention, les options suivantes affectent aussi les médias du site principal dans /data/www/web/nos_sites/XXX';
}
?>
<h1>Vider cache</h1>
<ul>
    <li><a href="?noviuscloud=videcache&vide_cache_fuelphp">Vide cache fuelphp</a> local/cache/fuelphp</li>
    <li><a href="?noviuscloud=videcache&vide_cache_pages">Vide cache pages</a> local/cache/pages</li>
    <li><a href="?noviuscloud=videcache&vide_cache_all_sauf_media">Vide tout le cache (y compris dev sp&eacute;), SAUF local/cache/media et local/cache/data</a></li>
</ul>
<br/>
<p><?= $msg_attention ?>:</p>
<ul>
    <li><a href="?noviuscloud=videcache&vide_cache_media" onclick="return confirm('<?= $msg_confirm ?>')">Vide cache media et data</a> local/cache/media et public/cache/media + local/cache/data et public/cache/data</li>
    <li><a href="?noviuscloud=videcache&vide_cache_all" onclick="return confirm('<?= $msg_confirm ?>')">Vide cache ALL (y compris dev sp&eacute;, m&eacute;dias et data)</a> local/cache</li>
</ul>
<br/>
<a href="?noviuscloud">Retour Novius Cloud</a>
