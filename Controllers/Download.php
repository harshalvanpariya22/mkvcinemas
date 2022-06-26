<?php

namespace App\Controllers;


use App\Libraries\UniqToken;
use App\Models\AdsModel;
use App\Models\LinkModel;
use App\Models\MovieModel;
use CodeIgniter\Model;


class Download extends Embed
{

    public function view( $uniqId, $sea = null, $epi = null )
    {
        $title = '';
        $movie = $links = $activeMovie = null;
        $metaKeywords = $metaDescription = '';
        $nextEpisode = $prevEpisode = null;
        $directLinks = $torrentLinks = [];

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

            $movieModel = new MovieModel();
            $activeMovie = $this->getMovie( $uniqId, $sea, $epi);

            if($activeMovie !== null){

                // Title and Meta data
                $title = 'Download ' . $activeMovie->getMovieTitle();
                $metaKeywords = $activeMovie->meta_keywords;
                $metaDescription = $activeMovie->meta_description;

                $linkModel = new LinkModel();

                $directLinks = $linkModel->findByMovieId( $activeMovie->id, 'direct_download', false);

                if(! empty($directLinks))
                    $directLinks = create_links_group( $directLinks );


                $torrentLinks = $linkModel->findByMovieId( $activeMovie->id, 'torrent_download', false);
                if(! empty($torrentLinks))
                    $torrentLinks = create_links_group( $torrentLinks );


                //load next episodes
                if($activeMovie->isEpisode()){

                    if($activeMovie instanceof \App\Entities\Movie){
                        $movieModel = new MovieModel();

                        $nextEpisode = $movieModel->getNextEpisode( $activeMovie );
                        $prevEpisode = $movieModel->getPrevEpisode( $activeMovie );

                    }

                }

            }

        }

        if(! empty($directLinks)){
            $links['directLinks'] = [
                'label' => lang('Download.direct_download_links'),
                'links' => $directLinks
            ];
        }

        if(! empty($torrentLinks)){
            $links['torrentLinks'] = [
                'label' => lang('Download.torrent_download_links'),
                'links' => $torrentLinks
            ];
        }

        //ads codes
        $adsModel = new AdsModel();
        $ads = $adsModel->forView()
                        ->getAds('download');


        $data = compact('activeMovie', 'links','nextEpisode','prevEpisode', 'ads','metaKeywords', 'metaDescription', 'title');




        if(is_web_page_cache_enabled()){
            $this->cachePage( web_page_cache_time() );
        }

        return view(theme_path('download'), $data);
    }



}