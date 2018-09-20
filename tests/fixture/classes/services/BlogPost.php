<?php

declare(strict_types=1);

namespace FixtureServices;

class BlogPost
{
    private $title;
    private $postNow;
    private $postedDate;
    private $author;

    public function __construct($title, $postNow, \DateTime $postedDate, Author $author)
    {
        $this->title = $title;
        $this->postNow = $postNow;
        $this->postedDate = $postedDate;
        $this->author = $author;
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

    public function getPostedDate()
    {
        return $this->postedDate;
    }

    public function getAuthor()
    {
        return $this->author;
    }
}
