# AI Tools ![CI](https://github.com/pagemachine/ai-tools/workflows/CI/badge.svg)

TYPO3 extension that uses AI to generate and translate image alt text automatically.

**Requires:** TYPO3 12.4–14.4 · PHP 8.2+

## Installation

```bash
composer require pagemachine/ai-tools
```

Also available from the [TYPO3 Extension Repository](https://extensions.typo3.org/extension/ai_tools) and [GitHub releases](https://github.com/pagemachine/ai-tools/releases).

## Features

### Alt text generation

Right-click any image in the File List and choose **Generate A.I. Metadata** to open the generation modal. The extension sends the image to the configured AI server and writes the result back to the file's metadata.

- Generate alt text in any site language
- Auto-translate to all other site languages in one step
- Customize the prompt used for generation (AI Tools > Prompts)
- Use **Generate All** on a folder to process multiple images at once

### Translation

Supported providers: DeepL and Google Translate. The active provider is configurable per server.

### Prompt management

Go to **AI Tools > Prompts** to manage the prompts used for image description. Set one as the default; it will be pre-selected in the generation modal.

### Server configuration

Go to **AI Tools > Settings** (admin only) to configure AI servers:

- API key (get a free key at [aigude.io](https://aigude.io/en/Products/))
- Multiple servers supported; one set as default

### Supported languages

AiGude generates descriptions natively in these languages without a translation step:

English, German, Spanish, French, Italian, Portuguese, Dutch, Japanese, Korean, Arabic, Chinese, Russian, Hindi, Turkish, Hebrew

For other site languages, write the prompt in any of the supported languages (e.g. English or German). The description is generated in the prompt's language and then translated to the target site language using the configured translation provider.

### Storage-scoped configuration

Useful for multi-site setups where each site has its own storage and API budget. Each file storage can be configured independently under **System > File Storages > AI Tools tab**:

- **Enable/disable** AI Tools for that storage
- **Override AI server:** route API calls for that storage to a specific server instead of the default

## Testing

```bash
docker compose run --rm app composer build
```
