<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PagesModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Page extends BaseController
{

    public function index($slug = '')
    {
        if(! empty( $slug )){

            $metaKeywords = $metaDescription = '';

            $pageModel = new PagesModel();
            $page = $pageModel->getPageBySlug( $slug );

            if($page !== null){

                $title = $page->title;
                $metaKeywords = $page->meta_keywords;
                $metaDescription = $page->meta_description;

                return view(theme_path('page'), compact('page','metaKeywords', 'metaDescription',  'title'));
            }

        }

        throw new PageNotFoundException();
    }
}
