<?php

namespace App\Controllers;

use App\Models\GenreModel;
use App\Models\MovieModel;
use App\Models\SeriesModel;
use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Model;

class Library extends BaseController
{
    public function loadRecord($reqType = '')
    {

        //get filters data
        $filtersData = $this->request->getGet();
        $allowedActions = ['movies', 'shows'];
        $isParentShow = false;

        if (empty($reqType)) $reqType = 'movies';

        if (!in_array($reqType, $allowedActions)) {
            throw new PageNotFoundException('no valid arguments');
        }


        if (!empty($filtersData['data_type'])) {
            //filtered data type
            $filteredDataType = $filtersData['data_type'];
            //validate data type
            if (in_array($filteredDataType, $allowedActions)) {
                if (!empty($reqType)) {
                    /*
                     * check requested action and filtered data type
                     * If is it not equal, return to filtered data type page
                     */
                    if ($reqType != $filteredDataType) {
                        $this->request->uri->setPath("/library/{$filteredDataType}");
                        return redirect()->to($this->request->uri);
                    }
                }
                //priority for filtered data type
                $reqType = $filtersData['data_type'];
            }
        }


        //filter data type
        $type = $reqType == 'movies' ? 'movie' : 'episode';

        //check genres
        $genreIds = [];
        if (isset($filtersData['genres'])) {
            $genres = explode(',', strtolower($filtersData['genres']));
            $genreIds = $this->getCleanedGenres($genres);
            $filtersData['genres'] = $genres;
        }

        //check qualities
        $qualities = [];
        if (isset($filtersData['quality'])) {
            $qualities = explode(',', $filtersData['quality']);
            $qualities = $this->getCleanedQualities($qualities);
            $filtersData['quality'] = $qualities;
        }

        //year
        $year = $filtersData['year'] ?? '';
        if ($year == 'all' || !($year > 1900 && $year < 2050)) {
            $year = '';
        }

        //country
        $country = $filtersData['country'] ?? '';
        if($country == 'all') $country = '';

        //language
        $language = $filtersData['lang'] ?? '';
        if($language == 'all') $language = '';

        //Imdb rate
        $minImdbRate = $filtersData['imdb_rate'] ?? 0;
        if (!($minImdbRate > 0 && $minImdbRate <= 10)) {
            $minImdbRate = 0;
        }

        //show only parents
        $onlyParentShows = isset( $filtersData['parents'] ) && $filtersData['parents'] == 1  ? 1 : 0;

        //filters
        $filters = compact('genreIds', 'year', 'minImdbRate', 'type', 'qualities',
                            'country', 'language');

        //order results
        $sortBy = $filtersData['sort_by'] ?? '';
        $sortDir = $filtersData['sort_dir'] ?? '';

        $allowedSortKeys = ['created_at', 'title', 'imdb_rate', 'year'];
        if (!in_array($sortBy, $allowedSortKeys)) {
            $sortBy = 'created_at';
        }

        if ($sortDir != 'asc')
            $sortDir = 'desc';


        $movieModel = new MovieModel();


        if($type != 'movie' && $onlyParentShows){
            $isParentShow = true;
        }

        $type == 'movie' ? $movieModel->movies() : $movieModel->episodes(! $isParentShow, false);

        if(! $isParentShow){

            $movies = $movieModel->orderBy("movies.{$sortBy}", $sortDir)
                                 ->forView()
                                 ->allMovies($filters, true)
                                 ->paginate(get_config('library_items_per_page'));

        }else{

            $movies = $movieModel->orderBy("movies.{$sortBy}", $sortDir)
                                 ->forView()
                                 ->allMovies($filters, false, 'series.id')
                                 ->select('series.*')
                                 ->paginate(get_config('library_items_per_page'));

        }



        $years = $movieModel->where('type', $type)
                            ->orderBy('year', 'desc')
                            ->distinct()
                            ->select('year')
                            ->asArray()
                            ->find();

        if($type == 'movie'){
            $countries = $movieModel->getCountries();
            $languages = $movieModel->getLanguages();
        }else{
            $seriesModel = new SeriesModel();
            $countries = $seriesModel->getCountries();
            $languages = $seriesModel->getLanguages();
        }



        $years = !empty($years) ? extract_array_data($years, 'year') : [];
        arsort($years);

        $genreModel = new GenreModel();
        $genres = $genreModel->asArray()->findAll();

        $pager = $movieModel->pager;

        $title = $type == 'movie' ? lang('Library.movies_page_title') : lang('Library.tv_shows_page_title');

        return view(theme_path('library'),
            compact('movies', 'title', 'years',
                'filtersData', 'type', 'genres', 'pager', 'sortBy',
                'countries', 'languages')
        );
    }



