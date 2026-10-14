<?php
$GLOBALS['BE_MOD']['content']['directories'] = ['tables' => ['tl_directory', 'tl_directory_entry']];
$GLOBALS['BE_MOD']['content']['directory_import'] = ['callback' => \PBDKN\TabellenverwaltungBundle\Backend\ImportBackend::class];
$GLOBALS['TL_CTE']['texts']['directory_table'] = \PBDKN\TabellenverwaltungBundle\Content\DirectoryTable::class;
$GLOBALS['FE_MOD']['miscellaneous']['directory_listing'] = \PBDKN\TabellenverwaltungBundle\Module\DirectoryModule::class;
