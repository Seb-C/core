<?php
//TestDroitPage('webmaster');

// Sans le setlocale US pour les nombre, le séparateur décimal des float est la virgule, ce qui fait planter 
// flot (13,6 au lieu de 13.6)
setlocale(LC_NUMERIC, 'en_US.utf8');

// Quel mode ?
// Les modes disponibles sont year (annee glissante), month (mois glissant) et all (tout)
$mode = 'year';
if ( !empty($_GET['mode']) && in_array($_GET['mode'], array('month', 'all')) ) {
    $mode = $_GET['mode'];
}

// Lecture des donnees, depuis la base SQLite
if (!is_file($config['SQLITE_HEBERGEMENT'])) {
        echo "Pas de données d'hébergement";
        //echo $config['SQLITE_HEBERGEMENT'];
    //header('location: ../');
    exit;
}

$debugNowTime = time();
$nowTime = time();

// pour debug
//if ( !empty($_GET['monTime'])) {
//    $testDate = $_GET['monTime'];
//    $nowTime = strtotime($testDate);
//}
// fin debug

$nowMonth = date('n', $nowTime);
$nowYear = date('Y', $nowTime);



$sqLite = new SQLite3($config['SQLITE_HEBERGEMENT']);
//$sqLite->openBase($config['SQLITE_HEBERGEMENT']);

// Quelle version lancer ? Cela depend de la version de la base
$r       = $sqLite->query('PRAGMA user_version');
$ar       = $r->fetchArray();
$version = $ar['user_version'];

if ( $version >= 1 ) {
    // Initialisation des variables
    $clwhere         = '';
    $aEspaceDisque  = array();
    $aBandePassante = array();
    $aMails         = array();

    // Mode d'affichage
    if ($mode == 'month') {
        $dateFormat    = 'Y-m-d';
        $timeStart     = $nowTime - 30 * 24 * 3600;
        $timeEnd       = $nowTime;
        $timeIncrement = 24 * 3600;

//        $clwhere = 'AND substr(lo_date, 1, 8) > \''.date('Ymd', $timeStart).'\'';
        $clwhere = 'AND substr(lo_date, 1, 8) > \''.date('Ymd', $timeStart).'\''.' AND substr(lo_date, 1, 8) < \''.date('Ymd', $timeEnd).'\'';

        $labelAxeX  = 'Jour';
        $labelTitre = ', sur les 30 derniers jours';

    } elseif ($mode == 'year') {
        $dateFormat    = 'Y-m';
        $timeStart     = $nowTime - 365 * 24 * 3600;
        $timeEnd       = mktime(0, 0, 0, $nowMonth, 28, $nowYear);
        $timeIncrement = 30 * 24 * 3600;

        $clwhere = 'AND substr(lo_date, 1, 8) > \''.date('Ymd', $timeStart).'\''.' AND substr(lo_date, 1, 8) < \''.date('Ymd', $timeEnd).'\'';

        $labelAxeX  = 'Mois';
        $labelTitre = ', sur les 12 derniers mois';
    } else {
        $dateFormat = 'Y-m';

        $labelAxeX  = 'Mois';
        $labelTitre = ', depuis le début';
    }

    // Formatage de la date issue de SQLite, au format date francais
    function formatDate($lsqlite_date, $ldateFormat) {
        $ldate = strptime($lsqlite_date, '%Y%m%d');
        $ltime = mktime($ldate['tm_hour'], $ldate['tm_min'], $ldate['tm_sec'], $ldate['tm_mon'] + 1, $ldate['tm_mday'], $ldate['tm_year'] + 1900);
        return date($ldateFormat, $ltime);
    }

    // Formatage de la datetime issue de SQLite, au format date francais
    function formatDatetime($lsqlite_date, $ldateFormat) {
        $ldate = strptime($lsqlite_date, '%Y%m%d-%H%M%S');
        $ltime = mktime($ldate['tm_hour'], $ldate['tm_min'], $ldate['tm_sec'], $ldate['tm_mon'] + 1, $ldate['tm_mday'], $ldate['tm_year'] + 1900);
        return date($ldateFormat, $ltime);
    }

    // Initialisation de l'espace disque et de la bande passante, avec
    // des valeurs nulles. Cela permet d'avoir des axes similaires.
    if (!empty($timeStart) && !empty($timeEnd) && !empty($timeIncrement)) {
        while ($timeStart <= $timeEnd) {
            $aBandePassante[date($dateFormat, $timeStart)] = 0;
            $aEspaceDisque[date($dateFormat, $timeStart)]  = 0;
            $timeStart += $timeIncrement;
        }
    }

    // Agregation de l'évolution du disque, sur la periode demandee, avec le mode choisi
    $r = $sqLite->query('SELECT lo_date, SUM(lo_octets_out) as somme FROM logs WHERE lo_type = \'espace\' '.$clwhere.' GROUP BY lo_date ORDER BY lo_date ASC');

    while ( $ar = $r->fetchArray() ) {
        $date = formatDatetime($ar['lo_date'], $dateFormat);
        if (empty($aEspaceDisque[$date])) {
            $aEspaceDisque[$date] = 0;
        }
        $aEspaceDisque[$date] = max($aEspaceDisque[$date], $ar['somme']);
    }

    // Agregation de la bande passante, sur la periode demandee, avec le mode choisi
    $r = $sqLite->query('SELECT lo_date, SUM(lo_octets_out) as somme FROM logs WHERE lo_type = \'web\' '.$clwhere.' GROUP BY lo_date ORDER BY lo_date ASC');

    while ($ar = $r->fetchArray()) {
        $date = formatDate($ar['lo_date'], $dateFormat);
        if (empty($aBandePassante[$date])) {
            $aBandePassante[$date] = 0;
        }
        $aBandePassante[$date] += $ar['somme'];
    }

    // Agregation du nombre de mails envoyes, sur la periode demandee, avec le mode choisi
    $r = $sqLite->query('SELECT lo_date, SUM(lo_compte) as somme FROM logs WHERE lo_type = \'mail\' '.$clwhere.' GROUP BY lo_date ORDER BY lo_date ASC');

    while ($ar = $r->fetchArray()) {
        $date = formatDate($ar['lo_date'], $dateFormat);
        if (empty($aMails[$date])) {
            $aMails[$date] = 0;
        }
        $aMails[$date] += $ar['somme'];
    }



} else {
    echo 'Version de la base SQLite inconnue';
    exit;
}

