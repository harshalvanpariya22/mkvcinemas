<?php

namespace App\Controllers\Admin\Ajax;

use App\Controllers\BaseAjax;
use App\Models\MovieModel;
use App\Models\SeriesModel;



/**
 * Class Suggest
 * @package App\Controllers\Admin\Ajax
 * @author John Antonio
 */
class Suggest extends BaseAjax
{
    public function index()
    {
        $title = $this->request->getGet('title');
        $type = $this->request->getGet('type');
        $content = '';

        if(! empty($title)){

            $tmdb = service('tmdb');
            $results = $tmdb->translate()
                            ->search( $title, $type );


            if(! empty( $results )){

                $movieModel = new MovieModel();
                $seriesModel = new SeriesModel();

                foreach ($results as $key => $val) {

                    $results[$key]['is_exist'] = false;

                    if($type == 'movie'){
                        $movie = $movieModel->select('movies.id')
                                            ->movies()
                                            ->getMovieByUniqId( $val['tmdb_id'], 'tmdb_id', false );
                    }else{
                        $movie = $seriesModel->select('id')
                                             ->getSeriesByTmdbId($val['tmdb_id']);
                    }

                    if($movie !== null){
                        $results[$key]['is_exist'] = true;
                    }
                }

            }

            ob_start();
            the_admin_suggest_page( $results );
            $content .= ob_get_clean();

            $this->addData( ['results' => $content] );
        }

        return $this->jsonResponse();
    }

}