<?php
use Contao\DC_Table;
$GLOBALS['TL_DCA']['tl_directory'] = [
    'config' => ['dataContainer' => DC_Table::class, 'ctable' => ['tl_directory_entry'], 'enableVersioning' => true, 'sql' => ['keys' => ['id' => 'primary']]],
    'list' => [
        'sorting' => ['mode' => 1, 'fields' => ['title'], 'flag' => 1, 'panelLayout' => 'search'],
        'label' => ['fields' => ['title'], 'format' => '%s'],
        'operations' => [
            'entries' => ['label' => ['Einträge', 'Einträge verwalten'], 'href' => 'table=tl_directory_entry', 'icon' => 'articles.svg'],
            'edit' => ['label' => ['Einstellungen', 'Tabelle bearbeiten'], 'href' => 'act=edit', 'icon' => 'edit.svg'],
            'delete' => ['label' => ['Löschen', 'Tabelle löschen'], 'href' => 'act=delete', 'icon' => 'delete.svg', 'attributes' => 'onclick="if(!confirm(\'Tabelle und Einträge löschen?\'))return false;"'],
            'show' => ['href' => 'act=show', 'icon' => 'show.svg'],
        ],
    ],
    'palettes' => ['default' => 'title,columns'],
    'fields' => [
        'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'title' => ['label' => ['Name', 'Zum Beispiel Mitgliedsbetriebe'], 'inputType' => 'text', 'search' => true, 'eval' => ['mandatory' => true, 'maxlength' => 255], 'sql' => "varchar(255) NOT NULL default ''"],
        'columns' => ['label' => ['Spalten', 'Schlüssel: eindeutiger Feldname; Wert: sichtbare Spaltenüberschrift. Bestehende Schlüssel nicht ändern.'], 'inputType' => 'keyValueWizard', 'eval' => ['mandatory' => true], 'save_callback' => [[\PBDKN\TabellenverwaltungBundle\Backend\EntryData::class, 'validateColumns']], 'sql' => 'blob NULL'],
    ],
];