?>

<?php

function calculerEchelle($arr) {
    $max = max($arr);
    if ($max > 2 * 1024 * 1024 * 1024 * 1024) {
        $uniteLabel = 'To';
        $uniteValue = 1024 * 1024 * 1024 * 1024;
    } elseif ($max > 2 * 1024 * 1024 * 1024) {
        $uniteLabel = 'Go';
        $uniteValue = 1024 * 1024 * 1024;
    } elseif ($max > 2 * 1024 * 1024) {
        $uniteLabel = 'Mo';
        $uniteValue = 1024 * 1024;
    } elseif ($max > 2 * 1024) {
        $uniteLabel = 'Ko';
        $uniteValue = 1024;
    } else {
        $uniteLabel = 'o';
        $uniteValue = 1;
    }
    return array($uniteLabel, $uniteValue);
}

function mois($mois) {
    $moiss = array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre');
    return $moiss[$mois - 1];
}

function maDateMA($date) {
    $dateParts = explode('-', $date);
    return mois($dateParts[1])." ".$dateParts[0];
}

function maDateJM($date) {
    $dateParts = explode('-', $date);
    return $dateParts[2]."/".$dateParts[1];
}



?>
<div class="editbody2">
    <div class="cadre1">
        <div class="cadre2">
            <div style="text-align: right; margin: 0 50px;">
                <select id="jsmode" class="js_autosubmit" name="mode">
                    <option value="month" <?= $mode == 'month' ? 'selected="selected"' : '' ?>>30 derniers jours</option>
                    <option value="year" <?= $mode == 'year' ? 'selected="selected"' : '' ?>>12 derniers mois</option>
                    <option value="all" <?= $mode == 'all' ? 'selected="selected"' : '' ?>>Depuis le d&eacute;but</option>
                </select>
  <!--              <select id="jsmode" class="js_autosubmit" name="monTime">
                    <option value="" ></option>
                    <option value="01-01-2012" >01-01-2012</option>
                    <option value="01-02-2012" >01-02-2012</option>
                    <option value="01-03-2012" >01-03-2012</option>
                    <option value="01-04-2012" >01-04-2012</option>
                    <option value="01-05-2012" >01-05-2012</option>
                    <option value="01-06-2012" >01-06-2012</option>
                    <option value="01-07-2012" >01-07-2012</option>
                    <option value="01-08-2012" >01-08-2012</option>
                    <option value="01-09-2012" >01-09-2012</option>
                    <option value="01-10-2012" >01-10-2012</option>
                    <option value="01-11-2012" >01-11-2012</option>
                    <option value="01-12-2012" >01-12-2012</option>
                    <option value="21-03-2014" >21-03-2014</option>
                    <option value="31-12-2013" >31-12-2013</option>
                    <option value="01-12-2013" >01-12-2013</option>
                    <option value="31-11-2013" >31-11-2013</option>
                    <option value="01-01-2013" >01-01-2013</option>
                    <option value="31-01-2013" >31-01-2013</option>
                    <option value="01-01-2014" >01-01-2014</option>
                    <option value="31-01-2014" >31-01-2014</option>

                </select>-->
            </div>
            <h2>Trafic échangé</h2>
            <div id="sourceChartBandePassante" style="height: 300px; clear: both;"></div><br /><br />
            <h2>Espace disque</h2>
            <div id="sourceChartEspaceDisque" style="height: 300px; clear: both;"></div><br /><br />
            <h2>Mails envoy&eacute;s</h2>
            <div id="sourceChartMails" style="height: 300px; clear: both;"></div>
        </div>
    </div>
