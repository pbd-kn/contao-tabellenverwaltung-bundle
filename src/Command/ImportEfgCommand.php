<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Command;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
final class ImportEfgCommand extends Command
{
    private Connection $connection;
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct('directory:import-efg');
    }
    protected function configure(): void
    {
        $this->setDescription('Kopiert ein EFG-Verzeichnis ohne Änderungen an den Quelldaten.')
            ->addArgument('form', InputArgument::REQUIRED, 'Exakter Wert aus tl_formdata.form')
            ->addArgument('title', InputArgument::REQUIRED, 'Name des neuen Verzeichnisses')
            ->addOption('execute', null, InputOption::VALUE_NONE, 'Änderungen tatsächlich durchführen')
            ->addOption('repair-titles', null, InputOption::VALUE_NONE, 'Automatisch importierte Bezeichnungen im vorhandenen Verzeichnis korrigieren');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $db = $this->connection;
        $form = (string) $input->getArgument('form');
        $title = trim((string) $input->getArgument('title'));
        if ($title === '' || strlen($title) > 255) {
            throw new \InvalidArgumentException('Der Verzeichnisname muss 1–255 Bytes lang sein.');
        }
        if ($input->getOption('repair-titles')) {
            return $this->repairTitles($input, $output, $title, $form);
        }
        $entries = $db->fetchAllAssociative('SELECT id FROM tl_formdata WHERE form=? ORDER BY sorting,id', [$form]);
        if (!$entries) {
            $output->writeln('Keine Quelldatensätze gefunden.');
            return Command::FAILURE;
        }
        $columns = [];
        $records = [];
        foreach ($entries as $entry) {
            $data = $db->fetchAllAssociative('SELECT ff_name AS `key`, value FROM tl_formdata_details WHERE pid=? ORDER BY sorting,id', [$entry['id']]);
            $seen = [];
            foreach ($data as $field) {
                if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D', $field['key']) || isset($seen[$field['key']])) {
                    throw new \RuntimeException('Ungültiger oder doppelter Feldname in EFG-Eintrag '.$entry['id'].': '.$field['key']);
                }
                $seen[$field['key']] = true;
                $columns[$field['key']] = ['key' => $field['key'], 'value' => $field['key']];
            }
            $records[] = ['id' => $entry['id'], 'data' => $data];
        }
        $output->writeln(count($records).' Einträge, '.count($columns).' Spalten.');
        if (!$input->getOption('execute')) {
            $output->writeln('Vorschau: keine Änderungen. Zum Kopieren --execute angeben.');
            return Command::SUCCESS;
        }
        $db->transactional(function (Connection $db) use ($title, $columns, $records): void {
            if ($db->fetchOne('SELECT id FROM tl_directory WHERE title=?', [$title])) {
                throw new \RuntimeException('Ein Verzeichnis mit diesem Namen existiert bereits. Import abgebrochen.');
            }
            $db->insert('tl_directory', ['title' => $title, 'tstamp' => time(), 'columns' => serialize(array_values($columns))]);
            $pid = (int) $db->lastInsertId();
            foreach ($records as $index => $record) {
                $db->insert('tl_directory_entry', [
                    'pid' => $pid, 'tstamp' => time(), 'sorting' => ($index + 1) * 128,
                    'legacyId' => $record['id'], 'title' => self::entryTitle($record['data'], (int) $record['id']),
                    'published' => '', 'data' => serialize($record['data']),
                ]);
            }
        });
        $output->writeln('Import abgeschlossen. Einträge sind zunächst unveröffentlicht; bitte Werte und Ausgabe prüfen.');
        return Command::SUCCESS;
    }

    public static function entryTitle(array $data, int $id): string
    {
        $fields = [];
        foreach ($data as $field) {
            $fields[strtoupper((string) $field['key'])] = trim((string) ($field['value'] ?? ''));
        }
        foreach (['FIRMA', 'BEZEICHNUNG'] as $key) {
            if (($fields[$key] ?? '') !== '') {
                return mb_substr($fields[$key], 0, 255);
            }
        }
        $name = trim(($fields['VORNAME'] ?? '').' '.($fields['NAME'] ?? ''));
        return mb_substr($name !== '' ? $name : 'Eintrag '.$id, 0, 255);
    }

    private function repairTitles(InputInterface $input, OutputInterface $output, string $title, string $form): int
    {
        $db = $this->connection;
        $directories = $db->fetchFirstColumn('SELECT id FROM tl_directory WHERE title=?', [$title]);
        if (count($directories) !== 1) {
            throw new \RuntimeException('Das Zielverzeichnis muss genau einmal existieren.');
        }
        $rows = $db->fetchAllAssociative('SELECT e.id,e.title,e.data,e.legacyId FROM tl_directory_entry e WHERE e.pid=? AND EXISTS (SELECT 1 FROM tl_formdata f WHERE f.id=e.legacyId AND f.form=?)', [$directories[0], $form]);
        $changes = [];
        foreach ($rows as $row) {
            $data = unserialize((string) $row['data'], ['allowed_classes' => false]);
            if (!is_array($data)) {
                throw new \RuntimeException('Ungültige Feldwerte bei Eintrag '.$row['id']);
            }
            $oldGenerated = mb_substr((string) ($data[0]['value'] ?? 'Eintrag '.$row['legacyId']), 0, 255);
            $newTitle = self::entryTitle($data, (int) $row['legacyId']);
            // Individuell bearbeitete Bezeichnungen erhalten.
            if ($row['title'] === $oldGenerated && $row['title'] !== $newTitle) {
                $changes[] = ['id' => $row['id'], 'title' => $newTitle, 'old' => $row['title']];
            }
        }
        $output->writeln(count($changes).' Bezeichnungen werden korrigiert.');
        if (!$input->getOption('execute')) {
            $output->writeln('Vorschau: keine Änderungen. Zum Korrigieren --execute angeben.');
            return Command::SUCCESS;
        }
        $db->transactional(function (Connection $db) use ($changes): void {
            foreach ($changes as $change) {
                $db->executeStatement('UPDATE tl_directory_entry SET title=?,tstamp=? WHERE id=? AND title=?', [$change['title'], time(), $change['id'], $change['old']]);
            }
        });
        $output->writeln('Bezeichnungen korrigiert. Feldwerte und Veröffentlichungsstatus bleiben erhalten.');
        return Command::SUCCESS;
    }
}
