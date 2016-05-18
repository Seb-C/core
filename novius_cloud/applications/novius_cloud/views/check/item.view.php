<?php
$relancer = '<span style="float: right;"><a href="#" class="relancer" data="'.$key.'">[relancer]</a></span>';
echo '<tr>';

if ($test->GetStatus() == 'ok') {
    $msg = 'OK';
    if ($test->GetMsg() <> '') {
        $msg = $test->GetMsg();
    }
    echo '<td><b>', $relancer, $test->GetTestName(), '</b>' , $test->GetTestDescription() ? ('<br><i>'.$test->GetTestDescription().'</i>') : '', '</td>
        <td class="green">', $msg, '</td>';
} elseif (in_array($test->GetStatus(), array('error', 'warning'))) {
    if ($test->GetStatus() == 'error') {
        $msg   = 'ERREUR';
        $class = 'red';
    } else {
        $msg   = 'Warning';
        $class = 'orange';
    }

    if ($test->GetMsg() <> '') {
        $msg = $test->GetMsg();
    }
    echo '<td><b>', $relancer, $test->GetTestName(), '</b>' , $test->GetTestDescription() ? ('<br><i>'.$test->GetTestDescription().'</i>') : '', ($test->GetConsequencesSiErreur() <> '') ? '<p class="red">'.$test->GetConsequencesSiErreur().'</p>' : '', ($test->GetCorrectionSiErreur() <> '') ? '<p><strong>Pour corriger&nbsp;: '.$test->GetCorrectionSiErreur().'</p>' : '', '</td>
        <td class="', $class,'">', $msg, '</td>';
} else {
    echo '<td><b>', $relancer, $test->GetTestName(), '</b>' , $test->GetTestDescription() ? ('<br><i>'.$test->GetTestDescription().'</i>') : '', ($test->GetConsequencesSiErreur() <> '') ? '<p class="red">'.$test->GetConsequencesSiErreur().'</p>' : '', ($test->GetCorrectionSiErreur() <> '') ? '<p><strong>Pour corriger&nbsp;: '.$test->GetCorrectionSiErreur().'</p>' : '', '</td>
        <td class="info">', $msg, '</td>';
}
echo '</tr>';