<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\GenreModel;
use CodeIgniter\Model;

class Discover extends BaseController
{

    protected $dataApi;

    public function __construct()
    {
        $this->dataApi = service('data_api');
    }

    public function movies()
    {
        $title = 'Discover Movies';

        $genres = $this->dataApi->getGenres('movie');
        $langList = $this->dataApi->getLangList();
        $langList = array_merge([ '' => '' ], $langList);

        $topBtnGroup = create_top_btn_group([
            'admin/discover/shows' => 'Discover TV Shows'
        ]);

        return view('admin/discover/movies',
            compact('title','langList', 'genres', 'topBtnGroup'));
    }


    public function shows()
    {
        $title = 'Discover TV Shows';

        $genres = $this->dataApi->getGenres('tv');

        $langList = $this->dataApi->getLangList();
        $langList = array_merge([ '' => '' ], $langList);

        $topBtnGroup = create_top_btn_group([
            'admin/discover/movies' => 'Discover Movies'
        ]);

        return view('admin/discover/series',
            compact('title','langList', 'genres', 'topBtnGroup'));
    }


}
