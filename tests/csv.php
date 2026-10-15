<?php
declare(strict_types=1);
require getenv('BUNDLE_TEST_AUTOLOAD') ?: dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/src/Command/ImportEfgCommand.php';
require_once dirname(__DIR__).'/src/Backend/CsvTransfer.php';
use Doctrine\DBAL\DriverManager;
use PBDKN\TabellenverwaltungBundle\Backend\CsvTransfer;
function check(bool $condition,string $message): void { if (!$condition) { throw new \RuntimeException($message); } }
function rejects(callable $callback,string $message): void {
    try { $callback(); } catch (\RuntimeException $expected) { return; }
    throw new \LogicException($message);
}
$db=DriverManager::getConnection(['driver'=>'pdo_sqlite','memory'=>true]);
$db->executeStatement('CREATE TABLE tl_directory (id INTEGER PRIMARY KEY, title TEXT, columns BLOB)');
$db->executeStatement('CREATE TABLE tl_directory_entry (id INTEGER PRIMARY KEY AUTOINCREMENT, pid INTEGER, tstamp INTEGER, sorting INTEGER, legacyId INTEGER, title TEXT, published TEXT, data BLOB)');
$db->insert('tl_directory',['id'=>1,'title'=>'Firmen','columns'=>serialize([['key'=>'FIRMA','value'=>'Firma'],['key'=>'ORT','value'=>'Ort'],['key'=>'TELEFON','value'=>'Telefon']])]);
$db->insert('tl_directory',['id'=>2,'title'=>'Anderes','columns'=>serialize([['key'=>'FIRMA','value'=>'Firma']])]);
$service=new CsvTransfer($db);
$csv=$service->parse("Firma;Ort;Telefon\r\n\"Müller; GmbH\";\"Konstanz\nBodensee\";+49 123\r\n=SUM(A1);Bonn;0123\r\n");
$mapping=['FIRMA'=>'Firma','ORT'=>'Ort','TELEFON'=>'Telefon'];
$plan=$service->plan(1,$csv,$mapping);
check($plan['new']===2 && $plan['updated']===0,'Wrong preview counts');
check((int)$db->fetchOne('SELECT COUNT(*) FROM tl_directory_entry')===0,'Preview wrote data');
$service->execute($plan);
$rows=$db->fetchAllAssociative('SELECT * FROM tl_directory_entry ORDER BY id');
check($rows[0]['published']==='' && $rows[0]['title']==='Müller; GmbH','New record status/title wrong');
check(array_column(unserialize($rows[0]['data']),'value','key')['ORT']==="Konstanz\nBodensee",'Multiline lost');
$db->update('tl_directory_entry',['published'=>'1'],['id'=>$rows[0]['id']]);
$export=$service->export(1);
check(str_starts_with($export,"\xEF\xBB\xBF"),'Missing BOM');
check(str_contains($export,"'+49 123") && str_contains($export,"'=SUM(A1)"),'Formula protection missing');
$roundtrip=$service->parse($export);
$map=['_id'=>'_id','_title'=>'_title','FIRMA'=>'FIRMA','ORT'=>'ORT','TELEFON'=>'TELEFON'];
$plan=$service->plan(1,$roundtrip,$map);
check($plan['updated']===2 && $plan['new']===0,'Export IDs not used');
$service->execute($plan);
check((int)$db->fetchOne('SELECT COUNT(*) FROM tl_directory_entry')===2,'Roundtrip duplicated records');
check($db->fetchOne('SELECT published FROM tl_directory_entry WHERE id=1')==='1','Publication changed');
check(array_column(unserialize($db->fetchOne('SELECT data FROM tl_directory_entry WHERE id=1')),'value','key')['TELEFON']==='+49 123','Formula escaping changed value');
check($db->fetchOne('SELECT title FROM tl_directory_entry WHERE id=2')==='=SUM(A1)','Formula title roundtrip failed');
$db->update('tl_directory_entry',['title'=>"'=literal apostrophe"],['id'=>2]);
$service->execute($service->plan(1,$service->parse($service->export(1)),$map));
check($db->fetchOne('SELECT title FROM tl_directory_entry WHERE id=2')==="'=literal apostrophe",'Literal apostrophe lost in roundtrip');
$partial=$service->parse("ID,Ort\n1,\n");
$plan=$service->plan(1,$partial,['_id'=>'ID','ORT'=>'Ort']);
$service->execute($plan);
$data=array_column(unserialize($db->fetchOne('SELECT data FROM tl_directory_entry WHERE id=1')),'value','key');
check($data['ORT']==='' && $data['FIRMA']==='Müller; GmbH','Partial update incorrect');
rejects(fn()=>$service->plan(2,$roundtrip,$map),'Cross-directory ID accepted');
rejects(fn()=>$service->plan(1,$service->parse("_id;FIRMA\n1;A\n1;B\n"),['_id'=>'_id','FIRMA'=>'FIRMA']),'Duplicate ID accepted');
rejects(fn()=>$service->parse("Firma;Firma\nA;B\n"),'Duplicate headings accepted');
rejects(fn()=>$service->parse("Firma;Ort\nA\n"),'Ragged CSV accepted');
rejects(fn()=>$service->parse(str_repeat('x',CsvTransfer::MAX_BYTES+1)),'Oversize accepted');
$western=$service->parse(mb_convert_encoding("Firma\nMüller\n",'Windows-1252','UTF-8'));
check($western['rows'][0]['Firma']==='Müller','Windows encoding broken');
$tab=$service->parse("Firma\tOrt\nA\tB\n"); check(count($tab['header'])===2,'Tab auto detection failed');
// A stale row aborts all changes, including new rows processed before it.
$plan=$service->plan(1,$service->parse("ID;Firma\n;Neu\n1;Geändert\n"),['_id'=>'ID','FIRMA'=>'Firma']);
$db->update('tl_directory_entry',['title'=>'Parallel bearbeitet'],['id'=>1]);
rejects(fn()=>$service->execute($plan),'Stale update accepted');
check((int)$db->fetchOne('SELECT COUNT(*) FROM tl_directory_entry')===2,'Failed import was not rolled back');
$plan=$service->plan(1,$csv,$mapping);
$db->update('tl_directory',['columns'=>serialize([['key'=>'X','value'=>'X']])],['id'=>1]);
rejects(fn()=>$service->execute($plan),'Changed schema accepted');
echo "CSV checks passed: preview, insert, update, publication, UTF-8/Windows, delimiters, multiline, lossless safe export, partial update, IDs, validation, stale data and atomic rollback.\n";