    public function recent_releases($type = 'movies')
    {
        if(! in_array($type, ['movies','shows']) || empty( get_config( 'items_per_new_release_page' ))){
            throw new PageNotFoundException();
        }

        $isMovie = $type == 'movies';
        $fTitle = ! $isMovie ? 'tv_shows' : 'movies';

        //Page Title
        $title = sprintf(lang('Page.recent_releases_' . $fTitle), '','');

        if( $isMovie ){

            $movieModel = new MovieModel();
            $movies = $movieModel->orderBy("movies.released_at", 'desc')
                                 ->forView()
                                 ->movies()
                                 ->allMovies()
                                 ->limit( get_config('items_per_new_release_page') )
                                 ->find();

        }else{

            $seriesModel = new SeriesModel();
            $movies = $seriesModel->orderBy('released_at', 'desc')
                                  ->limit( get_config('items_per_new_release_page') )
                                  ->find();

        }

        // Formatted Page Title
        $formattedTitle = sprintf(lang('Page.recent_releases_' . $fTitle), '<span class="text-primary">','</span>');
        $basePage = 'recent-releases';

        $data = compact('title', 'formattedTitle', 'movies', 'basePage', 'isMovie');
        return view(theme_path('list'), $data);
    }

    public function recommend($type = 'movies')
    {
        if(! in_array($type, ['movies','shows']) || empty( get_config( 'items_per_recommend_page' ))){
            throw new PageNotFoundException();
        }

        $isMovie = $type == 'movies';
        $fTitle = ! $isMovie ? 'tv_shows' : 'movies';
        $title = sprintf(lang('Page.recommend_' . $fTitle), '','');

        $maxResults = get_config( 'items_per_recommend_page' );
        if(empty( $maxResults )) $maxResults = 12;

        $maxGenres = 5;

        $totalReqs = 0;
        $results = service('recommend')->movies()->init()->get();

        $watchedMovieIds = service('watch_history')->get();

        $movieModel = new MovieModel();
        $movies = [];


        if(! empty($results)){
            arsort($results);

            $totalReqs = array_sum($results);
            $genreIds = array_keys($results);

            $selected = $watchedMovieIds;

            if($totalReqs > 0){
                foreach ($genreIds as $id) {

                    $percentage = round( $results[$id] / $totalReqs * 100 );
                    if($percentage > 0){
                        $limit = floor( $maxResults * $percentage / 100 );
                        if($limit > 0){

                            $isMovie ? $movieModel->movies() : $movieModel->episodes();
                            $t = $isMovie ? 'movie' : 'episode';

                            if(! empty($selected)){
                                $movieModel->whereNotIn('movies.id', $selected);
                            }

                            $allMovies = $movieModel->forView()
                                                    ->allMovies( [ 'genreIds'=>[$id], 'type' => $t ] )
                                                    ->orderBy('movies.id', 'RANDOM')
                                                    ->limit( $limit )
                                                    ->find();

                            foreach ($allMovies as $movie) {
                                $selected[] = $movie->id;
                            }

                            $movies = array_merge($movies, $allMovies);

                        }
                        unset($results[$id]);
                    }

                    $maxGenres -= 1;
                    if($maxGenres == 0){
                        break;
                    }

                }
            }


        }


        $formattedTitle = sprintf(lang('Page.recommend_' . $fTitle), '<span class="text-success">','</span>');
        $basePage = 'recommend';


        $data = compact('title', 'formattedTitle', 'movies', 'basePage', 'isMovie');
        return view(theme_path('list'), $data);

    }

