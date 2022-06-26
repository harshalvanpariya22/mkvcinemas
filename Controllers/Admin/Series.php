<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Season;
use App\Models\GenreModel;
use App\Models\MovieModel;
use App\Models\SeasonModel;
use CodeIgniter\Model;


class Series extends BaseController
{

    protected $model;

    public function __construct()
    {
        $this->model = new \App\Models\SeriesModel();
    }

    public function index()
    {
        $title = 'TV Shows';
        $series = $this->model->orderBy('id', 'desc')
            ->findAll();

        $topBtnGroup = create_top_btn_group([
            'admin/series/new' => 'Add TV Show'
        ]);

        if(! empty($series)){
            foreach ($series as $val) {
                $seasonsModel = new SeasonModel();
                $completedEpisodes = $seasonsModel->join('movies', 'movies.season_id = seasons.id', 'LEFT')
                                         ->select('movies.id')
                                         ->groupBy('movies.id')
                                         ->countAllResults();

                $completionRate = 0;
                $pendingEpisodes = 0;
                if($val->total_episodes > 0 && $completedEpisodes <= $val->total_episodes){
                    $completionRate = round( $completedEpisodes / $val->total_episodes * 100 );
                    $pendingEpisodes = $val->total_episodes - $completedEpisodes;
                }






                $val->completed_episodes = $completedEpisodes;
                $val->pending_episodes = $pendingEpisodes;
                $val->completion_rate = $completionRate;

            }
        }

        $title .= ' - ( ' . count( $series ) . ' )';

        $data = [
            'title' => $title,
            'series' => $series,
            'topBtnGroup' => $topBtnGroup
        ];
        return view('admin/series/list', $data);
    }

    public function new()
    {
        $title = 'New TV Show';
        $series = new \App\Entities\Series();
        $series->imdb_id = $this->request->getGet('imdb');
        $series->tmdb_id = $this->request->getGet('tmdb');

        $topBtnGroup = create_top_btn_group([
            'admin/series' => 'Back to TV Shows'
        ]);

        $genreModel = new GenreModel();
        $genres = $genreModel->asArray()->findAll();

        $data = [
            'title' => $title,
            'series' => $series,
            'genres' => $genres,
            'topBtnGroup' => $topBtnGroup
        ];

        return view('admin/series/new', $data);
    }

    public function edit($id)
    {
        $title = 'Edit TV Show';
        $series = $this->getSeries( $id );

        $topBtnGroup = create_top_btn_group([
            "admin/episodes/new?series_id={$series->id}" => 'Add Episode',
            'admin/series' => 'Back to TV Shows'
        ]);

        $genreModel = new GenreModel();
        $genres = $genreModel->asArray()->findAll();

        $seasonModel = new SeasonModel();
        $seasons = $seasonModel->withEpisodes()
                               ->findBySeriesId($id);

        $data = [
            'title' => $title,
            'series' => $series,
            'genres' => $genres,
            'seasons' => $seasons,
            'topBtnGroup' => $topBtnGroup
        ];

        return view('admin/series/edit', $data);
    }

    public function create()
    {
        $warningAlerts = [];

        if($this->request->getMethod() == 'post') {

            $series = new \App\Entities\Series( $this->request->getPost() );

            if(! is_media_download_to_server()){
                $series->poster = $series->poster_url;
                $series->banner = $series->banner_url;
            }

            if($this->model->insert( $series )) {

                $series = $this->getSeries( $this->model->getInsertID() );

                $this->model->addGenres(
                    $series->id,
                    $this->request->getPost( 'genres' )
                );

                if( is_media_download_to_server() ){
                    $this->saveMediaFiles( $series );
                }

                if($this->validator !== null) {
                    $warningAlerts = $this->validator->getErrors();
                }

                return redirect()->to('/admin/series/edit/' . $series->id )
                                 ->with('warning', $warningAlerts)
                                 ->with('success', 'New TV show created successfully');

            }else{

                return redirect()->back()
                                 ->with('errors', $this->model->errors())
                                 ->withInput();

            }

        }
    }

