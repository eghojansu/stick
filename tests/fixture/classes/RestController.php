<?php

namespace Fixture;

class RestController
{
    public function all()
    {
        return 'rest all';
    }

    public function create()
    {
        return 'rest create';
    }

    public function get($item)
    {
        return 'rest get '.$item;
    }

    public function put($item)
    {
        return 'rest put '.$item;
    }

    public function delete($item)
    {
        return 'rest delete '.$item;
    }
}
