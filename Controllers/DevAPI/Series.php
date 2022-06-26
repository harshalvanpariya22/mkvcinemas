<?php

namespace App\Controllers\DevAPI;


class Series extends BaseApi
{


    public function create()
    {
        ignore_user_abort(true);
        set_time_limit(0);

        $validationRules = [
            'imdb' => 'required|valid_imdb_id',
            'sea' => 'permit_empty|is_natural_no_zero',
            'epi' => 'permit_empty|is_natural_no_zero'
        ];
        $validationMsg = [  'imdb' => ['required' => 'Imdb Id is required'] ];

        if($this->validate( $validationRules, $validationMsg )) {

            $imdb = $this->request->getGet('imdb');
            $season = $this->request->getGet('sea');
            $episode = $this->request->getGet('epi');

            //create episodes import pattern
            if(! empty( $season )){

                $pattern = 1;

                if(! empty( $episode )){
                    $pattern .= ".{$episode}-{$episode}";
                }
                $imdb .= "[$pattern]";
            }

            if($results = $this->import( [ $imdb ], 'series' )){

                //successfully imported
                $this->addData( array_shift( $results ) );
                $this->success();

            }


        }else{

            $this->addError( $this->validator->getErrors() );

        }

        return $this->jsonResponse();
    }


}