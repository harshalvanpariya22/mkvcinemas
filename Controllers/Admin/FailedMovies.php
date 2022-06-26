<?php

namespace App\Controllers\Admin;


use App\Controllers\BaseController;

class FailedMovies extends BaseController
{

    /**
     * @var \App\Models\FailedMovies
     */
    protected $model;

    public function __construct()
    {
        $this->model = new \App\Models\FailedMovies();
    }

    public function index()
    {
        $title = 'Next For You';

        $movies = $this->model->orderBy('updated_at','desc')
                              ->orderBy('requests', 'desc')
                              ->findAll();

        $title .= ' - ( ' . count( $movies ) . ' )';

        return view('admin/failed_movies/list', compact('title','movies'));
    }

    public function delete()
    {
        $id = $this->request->getGet('id');

        if(! empty($id) && is_numeric($id)){

            $this->model->delete( $id );

        }

        return redirect()->back()
                         ->with('success', 'Record deleted successfully');

    }



}