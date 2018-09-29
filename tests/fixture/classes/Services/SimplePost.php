<?php

declare(strict_types=1);

namespace Fixture\Services;

class SimplePost
{
    private $title;
    private $postNow;

    public function __construct($title, $postNow)
    {
        $this->title = $title;
        $this->postNow = $postNow;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }
}