    public function update($id)
    {
        $warningAlerts = [];

        if($this->request->getMethod() == 'post') {

            $series = $this->getSeries( $id );
            $updatedData = $this->request->getPost([
                'title',
                'imdb_id',
                'tmdb_id',
                'total_seasons',
                'total_episodes',
                'released_at',
                'country',
                'language',
                'imdb_rate',
                'status'
            ]);

            if(! is_media_download_to_server()){
                if(! empty( $this->request->getPost('poster_url') )){
                    $series->poster = $this->request->getPost('poster_url');
                }
                if(! empty( $this->request->getPost('banner_url') )){
                    $series->banner = $this->request->getPost('banner_url');
                }
            }

            $series->fill( $updatedData );

            if($series->hasChanged()) {

                if(! $this->model->save($series)) {

                    return redirect()->back()
                                     ->with('errors', $this->model->errors())
                                     ->withInput();

                }

            }

            $this->model->addGenres(
                $series->id,
                $this->request->getPost( 'genres' )
            );

            if( is_media_download_to_server() ){
                $this->saveMediaFiles( $series );
            }
            $this->updateSeasonData();

            if($this->validator !== null) {
                $warningAlerts = $this->validator->getErrors();
            }

            return redirect()->back()
                             ->with('warning', $warningAlerts)
                             ->with('success', 'TV show updated successfully');

        }
    }

    public function delete( $id )
    {
        $series = $this->getSeries( $id );

        if($this->model->delete( $series->id )) {
            return redirect()->to('admin/series')
                             ->with('success', "{$series->title} show deleted successfully" );
        }else{
            return redirect()->back()
                             ->with('errors', "{$series->title} show unable to deleted" );
        }

    }

    public function completed($id): \CodeIgniter\HTTP\RedirectResponse
    {
        $isDone = $this->request->getGet('done') == 1;
        $series = $this->getSeries($id);

        if($this->model->completed($series->id, $isDone)){
            $isNot = ! $isDone ? 'not' : '';
            return redirect()->back()
                            ->with('success', "{$series->title} mark as {$isNot} completed successfully" );
        }

        return redirect()->back()
                         ->with('errors', "Something went wrong" );
    }

    protected function updateSeasonData()
    {

        $totalSeaEpisodes = $this->request->getPost('total_season_episodes');

        if(! empty($totalSeaEpisodes) && is_array($totalSeaEpisodes)) {

            $seasonModel = new SeasonModel();

            foreach ($totalSeaEpisodes as $seaId => $seaEpisodes) {
                $season = $seasonModel->where('id', $seaId)
                                      ->first();
                if($season !== null){
                    $season->fill([ 'total_episodes' => $seaEpisodes ]);
                    if($season->hasChanged()){
                        $seasonModel->save($season);
                    }
                }
            }

        }
    }

    protected function saveMediaFiles(\App\Entities\Series $series)
    {
        $imageValidationRules = [];

        $posterFile = $this->request->getFile('poster_file');
        $bannerFile = $this->request->getFile('banner_file');

        $posterUrl = $this->request->getPost('poster_url');
        $bannerUrl = $this->request->getPost('banner_url');

        if(! $posterFile->isValid()) $posterFile = null;
        if(! $bannerFile->isValid()) $bannerFile = null;

        if($posterFile !== null) {
            $imageValidationRules['poster_file'] = [
                'label' => 'poster image',
                'rules' => 'uploaded[poster_file]'
                    . '|is_image[poster_file]'
                    . '|mime_in[poster_file,image/jpg,image/jpeg,image/png]'
                    . '|max_size[poster_file,2048]'
            ];
        }

        if($bannerFile !== null) {
            $imageValidationRules['banner_file'] =[
                'label' => 'banner image',
                'rules' => 'uploaded[banner_file]'
                    . '|is_image[banner_file]'
                    . '|mime_in[banner_file,image/jpg,image/jpeg,image/png]'
                    . '|max_size[banner_file,4096]'
            ];
        }

        if(! empty($imageValidationRules) ) {

            $this->validate( $imageValidationRules );

            if($this->validator->hasError('poster_file'))
                $posterFile = null;

            if($this->validator->hasError('banner_file'))
                $bannerFile = null;

        }



        if(! empty($posterUrl)){
            helper('remote_download');
            if($filepath =  download_image( $posterUrl)){
                $posterFile = new \CodeIgniter\Files\File( $filepath );
            }
        }

        if(! empty($bannerUrl)){
            helper('remote_download');
            if($filepath =  download_image( $bannerUrl )){
                $bannerFile = new \CodeIgniter\Files\File( $filepath );
            }
        }


        if($posterFile !== null){
            //remote old poster file if exist
            $series->addPoster( $posterFile );
        }

        if($bannerFile !== null){
            //remote old banner file if exist
            $series->addBanner( $bannerFile );
        }


        if($series->hasChanged()) {
            $this->model->protect(false)
                ->save( $series );
        }



    }

    protected function getSeries($id) : \App\Entities\Series
    {
        $series = $this->model->where('id', $id)
            ->first();
        if($series == null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid series Id ' . $id);
        }
        return $series;
    }

}
