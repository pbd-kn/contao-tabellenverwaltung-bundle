<?php
use Contao\DC_Table;
use PBDKN\TabellenverwaltungBundle\Backend\EntryData;
$GLOBALS['TL_DCA']['tl_directory_entry'] = [
    'config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_directory', 'enableVersioning' => true, 'sql' => ['keys' => ['id' => 'primary', 'pid' => 'index', 'pid,legacyId' => 'unique']]],
    'list' => [
        'sorting' => ['mode' => 4, 'fields' => ['sorting'], 'headerFields' => ['title'], 'panelLayout' => 'search', 'child_record_callback' => [EntryData::class, 'label']],
        'global_operations' => [
            'all' => [
                'label' => ['Mehrere bearbeiten', 'Mehrere Einträge auswählen und gemeinsam bearbeiten'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => ['href' => 'act=edit', 'icon' => 'edit.svg'],
            'copy' => ['href' => 'act=copy', 'icon' => 'copy.svg'],
            'delete' => ['href' => 'act=delete', 'icon' => 'delete.svg', 'attributes' => 'onclick="if(!confirm(\'Eintrag löschen?\'))return false;"'],
            'show' => ['href' => 'act=show', 'icon' => 'show.svg'],
        ],
    ],
    'palettes' => ['default' => 'title,published,data'],
    'fields' => [
        'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'pid' => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'sorting' => ['sql' => "int(10) unsigned NOT NULL default '0'"],
        'legacyId' => ['sql' => 'int(10) unsigned NULL'],
        'title' => ['label' => ['Bezeichnung', 'Name zur Suche im Backend'], 'inputType' => 'text', 'search' => true, 'eval' => ['mandatory' => true, 'maxlength' => 255], 'sql' => "varchar(255) NOT NULL default ''"],
        'published' => ['label' => ['Veröffentlicht', 'Auf der Website anzeigen'], 'inputType' => 'checkbox', 'sql' => "char(1) NOT NULL default ''"],
        'data' => ['label' => ['Feldwerte', 'Die Schlüssel entsprechen den Spalten der Tabelle. Werte sind Klartext. Lange Werte umbrechen automatisch; die Eingabefelder lassen sich nach unten vergrößern.'], 'inputType' => 'directoryEntryValues', 'load_callback' => [[EntryData::class, 'load']], 'save_callback' => [[EntryData::class, 'save']], 'sql' => 'blob NULL'],
    ],
];
