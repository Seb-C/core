<?php

$cmd_convert = \Config::get('cmd_convert');

return array(
    'driver'          => 'imagemagicknc',
    'imagemagick_dir' => dirname($cmd_convert).'/',
);