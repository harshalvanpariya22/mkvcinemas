<?php

namespace App\Controllers\Admin;


use App\Models\LinkModel;
use App\Models\MovieModel;
use App\Models\Translations\MovieTranslationsModel;
use App\Models\SeasonModel;
use App\Models\SeriesModel;



class Episodes extends Movies
{


    public function new()
    {
        $title = 'New Episode';
        $episode = new \App\Entities\Movie();

        $activeSeriesId = $this->request->getGet( 'series_id' );
        $activeSeasonNum = $this->request->getGet( 'sea' );

        if(! empty($activeSeriesId)){
            $seasonModel = new SeasonModel();
            $nextEpisodeInfo = $seasonModel->getNextTmpEpisodeInfo($activeSeriesId,$activeSeasonNum);

            $episode->nextSeason = $nextEpisodeInfo['nextSeason'];
            $episode->nextEpisode = $nextEpisodeInfo['nextEpisode'];
        }

        $seriesModel = new SeriesModel();
        $series = $seriesModel->orderBy('id', 'desc')
                              ->where('is_completed', 0)
                              ->select(['id','title'])
                              ->asArray()
                              ->findAll();

        //Translations
        $translationModel = new MovieTranslationsModel();
        $translations = $translationModel->getDummyList();

        $topBtnGroup = create_top_btn_group([
            'admin/episodes' => 'Back to Episodes'
        ]);

        $data = [
            'title' => $title,
            'movie' => $episode,
            'series' => $series,
            'activeSeriesId' => $activeSeriesId,
            'topBtnGroup' => $topBtnGroup,
            'translations' => $translations
        ];

        if(empty( $series )) {
            session()->setFlashdata('errors', 'You must add TV show before create a episode');
        }

        return view('admin/episodes/new', $data);
    }

    public function edit($id)
    {
        $title = 'Edit Episode';
        $movie = $this->getEpisode( $id );


        $seriesModel = new SeriesModel();
        $series = $seriesModel->orderBy('id', 'desc')
            ->where('is_completed', 0)
            ->select(['id','title'])
            ->asArray()
            ->findAll();

        $linkModel = new LinkModel();
        $directDownloadLinks = $linkModel->findByMovieId( $id, 'direct_download');
        $torrentDownloadLinks = $linkModel->findByMovieId( $id, 'torrent_download');
        $streamLinks = $linkModel->findByMovieId( $id, 'stream' );

        //Translations
        $translationModel = new MovieTranslationsModel();
        $translations = $translationModel->findByMovieId( $id );

        $topBtnGroup = create_top_btn_group([
            'admin/episodes/new' => 'New Episode',
            'admin/episodes' => 'Back to Episodes',
        ]);

        $data = compact('title', 'movie', 'series', 'directDownloadLinks',
            'torrentDownloadLinks', 'streamLinks', 'translations', 'topBtnGroup');

        if(empty( $series )) {
            session()->setFlashdata('errors', 'You must add TV show before create a episode');
        }

        return view('admin/episodes/edit', $data);
    }

    protected function getSeasonId()
    {
        $seriesId = $this->request->getPost('series_id');
        $seasonNumber = $this->request->getPost('season');
        $episodeNumber = $this->request->getPost('episode');
        $seasonId = null;

        $validationRules = [
            'series_id' => [
                'label' => 'series',
                'rules' => 'required|exist[series.id]|is_natural_no_zero'
            ],
            'season'    => 'required|is_natural_no_zero',
            'episode'   => 'required|is_natural_no_zero'
        ];

        if($this->validate($validationRules)) {


            $seasonId = $seasonModel->getSeasonId($seriesId, $seasonNumber );

        }

        return $seasonId;

    }

    public function save($id = null)
    {
        if($this->request->getMethod() == 'post'){

            $validationRules = [
                'series_id' => [
                    'label' => 'series',
                    'rules' => 'required|exist[series.id]|is_natural_no_zero'
                ],
                'season'    => 'required|is_natural_no_zero',
                'episode'   => 'required|is_natural_no_zero'
            ];

            if($this->validate($validationRules)) {

                $postData = $this->request->getPost();

                $this->request->setGlobal('post', $postData);

                return empty($id) ? parent::create() : parent::update( $id );


            }else{

                return redirect()->back()
                    ->with('errors', $this->validator->getErrors())
                    ->withInput();

            }


        }
    }


    protected function getEpisode($id) : \App\Entities\Movie
    {
        $movieModel = new MovieModel();
        $movie = $movieModel->episodes()
                            ->where('movies.id', $id)
                            ->first();
        if($movie == null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid episode Id ' . $id);
        }
        return $movie;
    }

}
