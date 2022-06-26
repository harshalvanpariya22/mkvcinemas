<?php

namespace App\Controllers\DevAPI;


use App\Models\MovieModel;

class Movie extends BaseApi
{
    protected $model;

    public function __construct()
    {
        $this->model = new MovieModel();
    }

    public function create()
    {
        ignore_user_abort(true);
        set_time_limit(1800);

        $validationRules = [ 'imdb' => 'required' ];
        $validationMsg = [  'imdb' => ['required' => 'Imdb Id is required'] ];

        if($this->validate($validationRules, $validationMsg)){

            $imdbIds = str_to_array( $this->request->getGet('imdb') );
            if($results = $this->import( $imdbIds )){

                //successfully imported
                $this->addData( $results );
                $this->success();

            }

        }else{

            $this->addError( $this->validator->getError() );

        }

        return $this->jsonResponse();
    }


    public function get()
    {

        $selection = ['imdb_id', 'tmdb_id', 'title', 'description', 'duration', 'poster',
            'banner', 'year', 'imdb_rate', 'released_at', 'trailer', 'language', 'country'];

        if(isset( $_GET['imdb'] )){

            //specific movies
            $validationRules = [ 'imdb' => 'required' ];
            $validationMsg = [  'imdb' => ['required' => 'Imdb Id is required'] ];

            //validate
            if($this->validate( $validationRules, $validationMsg )){

                $imdbId = $this->request->getGet('imdb');

                //attempt to get movie
                $movie = $this->model->movies()
                                     ->select( $selection )
                                     ->where('imdb_id', $imdbId)
                                     ->asArray()
                                     ->first();
                if($movie !== null){

                    $this->success();
                    $this->addData( $movie );

                }else{

                    $this->addError('Movie not found');

                }

            }else{

                $this->addError( $this->validator->getErrors() );

            }


        }else{
            //all movies
            $movies = $this->model->movies()
                                  ->orderBy('created_at', 'desc')
                                  ->select( $selection )
                                  ->asArray()
                                  ->paginate(1);

            $pager = $this->model->pager;
            $data = [
                'total_pages' => $pager->getPageCount(),
                'current_page' => $pager->getCurrentPage(),
                'results' => $movies
            ];

            $this->success();
            $this->addData( $data );
        }

        return $this->jsonResponse();
    }






}