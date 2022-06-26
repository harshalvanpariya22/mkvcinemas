<?php

namespace App\Controllers;


use App\Libraries\ReqIdentity;
use App\Libraries\UniqToken;
use App\Models\LinkModel;
use App\Models\MovieModel;
use App\Models\SeriesModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Model;


class Ajax extends BaseAjax
{

    public function get_stream_link()
    {

        //validate captcha
        if(get_config('is_stream_gcaptcha_enabled') ){

            helper('captcha');

            $success = false;
            $captchaResponse = $this->request->getGet('captcha');

            if(! empty($captchaResponse)){
                if(validate_gcaptcha( $captchaResponse )){
                    $success = true;
                }
            }

            if(! $success){

                $this->addError('Captcha validate failed. try again');
                return $this->jsonResponse();

            }

        }


        $linkId = decode_id( $this->request->getGet( 'id' ) );
        $movieId = decode_id( $this->request->getGet( 'movie' ) );

        $validation = service('validation');
        if( $validation->check($linkId, 'required|is_natural_no_zero|exist[links.id]')
            && $validation->check($movieId, 'required|is_natural_no_zero|exist[links.movie_id]')){

            $linkModel = new LinkModel();
            $link = $linkModel->where('id', $linkId)
                              ->where('movie_id', $movieId)
                              ->where('type', 'stream')
                              ->first();

            if($link !== null){

                //update request in the link
                $linkModel->updateRequests( $link->id );
                $this->addData( ['link' => $link->link] );

                /*
                 * if this is first request, we will attempt to update
                 * requests in the movies/episodes
                 * */
                if($this->request->getGet('is_init') == 'false'){

                    $movieModel = new MovieModel();
                    $movie = $movieModel->getMovie( $link->movie_id );

                    //add view
                    if(! is_movie_viewed( $movie->imdb_id )) {
                        $movieModel->updateViews( $movie->id );
                        movie_viewed( $movie->imdb_id );
                    }

                    //save in watch history
                    service('watch_history')->add( $movie->id )->save();

                    //save in recommend
                    if( is_web_page_cache_enabled() ){
                        service('recommend')->detect( $movie );
                    }

                }

                //set active link token
                $tokenData = [ 'stream_link', $link->id ];
                $token = UniqToken::create( $tokenData );

                if($token !== null){
                    $this->addData(['token' => $token]);
                }

            }

        }

        return $this->jsonResponse();

    }

    public function report_download_link()
    {

        if(! is_links_report_enabled() ) {

            throw new PageNotFoundException('report system not enabled');

        }

        $token = $this->request->getGet('token');
        $isNotWorking = $this->request->getGet('reason') == 'not_working';

        $linkId = $movieId = 0;

        //decode token
        if($tokenData = UniqToken::decode( $token )){
            list($movieId, $linkId) = $tokenData;
            //validate link id
            if(! is_numeric( $linkId ) || $linkId <= 0){
                $linkId = 0;
            }
        }


        if(! empty($linkId)){

            $linkModel = new LinkModel();

            //get link
            $link = $linkModel->getLink( $linkId );
            if($link !== null && $link->movie_id == $movieId){

                //identity user request
                $reqIdentity = new ReqIdentity( $linkId );
                if($reqIdentity->isNew()){

                    $linkModel = new LinkModel();
                    $linkModel->where('type !=', 'stream')
                              ->report( $linkId, $isNotWorking );

                    $this->success();

                    //detect user request identity
                    $reqIdentity->detect();

                }else{

                    $this->addError('already reported');

                }

            }

        }


        return $this->jsonResponse();
    }

    public function report_stream_link()
    {
        if(! is_links_report_enabled() ) {

            throw new PageNotFoundException('report system not enabled');

        }

        //validate token
        $token = $this->request->getGet('token');
        $isNotWorking = $this->request->getGet('reason') == 'not_working';

        if($tokenData = UniqToken::decode( $token )){

            if(count( $tokenData ) == 2) {

                list($label, $linkId) = $tokenData;

                $validation = service('validation');

                if($validation->check($linkId, 'required|is_natural_no_zero|exist[links.id]')
                    && $label == 'stream_link') {

                    $reqIdentity = new ReqIdentity( $linkId );
                    if($reqIdentity->isNew()){

                        $linkModel = new LinkModel();
                        $linkModel->where('type', 'stream')
                                  ->report( $linkId, $isNotWorking);

                        $this->success();
                        $reqIdentity->detect();

                    }else {

                        $this->addError('already reported');

                    }


                }else{

                    $this->addError( 'data validation failed' );

                }

            }

        }else{

            $this->addError('Invalid Token');

        }

        return $this->jsonResponse();
    }

    public function get_suggest()
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

                ob_start();

                foreach ($results as $result) {
                    echo '<div class="col-6 col-lg-3 px-5">';
                    the_req_movie_item( $result );
                    echo '</div>';
                }

                $content .= ob_get_clean();

            }

            $this->addData( ['results' => $content] );
        }

        return $this->jsonResponse();
    }

}