<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTypoScriptSetup(
    'module.tx_dashboard.view.templateRootPaths.20 = EXT:solr_file_indexer_admin/Resources/Private/Templates'
);