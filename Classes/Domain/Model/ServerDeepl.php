<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Model;

class ServerDeepl extends Server
{
    protected string $endpoint;
    protected string $formality;

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function getFormality(): string
    {
        return $this->formality;
    }

    public function setFormality(string $formality): void
    {
        $this->formality = $formality;
    }
}
