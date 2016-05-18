<?php

namespace NC;


class Controller_Phpinfo extends \Nos\Controller
{
    public function action_index()
    {
        phpinfo();
    }
}
