<?php

namespace App\Controllers\Admin\Ajax;

use App\Controllers\BaseAjax;
use App\Models\MovieModel;
use App\Models\SeriesModel;
use CodeIgniter\Model;


/**
 * Class Discover
 * @package App\Controllers\Admin\Ajax
 * @author John Antonio
 */
class Discover extends BaseAjax
{


    public function index()
    {

        $dataApi = service('data_api');

        $type       = $this->request->getGet('type');
        $page       = $this->request->getGet('page');
        $year       = $this->request->getGet('year');
        $status       = $this->request->getGet('status');
        $showType       = $this->request->getGet('show_type');
        $genres     = $this->request->getGet('genres');
        $lang     = $this->request->getGet('lang');
        $sort       = $this->request->getGet('sort');
        $sortDir    = $this->request->getGet('sort_dir');
        $importedFilter = $this->request->getGet('imported_filter') == 1;
        $isMovie = $type == 'movies';

        if(empty( $page )) $page = 1;
        if($type != 'tv') $type = 'movie';

        if(! empty( $genres )){
            $genres = explode(',', $genres);
        }

        $filters = [
            'genres' => $genres,
            'year' => $year,
            'with_original_language' => $lang
        ];

        if($type == 'tv'){
            $filters['with_status'] = $status;
            $filters['with_type'] = $showType;
            $filters['first_air_date_year'] = $year;
            unset($filters['year']);
        }

        $results = $dataApi->discover($type , $page, $filters , $sort, $sortDir);

        $content = '';

        if(! empty($results['results'])){

            $list = $results['results'];
            $movieModel = $isMovie ? new MovieModel() : new SeriesModel();

            foreach ($list as $k => $result){

                $movie = $isMovie ? $movieModel->getMovieByTmdbId( $result->tmdb_id ) : $movieModel->getSeriesByTmdbId( $result->tmdb_id );
                $isExit = false;
                if($movie !== null){
                    if($importedFilter){
                        unset($results['results'][$k]);
                    }
                    $isExit = true;
                    $result->movie_id = $movie->id;
                }

                $result->isExist = $isExit;

            }

        }

        if(! empty($results['results'])){
            ob_start();
            the_admin_discover_page( $results['results'], $results['page'], $results['total_pages'] );
            $content .= ob_get_clean();
        }

        $results['results'] = $content;

        $this->addData( $results );

        return $this->jsonResponse();
    }


}