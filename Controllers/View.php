<?php

namespace App\Controllers;


use App\Models\AdsModel;
use App\Models\LinkModel;
use App\Models\MovieModel;
use App\Models\SeasonModel;
use App\Models\SeriesModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Model;


class View extends Embed
{

    public function view( $uniqId, $sea = null, $epi = null )
    {

        $title = '';

        $movie = $links = $seasons = null;
        $metaKeywords = $metaDescription = '';

        $validation = service('validation');

        $validation->setRules([
            'uniqId' => 'required|valid_movie_id',
            'sea' => 'permit_empty|is_natural_no_zero',
            'epi' => 'permit_empty|is_natural_no_zero',
        ]);

        $data = [
            'uniqId' => $uniqId,
            'sea' => $sea,
            'epi' => $epi
        ];

        if($validation->run($data)){

            $activeMovie = $this->getMovie( $uniqId, $sea, $epi);

            if($activeMovie !== null){

                // movie title and meta data
                $title = lang('Watch') . " {$activeMovie->getMovieTitle()}";
                $metaKeywords = $activeMovie->meta_keywords;
                $metaDescription = $activeMovie->meta_description;


                if($activeMovie->isEpisode()){

                    $seasonModel = new SeasonModel();
                    $seasons = $seasonModel->withEpisodes()
                                           ->findBySeriesId( $activeMovie->series_id );

                }

                //save in recommend
                service('recommend')->detect( $activeMovie );

            }else{

                throw new PageNotFoundException();

            }

        }

        //ads codes
        $adsModel = new AdsModel();
        $ads = $adsModel->forView()
                        ->getAds('view');

        if(is_web_page_cache_enabled()){

            $this->cachePage( web_page_cache_time() );

        }

        if(empty( $activeMovie )){

            throw new PageNotFoundException();

        }

        $data = compact('activeMovie', 'seasons', 'ads', 'metaKeywords', 'metaDescription', 'title');

        return view( theme_path('view') , $data);
    }



}