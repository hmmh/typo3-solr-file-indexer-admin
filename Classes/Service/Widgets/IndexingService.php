<?php
declare(strict_types = 1);
namespace HMMH\SolrFileIndexerAdmin\Service\Widgets;

use ApacheSolrForTypo3\Solr\Domain\Search\Query\ParameterBuilder\ReturnFields;
use ApacheSolrForTypo3\Solr\Domain\Search\Query\QueryBuilder;
use ApacheSolrForTypo3\Solr\System\Solr\ResponseAdapter;
use HMMH\SolrFileIndexer\Service\ConnectionAdapter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexingService
{
    /**
     * @return array
     */
    public function getIndexableDocuments(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_solrfileindexer_items');

        $res = $queryBuilder->select('uid', 'root', 'sys_language_uid')
            ->from('tx_solrfileindexer_items')
            ->executeQuery();

        $result = $res->fetchAllAssociative();

        $roots = $this->getSiteRoots();

        foreach ($result as $indexItem) {
            $siteRoot = $indexItem['root'];
            if (isset($roots[$siteRoot], $roots[$siteRoot]['languages'][$indexItem['sys_language_uid']])) {
                $roots[$siteRoot]['languages'][$indexItem['sys_language_uid']]['count']++;
            }
        }

        ksort($roots);

        return $roots;
    }

    /**
     * @return array
     */
    public function getIndexedDocuments(): array
    {
        $cores = [];

        try {
            $connections = GeneralUtility::makeInstance(ConnectionAdapter::class)
                ->getConnectionManager()
                ->getAllConnections();
        } catch (\ApacheSolrForTypo3\Solr\NoSolrConnectionFoundException $e) {
            return $cores;
        }

        foreach ($connections as $connection) {
            /** @var \ApacheSolrForTypo3\Solr\System\Solr\SolrConnection $connection */
            $readService = $connection->getReadService();
            if ($readService->ping()) {
                // @extensionScannerIgnoreLine
                $coreOptions = $readService->getPrimaryEndpoint()->getOptions();
                $hash = md5(serialize($coreOptions));
                if (!isset($readConnections[$hash])) {
                    $readConnections[$hash] = true;
                    $queryBuilder = GeneralUtility::makeInstance(QueryBuilder::class);
                    $searchQuery = $queryBuilder->newSearchQuery('');
                    $query = $searchQuery->useQueryString('*:*')
                        ->useFilter('type:sys_file_metadata')
                        ->useReturnFields(ReturnFields::fromString('*'))
                        ->getQuery();
                    $query->setRows(0);
                    $response = $readService->search($query);
                    if ($response instanceof ResponseAdapter) {
                        $data = $response->getParsedData();
                        // @extensionScannerIgnoreLine
                        if (isset($data->response->numFound)) {
                            $cores[] = [
                                'options' => $coreOptions,
                                // @extensionScannerIgnoreLine
                                'numFound' => (int)$data->response->numFound
                            ];
                        }
                    }
                }
            }
        }

        return $cores;
    }

    /**
     * @return array
     */
    protected function getSiteRoots(): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $sites = $siteFinder->getAllSites();

        $roots = [];

        foreach ($sites as $site) {
            /** @var \TYPO3\CMS\Core\Site\Entity\Site $site */
            $roots[$site->getRootPageId()] = [
                'host' => (string)$site->getBase(),
                'languages' => $this->getSiteLanguages($site->getLanguages())
            ];
        }

        return $roots;
    }

    /**
     * @param array $languages
     *
     * @return array
     */
    protected function getSiteLanguages(array $languages): array
    {
        $lang = [];

        foreach ($languages as $language) {
            /** @var \TYPO3\CMS\Core\Site\Entity\SiteLanguage $language */
            // @extensionScannerIgnoreLine
            $lang[$language->getLanguageId()] = [
                'title' => $language->getTitle(),
                'count' => 0
            ];
        }

        return $lang;
    }
}
