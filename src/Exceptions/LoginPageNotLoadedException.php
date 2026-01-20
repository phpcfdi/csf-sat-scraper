<?php

declare(strict_types=1);

namespace PhpCfdi\CsfSatScraper\Exceptions;

class LoginPageNotLoadedException extends SATException
{
    protected ?string $html = null;

    public function __construct(string $message, string $html)
    {
        parent::__construct($message);
        $this->html = $html;
    }

    public function getHtml(): ?string
    {
        // leave this getter since this is common on Exceptions
        return $this->html;
    }
}
