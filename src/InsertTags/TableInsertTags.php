<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\InsertTags;

use Doctrine\DBAL\Connection;

final class TableInsertTags
{
    public function __construct(private Connection $db) {}

    /** Return false only for tags belonging to another extension. */
    public function replace(string $tag)
    {
        $parts=explode('::',$tag);
        if (!in_array($parts[0],['direntry','tabelle'],true)) { return false; }
        if (count($parts)!==3) { return ''; }
        $first=html_entity_decode($parts[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $second=html_entity_decode($parts[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
        if ($first==='' || $second==='') { return ''; }

        if ($parts[0]==='direntry') {
            $condition=ctype_digit($first)?'d.id=?':'d.title=?';
            $rows=$this->db->fetchAllAssociative(
                "SELECT e.id FROM tl_directory_entry e INNER JOIN tl_directory d ON d.id=e.pid WHERE ".$condition." AND e.title=? AND e.published='1'",
                [$first,$second]
            );
            // Never pick an arbitrary entry when names are ambiguous.
            return count($rows)===1 ? (string)$rows[0]['id'] : '';
        }

        if (!ctype_digit($first)) { return ''; }
        $entry=$this->db->fetchAssociative("SELECT title,data FROM tl_directory_entry WHERE id=? AND published='1'",[(int)$first]);
        if (!$entry) { return ''; }
        if ($second==='_title') { return $this->escape($entry['title']); }
        $data=$entry['data'];
        if (is_resource($data)) { $data=stream_get_contents($data); }
        $fields=is_string($data)?@unserialize($data,['allowed_classes'=>false]):[];
        if (!is_array($fields)) { return ''; }
        foreach ($fields as $field) {
            if (isset($field['key']) && $field['key']===$second) { return $this->escape((string)($field['value']??'')); }
        }
        return '';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    }
}
