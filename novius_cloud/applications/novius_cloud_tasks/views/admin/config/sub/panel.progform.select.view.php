<?php

/**
 * Vue permettant d'afficher un select au niveau du panel de programmation
 *
 * @var $title  string  Le titre du select, affiché
 * @var $name   string  L'attribut name du select
 * @var $values array   La liste des valeurs
 */

$isAssoc = \Arr::is_assoc($values);

$savedValues = array();
if (isset($task['prog'][$name])) {
    if ($task['prog'][$name] == '*') {
        $savedValues = true;
    } else {
        $savedValues = explode(',', $task['prog'][$name]);
    }
}

echo '<label>', $title, '<br /><select name="', $name, '[]" multiple="multiple">';

foreach ($values as $optionValue => $optionTitle) {
    if (!$isAssoc) {
        $optionValue = $optionTitle;
    }
    echo '<option value="', $optionValue, '" ';
    if ($savedValues === true || in_array($optionValue, $savedValues)) {
        echo 'selected="selected"';
    }
    echo '>', $optionTitle, '</option>';
}

echo '</select>

<p class="allnone">
    <a href="#" class="js_selectall">Tout</a> /
    <a href="#" class="js_selectnone">Rien</a>
</p>

</label>';