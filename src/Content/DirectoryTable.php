<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Content;
use Contao\ContentElement;
use Contao\StringUtil;
use Contao\System;
use Contao\Input;
final class DirectoryTable extends ContentElement
{
    protected $strTemplate = 'ce_directory_table';
    protected function compile(): void
    {
        $db = System::getContainer()->get('database_connection');
        $directory = $db->fetchAssociative('SELECT * FROM tl_directory WHERE id=?', [$this->directoryId]);
        $allColumns = $directory ? StringUtil::deserialize($directory['columns'], true) : [];
        $select = static function (string $csv) use ($allColumns): array {
            $result = [];
            foreach (array_filter(array_map('trim', explode(',', $csv))) as $key) {
                foreach ($allColumns as $column) {
                    if (strcasecmp($column['key'], $key) === 0) { $result[] = $column; break; }
                }
            }
            return $result;
        };
        $this->Template->columns = $this->directoryDisplayColumns ? $select($this->directoryDisplayColumns) : $allColumns;
        $this->Template->detailColumns = $select((string) $this->directoryDetailColumns);
        $searchColumns = $select((string) $this->directorySearchColumns);
        $searchName = 'directory_q_'.$this->id;
        $searchFieldName = 'directory_field_'.$this->id;
        $searchField = Input::get($searchFieldName);
        $allowedSearchFields = array_column($searchColumns, 'key');
        if (!is_string($searchField) || !in_array($searchField, $allowedSearchFields, true)) {
            $searchField = $allowedSearchFields[0] ?? '';
        }
        $query = Input::get($searchName);
        $query = is_string($query) ? mb_substr(trim($query), 0, 200) : '';
        $entries = $db->fetchAllAssociative("SELECT data FROM tl_directory_entry WHERE pid=? AND published='1' ORDER BY sorting,id", [$this->directoryId]);
        $rows = [];
        foreach ($entries as $entry) {
            $row = array_column(StringUtil::deserialize($entry['data'], true), 'value', 'key');
            if ($query !== '' && $searchColumns) {
                if (mb_stripos((string) ($row[$searchField] ?? ''), $query) === false) { continue; }
            }
            $rows[] = $row;
        }
        $order = $select((string) $this->directoryOrder);
        if ($order) {
            usort($rows, static function (array $left, array $right) use ($order): int {
                foreach ($order as $column) {
                    $cmp = strnatcasecmp((string) ($left[$column['key']] ?? ''), (string) ($right[$column['key']] ?? ''));
                    if ($cmp !== 0) { return $cmp; }
                }
                return 0;
            });
        }
        $root = System::getContainer()->getParameter('kernel.project_dir');
        $this->Template->cellFormatter = new CellFormatter($root);
        $this->Template->searchEnabled = (bool) $searchColumns;
        $this->Template->searchName = $searchName;
        $this->Template->searchFieldName = $searchFieldName;
        $this->Template->searchField = $searchField;
        $labels = ['FIRMA' => 'Firma', 'PLZ' => 'PLZ', 'ORT' => 'Ort', 'NAME' => 'Name', 'VORNAME' => 'Vorname'];
        foreach ($searchColumns as &$column) {
            $column['label'] = $labels[strtoupper($column['key'])] ?? $column['value'];
        }
        unset($column);
        $this->Template->searchColumns = $searchColumns;
        $this->Template->query = $query;
        $this->Template->rows = $rows;
    }
}
