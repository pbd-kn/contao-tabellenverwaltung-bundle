<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Backend;

use Doctrine\DBAL\Connection;
use PBDKN\TabellenverwaltungBundle\Command\ImportEfgCommand;

final class CsvTransfer
{
    public const MAX_BYTES = 5242880;
    public const MAX_ROWS = 10000;
    public function __construct(private Connection $db) {}

    public function directory(int $id): array
    {
        $directory = $this->db->fetchAssociative('SELECT * FROM tl_directory WHERE id=?', [$id]);
        if (!$directory) { throw new \RuntimeException('Bitte ein vorhandenes Verzeichnis auswählen.'); }
        $directory['columns'] = $this->decode($directory['columns']);
        foreach ($directory['columns'] as $column) {
            if (in_array($column['key'], ['_id', '_title', '_csv_format'], true)) {
                throw new \RuntimeException('Die Feldnamen _id, _title und _csv_format sind für CSV reserviert.');
            }
        }
        return $directory;
    }

    private function decode($value): array
    {
        if (is_resource($value)) { $value = stream_get_contents($value); }
        $rows = is_string($value) ? @unserialize($value, ['allowed_classes'=>false]) : $value;
        return is_array($rows) ? $rows : [];
    }

    public function parse(string $contents, string $delimiter = 'auto'): array
    {
        if (strlen($contents) > self::MAX_BYTES) { throw new \RuntimeException('Die CSV darf höchstens 5 MB groß sein.'); }
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        if (!mb_check_encoding($contents, 'UTF-8')) { $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252'); }
        if (str_contains($contents, "\0")) { throw new \RuntimeException('Bitte eine CSV als UTF-8 oder Windows-1252 speichern, nicht als UTF-16.'); }
        $stream = fopen('php://temp', 'w+b');
        try {
            fwrite($stream, $contents);
            if ($delimiter === 'auto') {
                $best = 0;
                foreach ([';', ',', "\t"] as $candidate) {
                    rewind($stream);
                    $header = fgetcsv($stream, 0, $candidate, '"', '');
                    if (is_array($header) && count($header) > $best) { $best=count($header); $delimiter=$candidate; }
                }
            }
            if (!in_array($delimiter, [';', ',', "\t"], true)) { throw new \RuntimeException('Ungültiges Trennzeichen.'); }
            rewind($stream);
            $header = fgetcsv($stream, 0, $delimiter, '"', '');
            if (!$header) { throw new \RuntimeException('Die CSV ist leer.'); }
            $header = array_map(static fn($v)=>trim((string)$v), $header);
            if (in_array('', $header, true) || count(array_unique($header)) !== count($header)) { throw new \RuntimeException('Spaltenüberschriften müssen eindeutig und ausgefüllt sein.'); }
            $rows = [];
            while (($row=fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                if ($row === [null]) { continue; }
                if (count($row) !== count($header)) { throw new \RuntimeException('CSV-Datensatz '.(count($rows)+1).' hat eine falsche Spaltenanzahl.'); }
                $rows[] = array_combine($header, $row);
                if (count($rows)>self::MAX_ROWS) { throw new \RuntimeException('Höchstens 10.000 Datensätze pro Import.'); }
            }
            if (!$rows) { throw new \RuntimeException('Die CSV enthält keine Datensätze.'); }
            return ['header'=>$header, 'rows'=>$rows];
        } finally { fclose($stream); }
    }

    public function export(int $id): string
    {
        $directory=$this->directory($id);
        $keys=array_column($directory['columns'], 'key');
        $stream=fopen('php://temp', 'w+b');
        try {
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, array_merge(['_csv_format','_id','_title'], $keys), ';', '"', '');
            foreach ($this->db->fetchAllAssociative('SELECT * FROM tl_directory_entry WHERE pid=? ORDER BY sorting,id', [$id]) as $entry) {
                $values=array_column($this->decode($entry['data']), 'value', 'key');
                $cells=['contao-text-v1', (string)$entry['id'], $entry['title']];
                foreach ($keys as $key) { $cells[]=(string)($values[$key]??''); }
                // Prevent spreadsheet formula execution; the format marker enables lossless re-import.
                foreach ($cells as &$cell) { if (str_starts_with($cell,"'") || preg_match('/^[\s\x00-\x20]*[=+@\-]/u', $cell)) { $cell="'".$cell; } }
                unset($cell);
                fputcsv($stream, $cells, ';', '"', '');
            }
            rewind($stream);
            return stream_get_contents($stream);
        } finally { fclose($stream); }
    }

    public function plan(int $id, array $csv, array $mapping): array
    {
        $directory=$this->directory($id);
        $keys=array_column($directory['columns'], 'key');
        $allowed=array_merge(['_id','_title'], $keys);
        foreach ($mapping as $key=>$source) {
            if (!in_array($key, $allowed, true) || ($source!=='' && !in_array($source, $csv['header'], true))) { throw new \RuntimeException('Ungültige Spaltenzuordnung.'); }
        }
        if (!array_filter(array_intersect_key($mapping, array_flip($keys)))) { throw new \RuntimeException('Bitte mindestens ein Datenfeld zuordnen.'); }
        $plan=['directory'=>$id, 'schema'=>hash('sha256', serialize($directory['columns'])), 'rows'=>[], 'new'=>0, 'updated'=>0];
        $seen=[];
        foreach ($csv['rows'] as $index=>$row) {
            $get=static function(string $key) use ($row,$mapping): ?string {
                $source=$mapping[$key]??'';
                if ($source==='') { return null; }
                $value=(string)$row[$source];
                if (($row['_csv_format']??'')==='contao-text-v1' && (str_starts_with($value,"''") || preg_match('/^\'[\s\x00-\x20]*[=+@\-]/u',$value))) { $value=substr($value,1); }
                return $value;
            };
            $rawId=trim($get('_id')??''); $entry=null;
            if ($rawId!=='') {
                if (!ctype_digit($rawId) || (int)$rawId<1 || isset($seen[(int)$rawId])) { throw new \RuntimeException('Ungültige oder doppelte ID in Datensatz '.($index+1).'.'); }
                $seen[(int)$rawId]=true;
                $entry=$this->db->fetchAssociative('SELECT * FROM tl_directory_entry WHERE id=? AND pid=?', [(int)$rawId,$id]);
                if (!$entry) { throw new \RuntimeException('ID '.$rawId.' gehört nicht zum gewählten Verzeichnis. Für neue Einträge ID leer lassen.'); }
            }
            $values=$entry ? array_column($this->decode($entry['data']), 'value', 'key') : [];
            foreach ($keys as $key) { $value=$get($key); if ($value!==null) { $values[$key]=$value; } elseif (!isset($values[$key])) { $values[$key]=''; } }
            $data=[]; foreach ($values as $key=>$value) { $data[]=['key'=>$key,'value'=>$value]; }
            $title=$get('_title');
            if ($title===null || trim($title)==='') { $title=$entry['title']??ImportEfgCommand::entryTitle($data,$index+1); }
            if (mb_strlen($title)>255) { throw new \RuntimeException('Bezeichnung in Datensatz '.($index+1).' ist länger als 255 Zeichen.'); }
            $plan['rows'][]=['id'=>$entry?(int)$entry['id']:null,'title'=>$title,'data'=>serialize($data),'before'=>$entry?hash('sha256',serialize($entry)):null];
            ++$plan[$entry?'updated':'new'];
        }
        return $plan;
    }

    public function execute(array $plan): void
    {
        $this->db->transactional(function() use ($plan): void {
            $lock=$this->db->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SqlitePlatform ? '' : ' FOR UPDATE';
            $this->db->fetchOne('SELECT id FROM tl_directory WHERE id=?'.$lock,[$plan['directory']]);
            $directory=$this->directory($plan['directory']);
            if (!hash_equals($plan['schema'],hash('sha256',serialize($directory['columns'])))) { throw new \RuntimeException('Die Spalten wurden geändert. Bitte Vorschau neu erstellen.'); }
            $sorting=(int)$this->db->fetchOne('SELECT MAX(sorting) FROM tl_directory_entry WHERE pid=?',[$plan['directory']]);
            foreach ($plan['rows'] as $row) {
                $values=['title'=>$row['title'],'data'=>$row['data'],'tstamp'=>time()];
                if ($row['id']!==null) {
                    $entry=$this->db->fetchAssociative('SELECT * FROM tl_directory_entry WHERE id=? AND pid=?'.$lock,[$row['id'],$plan['directory']]);
                    if (!$entry || !hash_equals($row['before'],hash('sha256',serialize($entry)))) { throw new \RuntimeException('Ein Eintrag wurde seit der Vorschau geändert. Bitte Vorschau neu erstellen.'); }
                    $this->db->update('tl_directory_entry',$values,['id'=>$row['id'],'pid'=>$plan['directory']]);
                } else {
                    $sorting+=128;
                    $this->db->insert('tl_directory_entry',$values+['pid'=>$plan['directory'],'sorting'=>$sorting,'published'=>'','legacyId'=>null]);
                }
            }
        });
    }
}
