<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;


class BulkImport extends BaseController
{
    public function index()
    {
        $title = 'Bulk Import';

        $tmpIds = $this->request->getGet('ids');
        $tmpType = $this->request->getGet('type');
        if($tmpType != 'movies') $tmpType = 'tv';

        if(! empty($tmpIds)){

            return redirect()->to( '/admin/bulk-import')
                             ->with('import-ids', $tmpIds)
                             ->with('import-type', $tmpType);

        }

        $ids = session('import-ids');
        $type = session('import-type');

        if(! empty($ids)){
            $ids = explode(',', str_replace(' ', '',$ids));
        }



        if(! is_array($ids)) $ids = [];

        return view('admin/bulk_import/index',
        compact('title', 'type', 'ids'));
    }
}