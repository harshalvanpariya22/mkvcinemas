<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MovieModel;

class Sitemap extends BaseController
{
    public function index()
    {
        $movieModel = new MovieModel();

        $movies = $movieModel->allMovies([], false)
                             ->select('movies.imdb_id, movies.updated_at')
                             ->findAll();

        $episodes = $movieModel->episodes(false)
                               ->allMovies()
                               ->select('movies.imdb_id, movies.updated_at')
                               ->forView()
                               ->findAll();

        $movies = array_merge($movies, $episodes);
        $mainPages = $this->getMainPages();

        $this->response->setHeader('Content-Type', 'text/xml;charset=iso-8859-1');
        return view('sitemap', compact('movies', 'mainPages'));
    }



    protected function getMainPages(): array
    {
        return [
            library_url(),
            library_url([], 'shows'),
            site_url('/trending/movies'),
            site_url('/trending/shows'),
            site_url('/recommend/movies'),
            site_url('/recommend/shows'),
            site_url('/recent-releases/movies'),
            site_url('/recent-releases/shows'),
            site_url('/imdb-top/movies'),
            site_url('/imdb-top/shows')
        ];
    }

}
