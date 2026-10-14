<?php
$GLOBALS['BE_MOD']['content']['directories'] = ['tables' => ['tl_directory', 'tl_directory_entry']];
$GLOBALS['TL_CTE']['texts']['directory_table'] = \PBDKN\TabellenverwaltungBundle\Content\DirectoryTable::class;
$GLOBALS['FE_MOD']['miscellaneous']['directory_listing'] = \PBDKN\TabellenverwaltungBundle\Module\DirectoryModule::class;
