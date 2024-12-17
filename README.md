# AI Tools ![CI](https://github.com/pagemachine/ai-tools/workflows/CI/badge.svg)

A TYPO3 extension that leverages artificial intelligence to enhance content and accessibility.

## Documentation

The full documentation can be found [here](https://pagemachine.github.io/ai-tools/)

## Installation

This extension is installable from various sources:

1. Via [Composer](https://packagist.org/packages/pagemachine/ai-tools):

        composer require pagemachine/ai-tools

2. From the [TYPO3 Extension Repository](https://extensions.typo3.org/extension/ai_tools)
3. From [Github](https://github.com/pagemachine/ai-tools/releases)

## Features

### Image Alt Tag Generation
* Automatically generates meaningful alt tags for images using AI
* Supports generation in multiple languages:
    * Create alt tags in any supported language
    * Translate existing alt tags to different languages
* Improves website accessibility and SEO
* Prompt management through the TYPO3 backend


## Testing

All tests can be executed and all assets generated with the shipped Docker Compose definition:

    docker compose run --rm app composer build
