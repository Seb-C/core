<?php

namespace NC;

class Controller_Index extends \Nos\Controller
{
    public function action_index()
    {
        return \View::forge('novius_cloud::index/liens');
    }
}