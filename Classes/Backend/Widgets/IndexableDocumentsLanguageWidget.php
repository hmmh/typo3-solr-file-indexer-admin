<?php
declare(strict_types = 1);
namespace HMMH\SolrFileIndexerAdmin\Backend\Widgets;

use HMMH\SolrFileIndexerAdmin\Service\Widgets\IndexingService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

/**
 * This widget will show the number of pages
 */
class IndexableDocumentsLanguageWidget implements WidgetInterface, AdditionalCssInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;

    /**
     * IndexableDocumentsLanguageWidget constructor.
     *
     * @param WidgetConfigurationInterface $configuration
     * @param array                        $options
     */
    public function __construct(
        private readonly WidgetConfigurationInterface $configuration,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly array $options = [],
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getCssFiles(): array
    {
        return ['EXT:solr_file_indexer_admin/Resources/Public/Css/widget.css'];
    }

    /**
     * @inheritDoc
     */
    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request);

        /** @var IndexingService $widgetService */
        $widgetService = GeneralUtility::makeInstance(IndexingService::class);

        $view->assignMultiple([
            'icon' => $this->options['icon'] ?? null,
            'title' => $this->options['title'] ?? null,
            'roots' => $widgetService->getIndexableDocuments(),
            'options' => $this->options,
            'configuration' => $this->configuration
        ]);

        return $view->render('Widget/IndexableDocumentsLanguageWidget');
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return [];
    }
}
