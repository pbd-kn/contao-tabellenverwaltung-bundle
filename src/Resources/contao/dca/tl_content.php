<?php
$GLOBALS['TL_DCA']['tl_content']['palettes']['directory_table'] = '{type_legend},type,headline;{directory_legend},directoryId,directoryPresentation,directoryDetailPage,directoryDisplayColumns,directorySearchColumns,directoryOrder,directoryDetailColumns;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['fields']['directoryId'] = [
    'label' => ['Verzeichnis', 'Anzuzeigende Tabelle'], 'inputType' => 'select',
    'foreignKey' => 'tl_directory.title', 'eval' => ['mandatory' => true, 'includeBlankOption' => true],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_content']['fields']['directoryPresentation'] = ['label' => ['Darstellung', 'Tabelle, Partnerkarten oder Detailansicht'], 'inputType' => 'select', 'options' => ['table' => 'Tabelle', 'cards' => 'Partnerkarten', 'detail' => 'Details'], 'eval' => ['isAssociative' => true], 'default' => 'table', 'sql' => "varchar(16) NOT NULL default 'table'"];
$GLOBALS['TL_DCA']['tl_content']['fields']['directoryDetailPage'] = ['label' => ['Detailseite', 'Optionales Ziel für Links auf Partnerkarten'], 'inputType' => 'pageTree', 'eval' => ['fieldType' => 'radio'], 'sql' => "int(10) unsigned NOT NULL default '0'"];
foreach ([
    'directoryDisplayColumns' => ['Sichtbare Spalten', 'Feldschlüssel durch Komma getrennt, in gewünschter Reihenfolge. Leer: alle Spalten.'],
    'directorySearchColumns' => ['Durchsuchbare Spalten', 'Feldschlüssel durch Komma getrennt. Leer: keine Suchfunktion.'],
    'directoryOrder' => ['Sortierung', 'Feldschlüssel durch Komma getrennt, aufsteigend. Leer: Backend-Reihenfolge.'],
    'directoryDetailColumns' => ['Detailfelder', 'Feldschlüssel durch Komma getrennt. Erscheinen als aufklappbare Details pro Eintrag.'],
] as $name => $label) {
    $GLOBALS['TL_DCA']['tl_content']['fields'][$name] = ['label' => $label, 'inputType' => 'text', 'eval' => ['maxlength' => 2048], 'sql' => 'text NULL'];
}
