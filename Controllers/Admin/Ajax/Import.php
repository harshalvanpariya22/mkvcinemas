<?php

namespace App\Controllers\Admin\Ajax;

use App\Controllers\BaseAjax;
use App\Entities\Movie;
use App\Entities\Series;
use App\Libraries\DataAPI\API;
use App\Models\GenreModel;
use App\Models\MovieModel;
use App\Models\SeriesModel;


/**
 * Class Import
 * @package App\Controllers\Admin\Ajax
 * @author John Antonio
 */
class Import extends BaseAjax
{
    /**
     * Import status
     * @var string success, failed
     */
    protected $status = 'failed';

    /**
     * Import data type
     * @var string movie, episode, series
     */
    protected $type = 'movie';

    /**
     * Current Imdb Id for import
     * @var string
     */
    protected $imdbId  = '';

    /**
     * Data API (tmdb and omdb handler)
     */
    protected $bulkImport = null;

    /**
     * Import constructor.
     */
    public function __construct()
    {
        $this->helpers[] = 'bulk_import';
        $this->bulkImport = service('bulk_import');
    }


    public function index()
    {

        session_write_close();
        set_time_limit(1800);

        $uniqIds = $this->request->getGet('uniq_ids');
        $type = $this->request->getGet('type');

        if(! empty($uniqIds)){
            $uniqIds = str_replace(' ', '', $uniqIds);
            $uniqIds = explode(',', $uniqIds);
        }

        if(! empty( $uniqIds )){

            $type == 'movies' ? $this->bulkImport->movies() : $this->bulkImport->series();
            $this->bulkImport->set( $uniqIds )->run();

            if($results = $this->bulkImport->getResults()){

                $this->addData( $results );
            }

        }


        return $this->jsonResponse();
    }



}