<?php

namespace App\Controllers\Admin\Ajax;

use App\Controllers\BaseAjax;
use App\Libraries\DataAPI\API;
use App\Models\MovieModel;
use App\Models\SeasonModel;
use App\Models\SeriesModel;
use CodeIgniter\Model;

/**
 * Class Autoload
 * @package App\Controllers\Admin\Ajax
 * @author John Antonio
 */
class Autoload extends BaseAjax
{
    /**
     * Data API (tmdb and omdb handler)
     * @var ?API
     */
    protected $dataAPI = null;


    /**
     * Autoload constructor.
     */
    public function __construct()
    {
        $this->helpers[] = 'data_api';
        $this->dataAPI = service('data_api');
    }

    /**
     * Get tv info by imdb oor tmdb id
     * @return mixed
     */
    public function load_tv_data()
    {
        $id = $this->request->getGet( 'id' );

        if($this->validate([
            'id' => 'required|valid_movie_id'
        ])) {

            if(! isValidImdbId($id)){
                $results = $this->dataAPI->setTmdbId( $id )
                                         ->getTv();
            }else{
                $results = $this->dataAPI->setImdbId( $id )
                                         ->getTv();
            }

            if($results !== null) {

                $this->success();
                $this->addData( $results->getData() );

            }else{

                $this->addError( 'data not found' );

            }

        }else{

            $this->addError( $this->validator->getErrors() );

        }

        return $this->jsonResponse();

    }

    /**
     * Get movie info by imdb or tmdb id
     * @return mixed
     */
    public function load_movie_data()
    {
        $id = $this->request->getGet( 'id' );

        if($this->validate([
            'id' => 'required|valid_movie_id'
        ])) {

            if(! isValidImdbId($id)){
                $results = $this->dataAPI->setTmdbId( $id )
                    ->getMovie();
            }else{
                $results = $this->dataAPI->setImdbId( $id )
                                         ->getMovie();
            }

            if($results !== null) {

                $this->addData( $results->getData() );

            }else{

                $this->addError( 'data not found' );

            }

        }else{

            $this->addError( $this->validator->getErrors() );

        }

        return $this->jsonResponse();

    }

    /**
     * Get episode info by imdb id or season,epi numbers
     * @return mixed
     */
    public function load_episode_data()
    {
        $imdbId = $this->request->getGet( 'imdb_id' );
        $seriesId = $this->request->getGet( 'series_id' );
        $season = $this->request->getGet( 'season' );
        $episode = $this->request->getGet( 'episode' );

        if($this->validate([
            'imdb_id' => 'permit_empty|required_without[series_id]|valid_imdb_id',
            'series_id' => 'permit_empty|required_without[imdb_id]|is_natural_no_zero|exist[series.id]',
            'season' => 'permit_empty|required_with[series_id]|is_natural_no_zero',
            'episode' => 'permit_empty|required_with[series_id]|is_natural_no_zero'
        ])) {

            if(empty( $imdbId )) {
                //load from tmdb
                $seriesModel = new SeriesModel();
                $series = $seriesModel->where('id', $seriesId)->first();

                if(! empty($series->tmdb_id)){

                    $results = $this->dataAPI->setTmdbId( $series->tmdb_id )
                                             ->setImdbId( $series->imdb_id )
                                             ->getEpisode($season, $episode);

                    if($results !== null) {

                        $this->success();
                        $this->addData( $results->getData() );

                    }else{
                        $this->addError( 'data not found' );
                    }

                }

            }else{
                $results = $this->dataAPI->findByImdbId($imdbId, 'episode');

                if($results !== null) {

                    $this->success();
                    $this->addData( $results->getData() );
                }else{
                    $this->addError( 'data not found' );
                }

            }


        }else{
            $this->addError( $this->validator->getErrors() );
        }

        return $this->jsonResponse();

    }

    /**
     * Attempt to load next episode
     * @return mixed
     */
    public function load_next_episode()
    {
        $seriesId = $this->request->getGet('series_id');
        $season = $this->request->getGet('season');

        if($this->validate([
            'series_id' => 'required|is_natural_no_zero|exist[series.id]',
            'season' => 'permit_empty|is_natural'
        ])) {

            $seasonModel = new SeasonModel();
            $nextEpisodeInfo = $seasonModel->getNextTmpEpisodeInfo($seriesId, $season);

            $this->addData( $nextEpisodeInfo );

        }else{
            $this->addError( $this->validator->getErrors() );
        }


        return $this->jsonResponse();
    }

}