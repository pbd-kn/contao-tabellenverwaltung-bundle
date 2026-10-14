<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Backend;
use Contao\Database;
use Contao\StringUtil;
final class EntryData
{
    public function validateColumns($value)
    {
        $seen = [];
        foreach (StringUtil::deserialize($value, true) as $column) {
            $key = $column['key'] ?? '';
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D', $key) || isset($seen[$key])) {
                throw new \InvalidArgumentException('Feldnamen müssen eindeutig sein und aus Buchstaben, Ziffern und Unterstrichen bestehen.');
            }
            $seen[$key] = true;
        }
        return $value;
    }
    private function columns($dc): array
    {
        $entry = Database::getInstance()->prepare('SELECT pid FROM tl_directory_entry WHERE id=?')->execute($dc->id);
        $directory = Database::getInstance()->prepare('SELECT columns FROM tl_directory WHERE id=?')->execute($entry->pid);
        return StringUtil::deserialize($directory->columns, true);
    }
    public function load($value, $dc)
    {
        $rows = StringUtil::deserialize($value, true);
        $keys = array_column($rows, 'key');
        foreach ($this->columns($dc) as $column) {
            if (!in_array($column['key'], $keys, true)) {
                $rows[] = ['key' => $column['key'], 'value' => ''];
            }
        }
        return serialize($rows);
    }
    public function save($value, $dc)
    {
        $allowed = array_column($this->columns($dc), 'key');
        $seen = [];
        foreach (StringUtil::deserialize($value, true) as $row) {
            if (!in_array($row['key'], $allowed, true) || isset($seen[$row['key']])) {
                throw new \InvalidArgumentException('Unbekannter oder doppelter Feldname. Bitte zuerst die Spalten des Verzeichnisses anpassen.');
            }
            $seen[$row['key']] = true;
        }
        return $value;
    }
    public function label(array $row): string
    {
        return '<div class="tl_content_left">'.StringUtil::specialchars($row['title']).($row['published'] ? '' : ' (unveröffentlicht)').'</div>';
    }
}
