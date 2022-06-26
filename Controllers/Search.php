<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MovieModel;
use App\Models\SeriesModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Exceptions\PageNotFoundException;

class Search extends BaseController
{

    use ResponseTrait;

    public function index()
    {

        $searchTerm = $this->request->getGet( 'term' );
        $maxResults = 8;
        $results = $seriesResults = [];


        if($this->validate([
            'term' => 'required|min_length[3]'
        ])){

            #01: check with movies table (movie title and imdb/ tmdb id)
            $movieModel = new MovieModel();


            $movieResults = $movieModel->forView()
                                       ->join('movie_translations as translations', 'translations.movie_id = movies.id', 'LEFT')
                                       ->groupStart()
                                            ->like('movies.title', $searchTerm, 'both', null, true)
                                            ->orGroupStart()
                                                ->where('lang', current_language())
                                                ->like('translations.title', $searchTerm, 'both', null, true)
                                            ->groupEnd()
                                       ->groupEnd()
                                       ->orWhere('movies.imdb_id', $searchTerm)
                                       ->orWhere('movies.tmdb_id', $searchTerm)
                                       ->select('movies.*')
                                       ->groupBy('movies.id')
                                       ->findAll( $maxResults );


            if(! empty($movieResults))
                $maxResults -= count( $movieResults );

            #02: check with series table (series title and imdb/ tmdb id)
            if($maxResults !== 0){

                $seriesModel = new SeriesModel();

                $seriesResults = $seriesModel->like('title', $searchTerm)
                                             ->orWhere('imdb_id', $searchTerm)
                                             ->orWhere('tmdb_id', $searchTerm)
                                             ->findAll( $maxResults );

            }

            $results = array_merge($movieResults, $seriesResults);

        }


        if( $this->request->isAJAX() ){

            if(! empty($results)){
                foreach ($results as $movie) {
                    echo '<div class="col-6 col-md-4 col-lg-3  px-5">';
                    the_movie_item($movie, false, false);
                    echo '</div>';
                }
            }else{
                echo 'no-data';
            }
            return;
        }


        throw new PageNotFoundException();

    }




}
