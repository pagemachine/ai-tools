<?php

declare(strict_types = 1);

namespace Pagemachine\AItools\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\Validate;

class Aiimage extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string $description
     */
    protected $description;

    /**
     * @var string $imagesnumber
     * Validate("notEmpty")
     */
    protected $imagesnumber;

    /**
     * @var string $resolution
     * @TYPO3\CMS\Extbase\Annotation\Validate("notEmpty")
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
     */
    public function setFile(array $file)
    {
        $this->file = $file;
    }

}
