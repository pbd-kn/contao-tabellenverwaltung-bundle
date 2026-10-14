<?php
declare(strict_types=1);
namespace PBDKN\TabellenverwaltungBundle\Module;
use Contao\ContentModel;
use Contao\Module;
use PBDKN\TabellenverwaltungBundle\Content\DirectoryTable;
final class DirectoryModule extends Module
{
    public function generate()
    {
        $data = $this->arrData;
        $data['id'] = 100000 + (int) $this->id;
        $data['type'] = 'directory_table';
        $data['invisible'] = '';
        $data['customTpl'] = '';
        $data['tstamp'] = $data['tstamp'] ?: time();
        $data['start'] = $data['start'] ?? '';
        $data['stop'] = $data['stop'] ?? '';
        $model = new ContentModel();
        $model->setRow($data);
        return (new DirectoryTable($model, $this->strColumn))->generate();
    }
    protected function compile(): void {}
}
