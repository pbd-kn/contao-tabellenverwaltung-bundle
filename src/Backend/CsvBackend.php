<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Backend;

use Contao\BackendModule;
use Contao\BackendUser;
use Contao\System;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;

final class CsvBackend extends BackendModule
{
    protected $strTemplate='be_directory_csv';
    protected function compile(): void
    {
        if (!BackendUser::getInstance()->isAdmin) { throw new AccessDeniedException('CSV-Übertragung ist nur für Administratoren verfügbar.'); }
        $container=System::getContainer();
        $db=$container->get('database_connection');
        $request=$container->get('request_stack')->getCurrentRequest();
        $session=$request->getSession();
        $csrf=$container->get('contao.csrf.token_manager');
        $service=new CsvTransfer($db);
        $directoryId=(int)$request->request->get('directory', $request->query->get('directory',0));
        $error=''; $message=''; $directory=null; $preview=null;
        $state=$session->get('directory_csv_state');
        if (!$state || $state['directory']!==$directoryId) { $state=null; }
        if ($request->isMethod('POST')) {
            $token=$request->request->get('REQUEST_TOKEN');
            if (!is_string($token) || !$csrf->isTokenValid(new CsrfToken($container->getParameter('contao.csrf_token_name'),$token))) { throw new AccessDeniedException('Ungültiges Anfragetoken.'); }
        }
        try {
            if ($directoryId) { $directory=$service->directory($directoryId); }
            if ($request->isMethod('POST') && $request->request->get('action')==='export') {
                $contents=$service->export($directoryId);
                throw new ResponseException(new Response($contents,200,[
                    'Content-Type'=>'text/csv; charset=UTF-8',
                    'Content-Disposition'=>'attachment; filename="verzeichnis-'.$directoryId.'.csv"',
                    'Cache-Control'=>'private, no-store', 'X-Content-Type-Options'=>'nosniff',
                ]));
            }
            if ($request->isMethod('POST')) {
                $action=$request->request->get('action');
                if ($action==='upload') {
                    $session->remove('directory_csv_state'); $state=null;
                    if (!$directory) { throw new \RuntimeException('Bitte zuerst ein Verzeichnis auswählen.'); }
                    $file=$request->files->get('csv');
                    if (!$file || !$file->isValid()) { throw new \RuntimeException('Bitte eine gültige CSV-Datei auswählen (höchstens 5 MB; zusätzlich gilt das PHP-Uploadlimit).'); }
                    if ($file->getSize()>CsvTransfer::MAX_BYTES) { throw new \RuntimeException('Die CSV darf höchstens 5 MB groß sein.'); }
                    $csv=$service->parse((string)file_get_contents($file->getPathname()),(string)$request->request->get('delimiter','auto'));
                    $mapping=[];
                    foreach (array_merge([['key'=>'_id','value'=>'_id'],['key'=>'_title','value'=>'_title']],$directory['columns']) as $column) {
                        $mapping[$column['key']]='';
                        foreach ([$column['key'],$column['value']] as $match) {
                            foreach ($csv['header'] as $header) {
                                if (mb_strtolower($header)===mb_strtolower($match)) { $mapping[$column['key']]=$header; break 2; }
                            }
                        }
                    }
                    $state=['directory'=>$directoryId,'csv'=>$csv,'mapping'=>$mapping,'nonce'=>bin2hex(random_bytes(16))];
                    $session->set('directory_csv_state',$state);
                } elseif (in_array($action,['preview','execute'],true)) {
                    if (!$state || !hash_equals($state['nonce'],(string)$request->request->get('nonce',''))) { throw new \RuntimeException('Diese Übernahme ist abgelaufen oder bereits ausgeführt. Bitte CSV erneut hochladen.'); }
                    if ($action==='preview') {
                        unset($state['plan']);
                        $session->set('directory_csv_state',$state);
                        $posted=$request->request->all('mapping');
                        $mapping=[];
                        foreach (array_keys($state['mapping']) as $index=>$key) { $mapping[$key]=is_string($posted[$index]??null)?$posted[$index]:''; }
                        $state['mapping']=$mapping;
                        $state['plan']=$service->plan($directoryId,$state['csv'],$mapping);
                        $session->set('directory_csv_state',$state);
                    } else {
                        if (!isset($state['plan']) || $request->request->get('confirmed')!=='1') { throw new \RuntimeException('Bitte zuerst die Vorschau erstellen und die Übernahme bestätigen.'); }
                        $service->execute($state['plan']);
                        $message=$state['plan']['new'].' neue Einträge angelegt, '.$state['plan']['updated'].' aktualisiert. Neue Einträge sind unveröffentlicht; bestehende Veröffentlichungszustände bleiben erhalten.';
                        $session->remove('directory_csv_state'); $state=null;
                    }
                }
            }
            $preview=$state['plan']??null;
        } catch (ResponseException $response) {
            throw $response;
        } catch (\Exception $exception) {
            $error=$exception->getMessage();
            if ($state) { unset($state['plan']); $session->set('directory_csv_state',$state); }
        }
        $this->Template->directories=$db->fetchAllAssociative('SELECT id,title FROM tl_directory ORDER BY title');
        $this->Template->directoryId=$directoryId;
        $this->Template->directory=$directory;
        $this->Template->state=$state;
        $this->Template->preview=$preview;
        $this->Template->error=$error;
        $this->Template->message=$message;
        $this->Template->token=$csrf->getDefaultTokenValue();
    }
}
