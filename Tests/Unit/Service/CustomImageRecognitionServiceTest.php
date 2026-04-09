<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Tests\Unit\Service;

use Pagemachine\AItools\Service\ImageRecognition\CustomImageRecognitionService;
use PHPUnit\Framework\Attributes\DataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CustomImageRecognitionServiceTest extends UnitTestCase
{
    #[DataProvider('regexProvider')]
    public function testCleanUpRegex(string $input, string $expected)
    {
        $reflection = new \ReflectionClass(CustomImageRecognitionService::class);
        $property = $reflection->getProperty('cleanUpRegex');
        $property->setAccessible(true);
        $regex = $property->getValue();

        $result = preg_replace($regex, '', $input);
        self::assertEquals($expected, trim((string)$result));
    }

    public static function regexProvider(): array
    {
        return [
            ['Certainly! The main subject of the image is a cat.', 'a cat.'],
            ['This image prominently shows a beautiful landscape.', 'a beautiful landscape.'],
            ['The image showcases a modern building.', 'a modern building.'],
            ['Certainly! This image predominantly features a sunset.', 'a sunset.'],
            ['The main subject of the image displays a group of people.', 'a group of people.'],
            ['Certainly! The main subject of the image is a cat!@#.', 'a cat!@#.'],
            ['This image prominently shows a beautiful landscape, with mountains.', 'a beautiful landscape, with mountains.'],
            ["The image showcases a modern building; it's very tall.", "a modern building; it's very tall."],
            ['Certainly! This image predominantly features a sunset... amazing!', 'a sunset... amazing!'],
            ['The main subject of the image displays a group of people - friends.', 'a group of people - friends.'],
            ['The image predominantly showcases a dog.', 'a dog.'],
            ['A dinosaur in the park.', 'A dinosaur in the park.'],
        ];
    }
}
