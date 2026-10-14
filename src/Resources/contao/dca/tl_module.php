<?php
\Contao\Controller::loadDataContainer('tl_content');
$GLOBALS['TL_DCA']['tl_module']['palettes']['directory_listing'] = '{title_legend},name,headline,type;{directory_legend},directoryId,directoryPresentation,directoryDetailPage,directoryDisplayColumns,directorySearchColumns,directoryOrder,directoryDetailColumns;{protected_legend:hide},protected;{expert_legend:hide},cssID';
foreach (['directoryId', 'directoryPresentation', 'directoryDetailPage', 'directoryDisplayColumns', 'directorySearchColumns', 'directoryOrder', 'directoryDetailColumns'] as $field) {
    $GLOBALS['TL_DCA']['tl_module']['fields'][$field] = $GLOBALS['TL_DCA']['tl_content']['fields'][$field];
}
