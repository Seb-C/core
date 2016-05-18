<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="http://code.jquery.com/jquery-1.8.0.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('.relancer').live('click', function() {
                var tr = $(this).parents('tr');
                $.get('?noviuscloud=check&ajax='+$(this).attr('data'), function(data) {
                    tr.fadeOut('normal', function() {
                        tr.replaceWith(data);
                    });
                });
                return false;
            });
        });
    </script>
    <style type="text/css">
        body {
            background-color: #F3F3F3;
            margin: 0;
        }
        h1 {
            background-color: #98b8e8;
            font-size: 1.4em;
            margin: 0 0 15px;
            padding: 10px;
        }

        table {
            border-spacing: 0;
            border-collapse: collapse;
            margin: 5px auto 20px;
            width: 98%;
        }

        th {
            font-size: 1.1em;
            padding: 25px 0 5px;
            text-align: left;
        }

        td {
            border: 1px solid #333;
            padding: 10px 12px;
        }

        td.green, td.red, td.info {
            width: 180px;
        }

        td.green, td.red, td.orange {
            font-weight: bold;
            text-align: center;
            width: 200px;
        }

        td.green {
            background-color: #00ff88;
        }

        td.orange {
            background-color: #EF8B3B;
        }

        td.red {
            background-color: #FF5F5F;
        }

        div.bouton {
            margin-top: 60px;
            text-align: center;
        }

        p.red {
            border: 1px solid red;
            background-color: #FFCCCC;
            padding: 3px;
        }

    </style>
</head>
<body>
<h1>Test de configuration Novius Cloud</h1>
<table>

<?php

// Affichage de tous les tests
$currentCategorie = '';
foreach ($tests as $categorie => $categorie_tests) {

    if ($categorie != $currentCategorie) {
        echo '<tr class="tr"><th colspan="2">', $categorie, '</th></tr>';
        $currentCategorie = $categorie;
    }

    foreach ($categorie_tests as $key => $test) {
        echo \View::forge('novius_cloud::check/item', array('key' => $key, 'test' => $test), false);
    }
}

?>
</table>
<h1>Fin des tests</h1>

</body>
</html>