</div>
<script language="javascript" type="text/javascript" src="/static/apps/novius_cloud_hebergement/js/jquery.flot.js"></script>
<script id="source" language="javascript" type="text/javascript">

    $(function () {

        // BANDE PASSANTE
        <?php
            // Unites de l'axe Y
            list($uniteLabel, $uniteValue) = calculerEchelle($aBandePassante);

            echo 'var d1 = [';
            $sep = '';

            $i = 1;
            ksort($aBandePassante);
            foreach ($aBandePassante as $date => $bp) {
                $bp   = round($bp / $uniteValue, 1);
                echo $sep, '[', $i,', ', $bp, ']';
                $i++;
                $sep = ', ';
            }
            echo '];';
        ?>

        $.plot($("#sourceChartBandePassante"), [
            {
                data: d1,
                label: "Volume transf&eacute;r&eacute;, en <?= $uniteLabel ?>",
                // v1 jaune
                // bars: { show: true, fill: true, fillColor: 'rgba(255, 219, 77, 0.8)', lineWidth: 1, opacity: 0.2 },
                // color: '#d0a55d',
                // v2 orange
                bars: { show: true, fill: true, fillColor: 'rgba(254, 197, 16, 0.8)', lineWidth: 1, opacity: 0.2 },
                color: '#d29124',
                shadowSize: 1
            }
        ], {
            grid: {
                show: true,
                backgroundColor: '#dddddd'
            },
            legend: {
                position: "nw"
            },
            colors: ['red', 'blue', 'red', 'green', 'yellow', 'red', 'blue'],
            xaxis: {
                ticks: [
                    <?php

                        $i   = 1.5;
                        $sep = '';
                        ksort($aBandePassante);
                        foreach ($aBandePassante as $date => $bp) {
                            $maDate = $date.'-01';
                            if ($mode != 'month') {

                                echo $sep, '[', $i, ', \'', maDateMA($maDate),'\']';
                            } else {

                                echo $sep, '[', $i, ', \'', maDateJM($maDate),'\']';
                                //echo $sep, '[', $i, ', \'', $date->format('%d/%m'),'\']';
                            }
                            $sep = ', ';
                            $i++;
                        }

                    ?>
                ]
            }
        });



        // Espace disque
        <?php
            // Unites de l'axe Y
            list($uniteLabel, $uniteValue) = calculerEchelle($aEspaceDisque);

            echo 'var d2 = [';
            $sep = '';

            $i = 1;
            $currentEspaceDisque = 0;
            ksort($aBandePassante);
            foreach ($aBandePassante as $date => $bp) {
                if (!empty($aEspaceDisque[$date]) ) {
                    $currentEspaceDisque = $aEspaceDisque[$date];
                }

                $ed   = round($currentEspaceDisque / $uniteValue,1);
                echo $sep, '[', $i,', ', $ed, ']';
                $i++;
                $sep = ', ';
            }
            echo '];';
        ?>

        $.plot($("#sourceChartEspaceDisque"), [
            {
                data: d2,
                label: "Espace disque utilis&eacute;, en <?= $uniteLabel ?>",
                // lines: { show: true, fill: true, fillColor: 'rgba(0, 102, 153, 0.8)', lineWidth: 1, opacity: 0.2 },
                // color: '#006699',
                lines: { show: true, fill: true, fillColor: 'rgba(254, 197, 16, 0.8)', lineWidth: 1, opacity: 0.2 },
                color: '#d29124',

                shadowSize: 1
            }
        ], {
            grid: {
                show: true,
                backgroundColor: '#dddddd'
            },
            legend: {
                position: "nw"
            },
            colors: ['red', 'blue', 'red', 'green', 'yellow', 'red', 'blue'],
            xaxis: {
                ticks: [
                    <?php

                        $i   = 1.5;
                        $sep = '';
                        ksort($aBandePassante);
                        foreach ($aBandePassante as $date => $bp) {
                            //$date = new Date($date.'-01');
                            $maDate = $date.'-01';
                            if ($mode != 'month') {
                                echo $sep, '[', $i, ', \'',  maDateMA($maDate),'\']';
                            } else {
                                echo $sep, '[', $i, ', \'', maDateJM($maDate),'\']';
                                //echo $sep, '[', $i, ', \'', $date->format('%d/%m'),'\']';
                            }
                            $sep = ', ';
                            $i++;
                        }

                    ?>
                ]
            }
        });


        // Mails
        <?php
            // Unites de l'axe Y
            list($uniteLabel, $uniteValue) = calculerEchelle($aEspaceDisque);

            echo 'var d3 = [';
            $sep = '';

            $i = 1;
            ksort($aBandePassante);
            foreach ($aBandePassante as $date => $bp) {
                $nbMails = empty($aMails[$date]) ? 0 : $aMails[$date];
                echo $sep, '[', $i,', ', $nbMails, ']';
                $i++;
                $sep = ', ';
            }
            echo '];';
        ?>

        $.plot($("#sourceChartMails"), [
            {
                data: d3,
                label: "Nombre de mails envoy&eacute;s",
                // bars: { show: true, fill: true, fillColor: 'rgba(0, 102, 153, 0.8)', lineWidth: 1, opacity: 0.2 },
                // color: '#006699',
                bars: { show: true, fill: true, fillColor: 'rgba(254, 197, 16, 0.8)', lineWidth: 1, opacity: 0.2 },
                color: '#d29124',

                shadowSize: 1
            }
        ], {
            grid: {
                show: true,
                backgroundColor: '#dddddd'
            },
            legend: {
                position: "nw"
            },
            colors: ['red', 'blue', 'red', 'green', 'yellow', 'red', 'blue'],
            xaxis: {
                ticks: [
                    <?php

                        $i   = 1.5;
                        $sep = '';
                        ksort($aBandePassante);
                        foreach ($aBandePassante as $date => $bp) {
                            //$date = new Date($date.'-01');
                            $maDate = $date.'-01';
                            if ($mode != 'month') {
                                echo $sep, '[', $i, ', \'',  maDateMA($maDate),'\']';
                            } else {
                                echo $sep, '[', $i, ', \'', maDateJM($maDate),'\']';
                                //echo $sep, '[', $i, ', \'', $date->format('%d/%m'),'\']';
                            }
                            $sep = ', ';
                            $i++;
                        }

                    ?>
                ]
            }
        });


    })



</script>

<?php if (\NC::isIpIn()) { ?>
<hr />

<!--Debug $_GET : --><?php //d($_GET); ?>
<!--Debug $nowTime : --><?php //d($nowTime); ?>
<!--Debug $debugNowTime : --><?php //d($debugNowTime); ?>
<!--Debug $aBandePassante : --><?php //d($aBandePassante); ?>
<!--Debug $aEspaceDisque : --><?php //d($aEspaceDisque); ?>
<!--Debug $aMails : --><?php //d($aMails); ?>
<!---->
<hr />

<?php } ?>
