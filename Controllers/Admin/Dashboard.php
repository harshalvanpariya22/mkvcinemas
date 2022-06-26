<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Analytics;
use App\Models\MovieModel;
use App\Models\SettingsModel;


class Dashboard extends BaseController
{
    public function index()
    {
        $title = 'Dashboard';

        $analytics = new Analytics();
        $anytc = $analytics->init()
                            ->getData();

        $movieModel = new MovieModel();
        $topMovies = $movieModel->movies()
                                ->where('views > ', 0)
                                ->orderBy('views', 'DESC')
                                ->findAll(10);

        $topEpisodes = $movieModel->episodes()
                                  ->where('movies.views > ', 0)
                                  ->orderBy('movies.views', 'DESC')
                                  ->findAll(10);



        $data = compact('title', 'anytc', 'topMovies', 'topEpisodes');

        return view('admin/dashboard/index', $data);
    }
}
