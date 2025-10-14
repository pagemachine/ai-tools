<?php

namespace Pagemachine\AItools\Placeholder;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileCategoriesPlaceholder extends PlaceholderAbstract
{
    public function getValue(): string
    {
        if (!$this->file || !$this->file->hasProperty('categories')) {
            return '';
        }

        if (!$this->file->getProperty('uid')) {
            return '';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');

        $categories = $queryBuilder
            ->select('c.title')
            ->from('sys_category', 'c')
            ->join(
                'c',
                'sys_category_record_mm',
                'mm',
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->quoteIdentifier('c.uid')),
                    $queryBuilder->expr()->eq('mm.tablenames', $queryBuilder->createNamedParameter('sys_file_metadata')),
                    $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('categories')),
                    $queryBuilder->expr()->eq('mm.uid_foreign', $queryBuilder->createNamedParameter((int) $this->file->getProperty('uid')))
                )
            )
            ->orderBy('mm.sorting_foreign', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        $categoryNames = array_map(fn($cat) => $cat['title'], $categories);

        return implode(', ', $categoryNames);
    }

    public function getExampleValue(): string
    {
        return 'Cars, Vehicles, Transportation';
    }
}
