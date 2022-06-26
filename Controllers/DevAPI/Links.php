<?php

namespace App\Controllers\DevAPI;


use App\Models\LinkModel;
use App\Models\MovieModel;

class Links extends BaseApi
{

    public function add()
    {

        $validationRules = [
            'imdb' => 'required|valid_imdb_id',
            'links' => 'required',
            'type' => 'required|in_list[stream,direct_download,torrent_download]'
        ];

        $results = [];

        if($this->validate( $validationRules )){

            $imdbId = $this->request->getGet('imdb');
            $type = $this->request->getGet('type');
            $links = str_to_array( $this->request->getGet('links') );

            //find movie
            $movieModel = new MovieModel();
            $movie = $movieModel->getMovieByImdbId( $imdbId );

            if($movie !== null){

                $linkModel = new LinkModel();

                foreach ($links as $link) {

                    $success = false;

                    $data = [
                        'movie_id' => $movie->id,
                        'link' =>  $link,
                        'type' => $type
                    ];

                    if($linkModel->insert( $data )){

                        $success = true;

                    }

                    $resp = [
                        'link' => $link,
                        'success' => $success
                    ];

                    $results[] = $resp;
                }

                $this->success();
                $this->addData( $results );

            }else{

                $this->addError( 'Movie not found' );

            }

        }else {

            $this->addError( $this->validator->getErrors() );

        }

        return $this->jsonResponse();

    }

}