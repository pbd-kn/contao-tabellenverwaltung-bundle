<?php
declare(strict_types=1);
require getenv('BUNDLE_TEST_AUTOLOAD') ?: dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__).'/src/Command/ImportEfgCommand.php';
use Doctrine\DBAL\DriverManager;
use PBDKN\TabellenverwaltungBundle\Command\ImportEfgCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

function check(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
$db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
foreach ([
    'CREATE TABLE tl_formdata (id INTEGER PRIMARY KEY, form TEXT, sorting INTEGER)',
    'CREATE TABLE tl_formdata_details (id INTEGER PRIMARY KEY, pid INTEGER, ff_name TEXT, value TEXT, sorting INTEGER)',
    'CREATE TABLE tl_directory (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, tstamp INTEGER, columns BLOB)',
    'CREATE TABLE tl_directory_entry (id INTEGER PRIMARY KEY AUTOINCREMENT, pid INTEGER, tstamp INTEGER, sorting INTEGER, legacyId INTEGER, title TEXT, published TEXT, data BLOB)',
] as $sql) { $db->executeStatement($sql); }
$db->insert('tl_formdata', ['id' => 1, 'form' => 'Mitglieder', 'sorting' => 128]);
$db->insert('tl_formdata_details', ['id' => 1, 'pid' => 1, 'ff_name' => 'firma', 'value' => 'Müller & Söhne', 'sorting' => 128]);
$command = new ImportEfgCommand($db);
$run = static function (bool $execute) use ($command): int {
    $arguments = ['form' => 'Mitglieder', 'title' => 'Mitgliedsbetriebe'];
    if ($execute) { $arguments['--execute'] = true; }
    return $command->run(new ArrayInput($arguments), new BufferedOutput());
};
check($run(false) === 0, 'Preview failed');
check((int) $db->fetchOne('SELECT COUNT(*) FROM tl_directory') === 0, 'Preview wrote data');
check($run(true) === 0, 'Import failed');
$row = $db->fetchAssociative('SELECT * FROM tl_directory_entry');
check($row['published'] === '', 'Imported record is published');
check((int) $row['legacyId'] === 1, 'Source identity lost');
check(unserialize($row['data'], ['allowed_classes' => false])[0]['value'] === 'Müller & Söhne', 'Value changed');
try { $run(true); throw new LogicException('Duplicate was accepted'); }
catch (RuntimeException $exception) { check(str_contains($exception->getMessage(), 'existiert'), 'Unexpected duplicate error'); }
check((int) $db->fetchOne('SELECT COUNT(*) FROM tl_directory') === 1, 'Duplicate import changed target');
check($db->fetchOne('SELECT value FROM tl_formdata_details WHERE id=1') === 'Müller & Söhne', 'Source changed');
echo "Import checks passed: preview, copy, UTF-8, unpublished state, duplicate protection, source preservation.\n";
check(ImportEfgCommand::entryTitle([['key' => 'MOBIL', 'value' => ''], ['key' => 'FIRMA', 'value' => 'Firma Ä']], 2) === 'Firma Ä', 'Company title depends on field order');
check(ImportEfgCommand::entryTitle([['key' => 'NAME', 'value' => 'Müller'], ['key' => 'VORNAME', 'value' => 'Anna']], 3) === 'Anna Müller', 'Person fallback failed');
check(ImportEfgCommand::entryTitle([], 4) === 'Eintrag 4', 'Empty fallback failed');
$db->update('tl_directory_entry', ['title' => '', 'data' => serialize([['key' => 'MOBIL', 'value' => ''], ['key' => 'FIRMA', 'value' => 'Firma Ä']])], ['id' => $row['id']]);
$repairArgs = ['form' => 'Mitglieder', 'title' => 'Mitgliedsbetriebe', '--repair-titles' => true];
$command->run(new ArrayInput($repairArgs), new BufferedOutput());
check($db->fetchOne('SELECT title FROM tl_directory_entry') === '', 'Repair preview wrote data');
$repairArgs['--execute'] = true;
$before = $db->fetchAssociative('SELECT data,published FROM tl_directory_entry');
$command->run(new ArrayInput($repairArgs), new BufferedOutput());
check($db->fetchOne('SELECT title FROM tl_directory_entry') === 'Firma Ä', 'Repair failed');
check($db->fetchAssociative('SELECT data,published FROM tl_directory_entry') === $before, 'Repair changed field data');
$db->update('tl_directory_entry', ['title' => 'Eigene Bezeichnung'], ['id' => $row['id']]);
$command->run(new ArrayInput($repairArgs), new BufferedOutput());
check($db->fetchOne('SELECT title FROM tl_directory_entry') === 'Eigene Bezeichnung', 'Manual title overwritten');
echo "Title checks passed: field order, fallbacks, repair preview, preservation of data and manual titles.\n";
