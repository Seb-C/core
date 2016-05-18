<?php

return array(
    'enabled_types' => array(
        /**
         * Pas d'update des metadata en prod !
         */
        'metadata' => Fuel::$env == \Fuel::DEVELOPMENT,
    )
);