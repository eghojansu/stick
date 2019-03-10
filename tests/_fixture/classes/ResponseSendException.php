<?php

declare(strict_types=1);

namespace Fixture;

use Fal\Stick\Web\Response;

class ResponseSendException extends Response
{
    /**
     * {@inheritdoc}
     */
    public function send(): Response
    {
        throw new \LogicException('I am an exception.');
    }
}
