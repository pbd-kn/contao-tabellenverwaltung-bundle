<?php
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [\PBDKN\TabellenverwaltungBundle\InsertTags\TableInsertTags::class, 'replace'];
$tableManagementModules = [
    'directories' => ['tables' => ['tl_directory', 'tl_directory_entry']],
    'directory_import' => ['callback' => \PBDKN\TabellenverwaltungBundle\Backend\ImportBackend::class],
    'directory_csv' => ['callback' => \PBDKN\TabellenverwaltungBundle\Backend\CsvBackend::class],
];
// Keep the existing module identifiers, URLs and user permissions.
$backendGroups = [];
foreach ($GLOBALS['BE_MOD'] as $group => $modules) {
    $backendGroups[$group] = $modules;
    if ($group === 'content') { $backendGroups['table_management'] = $tableManagementModules; }
}
$backendGroups['table_management'] = $tableManagementModules;
$GLOBALS['BE_MOD'] = $backendGroups;
unset($tableManagementModules, $backendGroups, $group, $modules);
$GLOBALS['TL_CTE']['texts']['directory_table'] = \PBDKN\TabellenverwaltungBundle\Content\DirectoryTable::class;
$GLOBALS['FE_MOD']['miscellaneous']['directory_listing'] = \PBDKN\TabellenverwaltungBundle\Module\DirectoryModule::class;
