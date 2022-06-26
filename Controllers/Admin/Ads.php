<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdsModel;

class Ads extends BaseController
{

    protected $model;

    public function __construct()
    {
        $this->model = new AdsModel();
    }

    public function index()
    {
        //
    }

    public function home_page()
    {
        $title = 'Ads - Home Page ';

        $ads = $this->model->where('page', 'home')
                           ->findAll();

        $topAd = $ads['home.banner.top'] ?? '';
        $playerRightAd = $ads['home.banner.player-right'] ?? '';
        $playerBottomAd = $ads['home.banner.player-bottom'] ?? '';
        $popAds = $ads['home.popad'] ?? '';


        $data = compact('title', 'topAd', 'playerRightAd', 'playerBottomAd', 'popAds');

        return view('admin/ads/home', $data);
    }

    public function embed_page()
    {
        $title = 'Ads - Embed Page ';

        $ads = $this->model->where('page', 'embed')
            ->findAll();

        $popAds = $ads['embed.popad'] ?? '';

        $data = compact('title', 'popAds');

        return view('admin/ads/embed', $data);
    }

    public function view_page()
    {
        $title = 'Ads - View Page ';

        $ads = $this->model->where('page', 'view')
            ->findAll();

        $playerTopAd = $ads['view.banner.player-top'] ?? '';
        $playerBottomAd = $ads['view.banner.player-bottom'] ?? '';
        $playerSidebarAd = $ads['view.banner.sidebar'] ?? '';
        $popAds = $ads['view.popad'] ?? '';

        $data = compact('title', 'playerTopAd',  'playerBottomAd','playerSidebarAd', 'popAds');

        return view('admin/ads/view', $data);
    }

    public function download_page()
    {
        $title = 'Ads - Download Page ';

        $ads = $this->model->where('page', 'download')
                           ->findAll();

        $playerTopAd = $ads['download.banner.title-bottom'] ?? '';
        $playerBottomAd = $ads['download.banner.links-group-middle'] ?? '';
        $popAds = $ads['download.popad'] ?? '';

        $data = compact('title', 'playerTopAd',  'playerBottomAd', 'popAds');

        return view('admin/ads/download', $data);
    }


    public function link_page()
    {
        $title = 'Ads - Link Page ';

        $ads = $this->model->where('page', 'link')
            ->findAll();

        $counterTop = $ads['link.banner.counter-top'] ?? '';
        $counterBottom = $ads['link.banner.counter-bottom'] ?? '';
        $popAds = $ads['link.popad'] ?? '';

        $data = compact('title', 'counterTop',  'counterBottom', 'popAds');

        return view('admin/ads/link', $data);
    }

    public function update()
    {
        $data = $this->request->getPost();
        $ads = $data['ads'] ?? [];

        if(! empty($ads)) {
            foreach ($ads as $key => $val) {

                if(empty($val['id']))
                    continue;

                $ad = $this->model->where('id', $val['id'])
                                  ->first();

                if($ad !== null){

                    $val['ad_code'] = base64_encode( $val['ad_code'] );
                    $ad->fill( $val );


                    if($ad->hasChanged()){

                        $this->model->save( $ad);

                    }

                }

            }
        }


        return redirect()->back()
                         ->with('success', 'data updated successfully');


    }



}