    public function history($type = 'movies')
    {
        if(! in_array($type, ['movies','shows']) || empty( get_config( 'watch_history_limit' ))){
            throw new PageNotFoundException();
        }

        $isMovie = $type == 'movies';
        $fTitle = ! $isMovie ? 'tv_shows' : 'movies';
        $title = sprintf(lang('Page.watch_history_' . $fTitle), '','');

        $movies = [];

        $movieIds = service('watch_history')->get();

        if(! empty($movieIds)){

            //clean array ids
            foreach ($movieIds as $Key => $id) {
                if(empty($id) || ! is_numeric($id) || $id <= 0){
                    unset( $movieIds[$Key] );
                }
            }

            if(! empty($movieIds)){

                $movieModel = new MovieModel();

                $isMovie ? $movieModel->movies() : $movieModel->episodes();
                $movies = $movieModel->whereIn('movies.id', $movieIds)
                                     ->forView()
                                     ->orderBy('FIELD(movies.id, '. implode(',', $movieIds) .')', '', false )
                                     ->allMovies()
                                     ->limit( get_config('watch_history_limit') )
                                     ->find();

            }

        }

        $formattedTitle = sprintf(lang('Page.watch_history_' . $fTitle), '<span class="text-primary">','</span>');
        $basePage = 'history';

        $data = compact('title', 'formattedTitle', 'movies', 'basePage', 'isMovie');
        return view(theme_path('list'), $data);
    }

    public function trending($type = 'movies')
    {

        if(! in_array($type, ['movies','shows']) || empty( get_config( 'items_per_trending_page' ) )){
            throw new PageNotFoundException();
        }

        $isMovie = $type == 'movies';
        $fTitle = ! $isMovie ? 'tv_shows' : 'movies';
        $title = sprintf(lang('Page.trending_in_' . $fTitle), '','');

        $movieModel = new MovieModel();



        if(! $isMovie){

            $movieModel->episodes()
                       ->select('seasons.series_id')
                       ->distinct()
                       ->allMovies(null, false, 'seasons.series_id')
                       ->asArray();

        }else{

            $movieModel->allMovies()
                       ->movies();

        }

        $movies =  $movieModel->trending( $type )
                              ->forView()
                              ->limit( get_config( 'items_per_trending_page' ) )
                              ->find();

        if(! $isMovie ){

            $seriesIds = extract_array_data($movies, 'series_id');

            $seriesModel = new SeriesModel();
            $movies = $seriesModel->whereIn('id', $seriesIds)
                                  ->orderBy('FIELD(id, '. implode(',', $seriesIds) .')', '', false )
                                  ->find();

        }

        $formattedTitle = sprintf(lang('Page.trending_in_' . $fTitle), '<span class="text-danger">','</span>');
        $basePage = 'trending';

        $data = compact('title', 'formattedTitle', 'movies', 'basePage', 'isMovie');
        return view(theme_path('list'), $data);

    }

    public function imdb_top($type = 'movies')
    {

        if(! in_array($type, ['movies','shows']) || empty( get_config( 'items_per_imdb_top_page' ) )){
            throw new PageNotFoundException();
        }

        $isMovie = $type == 'movies';
        $fTitle = ! $isMovie ? 'tv_shows' : 'movies';
        $title = sprintf(lang('Page.imdb_top_' . $fTitle), '','');

        if( $isMovie ){

            $movieModel = new MovieModel();
            $movies = $movieModel->orderBy("movies.imdb_rate", 'desc')
                                 ->forView()
                                 ->movies()
                                 ->allMovies(['minImdbRate', 1])
                                 ->limit(get_config( 'items_per_imdb_top_page' ))
                                 ->find();

        }else{

            $seriesModel = new SeriesModel();
            $movies = $seriesModel->orderBy('imdb_rate', 'desc')
                                  ->where('imdb_rate >', 1)
                                  ->limit(get_config( 'items_per_imdb_top_page' ))
                                  ->find();

        }

        $formattedTitle = sprintf(lang('Page.imdb_top_' . $fTitle), '<span class="text-secondary">','</span>');
        $basePage = 'imdb-top';


        $data = compact('title', 'formattedTitle', 'movies', 'basePage', 'isMovie');
        return view(theme_path('list'), $data);
    }


    protected function getCleanedGenres( ?array $genres )
    {
        if(! is_array($genres)) {
            return null;
        }
        $results = [];

        //filter empty values
        $genres = array_filter($genres);

        $genreModel = new GenreModel();

        foreach ($genres as $genreName) {

            $genre = $genreModel->select('id')
                                ->getGenreByName($genreName);

            if($genre !== null){
                $results[] = $genre->id;
            }

        }

        return $results;

    }

    protected function getCleanedQualities(?array $qualities)
    {
        if (!is_array($qualities)) {
            return null;
        }

        //filter empty values
        $qualities = array_filter($qualities);
        $allowedQualities = get_stream_quality_formats();

        return array_intersect($qualities, $allowedQualities);

    }
}