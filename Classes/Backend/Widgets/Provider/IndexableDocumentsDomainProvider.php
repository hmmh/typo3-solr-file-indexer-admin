<?php
declare(strict_types = 1);
namespace HMMH\SolrFileIndexerAdmin\Backend\Widgets\Provider;

use HMMH\SolrFileIndexerAdmin\Service\Widgets\IndexingService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

/**
 * Class IndexableDocumentProvider
 *
 * @package HMMH\SolrFileIndexer\Backend\Widgets\Provider
 */
class IndexableDocumentsDomainProvider implements ChartDataProviderInterface
{
    /**
     * @var array
     */
    protected $chartColors = ['#ff8700', '#a4276a', '#1a568f', '#4c7e3a', '#69bbb5', '#fe5e51', '#414c50', '#36abb5'];

    /**
     * @inheritDoc
     */
    public function getChartData(): array
    {
        $labels = [];
        $data = [];
        $backgroundColor = [];
        $color = 0;

        /** @var IndexingService $widgetService */
        $widgetService = GeneralUtility::makeInstance(IndexingService::class);

        $roots = $widgetService->getIndexableDocuments();
        foreach ($roots as $root) {
            $labels[] = $root['host'];
            $backgroundColor[] = $this->chartColors[$color];
            $tmpCount = 0;
            foreach ($root['languages'] as $language) {
                if ($language['count'] > 0) {
                    $tmpCount += $language['count'];
                }
            }

            $data[] = $tmpCount;

            $color++;

            if ($color >= count($this->chartColors)) {
                $color = 0;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $this->getLanguageService()->sL('LLL:EXT:solr_file_indexer_admin/Resources/Private/Language/locallang_db.xlf:widgets.total'),
                    'backgroundColor' => $backgroundColor,
                    'data' => $data
                ]
            ],
        ];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
