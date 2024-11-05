<?php

declare(strict_types=1);

namespace Pagemachine\AItools\Controller\Backend;

use Pagemachine\AItools\Service\ImageMetaDataService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CreditsController extends ActionController
{
    public function __construct(
        private readonly ImageMetaDataService $imageMetaDataService,
        private readonly ResourceFactory $resourceFactory,
    ) {
    }

    protected function imageRecognition(ServerRequestInterface $request): string
    {
        $fileIdentifier = $this->getParameter($request, 'fileIdentifier');
        if ($fileIdentifier) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($fileIdentifier);
            if ($fileObject instanceof FileInterface) {
                if ($fileObject->getType() !== AbstractFile::FILETYPE_IMAGE) {
                    return '';
                }

                return $this->imageMetaDataService->priceForImageDescription($fileObject, $this->getParameter($request, 'textPrompt'));
            }
        }

        return '';
    }


    public function ajaxCreditsAction(ServerRequestInterface $request): ResponseInterface
    {
        $text = '';
        try {
            switch ($this->getParameter($request, 'type')) {
                case 'imageRecognition':
                    $text = $this->imageRecognition($request);
                    break;
                case 'remaining':
                    break;
                default:
                    throw new \Exception('Invalid type');
            }
        } catch (\Exception $e) {
            return $this->responseFactory->createResponse()
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR)));
        }

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(json_encode(['credits' => $text])));
    }

    protected function getParameter(ServerRequestInterface $request, string $key): string
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        return $parsedBody[$key] ?? $queryParams[$key] ?? '';
    }
}
