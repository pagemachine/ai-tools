<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Domain\Model;

class Aiimage extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string $description
     */
    protected $description;

    /**
     * @var string $imagesnumber
     */
    protected $imagesnumber;

    /**
     * @var string $resolution
     */
    protected $resolution;

    /** @var array */
    protected $file = [];

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getImagesnumber()
    {
        return $this->imagesnumber;
    }

    /**
     * @param string $description
     */
    public function setImagesnumber(string $imagesnumber)
    {
        $this->imagesnumber = $imagesnumber;
    }

    /**
     * @return string
     */
    public function getResolution()
    {
        return $this->resolution;
    }

    /**
     * @param string $resolution
     */
    public function setResolution(string $resolution)
    {
        $this->resolution = $resolution;
    }

    /**
     * @return array
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param array $file
     */
    public function setFile(array $file)
    {
        $this->file = $file;
    }

}
