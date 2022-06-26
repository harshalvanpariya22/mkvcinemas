<?php

namespace App\Controllers;

use App\Libraries\HashIds\HashIds;
use App\Libraries\UniqToken;
use App\Models\AdsModel;
use App\Models\FailedMovies;
use App\Models\MovieModel;
use App\Models\RequestsModel;
use App\Models\RequestSubscriptionModel;

class Home extends BaseController
{
    public function index()
    {




        $title = ! empty( get_config( 'site_title' ) )  ? get_config( 'site_title' ) : 'Home';
        $metaKeywords = get_config('site_keywords');
        $metaDescription = get_config('site_description');

        $movieModel = new MovieModel();
        $trendingMovies = [];
        $latestMovies = $latestMovies = [];


        //recently movies
        if(! empty( get_config('home_items_per_page') )){

            $latestMovies = $movieModel->orderBy('movies.id', 'desc')
                                       ->limit( get_config('home_items_per_page') )
                                       ->forView()
                                       ->movies()
                                       ->allMovies()
                                       ->find();
        }


        $activeMovie = ! empty( $latestMovies ) ? $latestMovies[0] : null;

        //trending movies
        if(! empty( get_config('items_per_trending_page') ) ){

            $trendingMovies = $movieModel->allMovies()
                                         ->movies()
                                         ->trending()
                                         ->forView()
                                         ->limit(6)
                                         ->find();

        }



        if($activeMovie == null){

            $activeMovie = $movieModel->episodes()
                                      ->orderBy('id', 'desc')
                                      ->first();

        }

        //ads codes
        $adsModel = new AdsModel();
        $ads = $adsModel->forView()
                        ->getAds('home');


        $data = compact('latestMovies','trendingMovies', 'activeMovie', 'ads',
            'title', 'metaKeywords', 'metaDescription');

        return view(theme_path('home'), $data);
    }
}
