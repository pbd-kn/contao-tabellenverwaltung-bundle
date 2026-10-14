<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Backend;
use Contao\BackendModule;
use Contao\BackendUser;
use Contao\Input;
use Contao\System;
use Contao\CoreBundle\Exception\AccessDeniedException;
use PBDKN\TabellenverwaltungBundle\Command\ImportEfgCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Security\Csrf\CsrfToken;
final class ImportBackend extends BackendModule
{
    protected $strTemplate = 'be_directory_import';
    protected function compile(): void
    {
        if (!BackendUser::getInstance()->isAdmin) { throw new AccessDeniedException('Der EFG-Import ist nur für Administratoren verfügbar.'); }
        $container = System::getContainer();
        $db = $container->get('database_connection');
        $request = $container->get('request_stack')->getCurrentRequest();
        $session = $request->getSession();
        $csrf = $container->get('contao.csrf.token_manager');
        $tables = $db->createSchemaManager()->listTableNames();
        $available = in_array('tl_formdata', $tables, true) && in_array('tl_formdata_details', $tables, true);
        $forms = $available ? $db->fetchAllAssociative('SELECT form,COUNT(*) AS count FROM tl_formdata GROUP BY form ORDER BY form') : [];
        $source = Input::post('source');
        $source = is_string($source) ? $source : '';
        $title = Input::post('title');
        $title = is_string($title) ? trim($title) : '';
        $message = '';
        $error = '';
        $preview = null;
        if ($request->isMethod('POST') && Input::post('FORM_SUBMIT') === 'directory_import') {
            $token = Input::post('REQUEST_TOKEN');
            if (!is_string($token) || !$csrf->isTokenValid(new CsrfToken($container->getParameter('contao.csrf_token_name'), $token))) { throw new AccessDeniedException('Ungültiges Anfragetoken.'); }
            try {
                if (!$available || !in_array($source, array_column($forms, 'form'), true)) { throw new \InvalidArgumentException('Bitte ein vorhandenes EFG-Verzeichnis auswählen.'); }
                if ($title === '' || strlen($title) > 255) { throw new \InvalidArgumentException('Bitte einen Zielnamen mit maximal 255 Bytes eingeben.'); }
                if ($db->fetchOne('SELECT id FROM tl_directory WHERE title=?', [$title])) { throw new \RuntimeException('Dieses Zielverzeichnis existiert bereits. Es wird nicht erneut importiert.'); }
                $args = ['form' => $source, 'title' => $title];
                $count = (int) $db->fetchOne('SELECT COUNT(*) FROM tl_formdata WHERE form=?', [$source]);
                $execute = Input::post('action') === 'execute';
                if ($execute) {
                    $previous = $session->get('directory_import_preview');
                    if (!$previous || $previous !== ['source'=>$source,'title'=>$title,'count'=>$count] || Input::post('confirmed') !== '1') { throw new \RuntimeException('Bitte zuerst die Vorschau prüfen und die Übernahme bestätigen.'); }
                    $args['--execute'] = true;
                }
                $output = new BufferedOutput();
                $status = (new ImportEfgCommand($db))->run(new ArrayInput($args), $output);
                if ($status !== 0) { throw new \RuntimeException($output->fetch()); }
                $message = $output->fetch();
                if ($execute) { $session->remove('directory_import_preview'); }
                else { $preview = ['source'=>$source,'title'=>$title,'count'=>$count]; $session->set('directory_import_preview', $preview); }
            } catch (\Exception $exception) { $error = $exception->getMessage(); $session->remove('directory_import_preview'); }
        }
        $this->Template->forms = $forms;
        $this->Template->source = $source;
        $this->Template->targetTitle = $title;
        $this->Template->message = $message;
        $this->Template->error = $error;
        $this->Template->preview = $preview;
        $this->Template->available = $available;
        $this->Template->token = $csrf->getDefaultTokenValue();
    }
}
