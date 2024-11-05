<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Model;

class ServerCustom extends Server
{
    protected string $username;
    protected string $password;
    protected string $imageurl;
    protected string $translationurl;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getImageurl(): string
    {
        return $this->imageurl;
    }

    public function setImageurl(string $imageurl): void
    {
        $this->imageurl = $imageurl;
    }

    public function getTranslationurl(): string
    {
        return $this->translationurl;
    }

    public function setTranslationurl(string $translationurl): void
    {
        $this->translationurl = $translationurl;
    }
}
