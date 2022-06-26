<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MovieModel;
use App\Models\SeriesModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Model;
use Config\Services;

class Api extends BaseController
{
    use ResponseTrait;

    protected $success = false;
    protected $msg = null;
    protected $data = null;


    public function index()
    {
        if(is_referer_blocked()){
            throw new PageNotFoundException('API unavailable');
        }

        $title = 'API Usage';
        return view(theme_path('api'), compact('title'));
    }


    public function status()
    {
        $item = null;

        $imdbId = $this->request->getGet('imdb');
        $tmdbId = $this->request->getGet('tmdb');
        $season = $this->request->getGet('sea');
        $episode = $this->request->getGet('epi');
        $type = $this->request->getGet('type');
        $isMovie = $type == 'movie';

        //check rate limitation
        $rateLimit = get_config('api_status_check_rate_limit');
        if($this->isRateExceeded( $rateLimit, 'status' )){
            return $this->rateExceeded()->jsonResponse();
        }

        //create validation rules
        $validateRules = [
            'type' => 'in_list[movie,tv]'
        ];

        if(! empty( $imdbId ) || empty( $tmdbId ) )
            $validateRules['imdb'] = 'required|valid_imdb_id';
        else
            $validateRules['tmdb'] = 'required|valid_tmdb_id';

        if($type == 'tv'){
            $validateRules['sea'] = 'required|is_natural_no_zero';
            $validateRules['epi'] = 'required|is_natural_no_zero';
        }

        //validation messages
        $validateMsg = [
            'sea' => [
                'required' => 'Season is required',
                'is_natural_no_zero' => 'Invalid season'
            ],
            'epi' => [
                'required' => 'Episode is required',
                'is_natural_no_zero' => 'Invalid episode'
            ]
        ];

        if($this->validate( $validateRules, $validateMsg )){

            $uniqId = ! empty( $imdbId ) ? $imdbId : $tmdbId;
            $movieModel = new MovieModel();
            $seriesModel = new SeriesModel();

            if( $isMovie ){

                $item = $movieModel->select('movies.id')
                                   ->forView('movie')
                                   ->getMovieByUniqId( $uniqId, null, false );

            }else{

                $series = $seriesModel->getSeriesByUniqId( $uniqId );

                if($series !== null){

                    $item = $movieModel->select('movies.id')
                                       ->forView('episode')
                                       ->getEpisode($series->id, $season, $episode);

                }

            }

            $label = $isMovie ? 'movie' : 'episode';
            //check item
            if($item !== null) {
                $this->success = true;
                $this->addMsg( "This {$label} has in our database" );
            }else{
                $this->addMsg( "This {$label} hasn't in our database" );
            }

        }else{


            $validateErrors = $this->validator->getErrors();
            $this->addMsg( array_shift( $validateErrors ) );

        }


        return $this->jsonResponse();
    }

    protected function jsonResponse()
    {
        $response = [
            'status' => $this->success ? 'success' : 'failed',
            'msg' => $this->msg
        ];
        if(! empty( $this->data ))
            $response['data'] = $this->data;

        return $this->setResponseFormat('json')->respond( $response );
    }

    protected function rateExceeded()
    {
        $this->addMsg( 'Daily rate limit exceeded');
        return $this;
    }

    protected function addMsg( $msg )
    {
        $this->msg = $msg;
    }

    protected function isRateExceeded($rateLimit = 0, $t = 'l'): bool
    {
        //check rate limit
        if($rateLimit > 0){

            $throttle = Services::throttler();

            //Restrict an IP address to api request links per day
            if(! $throttle->check(md5($this->request->getIPAddress() . '_api_' . $t), $rateLimit, DAY)){
                //too many requests
                return true;
            }

        }

        return false;
    }



}
