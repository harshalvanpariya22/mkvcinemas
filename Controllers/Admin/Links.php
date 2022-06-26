<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LinkModel;
use App\Models\MovieModel;
use CodeIgniter\Exceptions\PageNotFoundException;


class Links extends BaseController
{

    protected $model;


    public function __construct()
    {
        $this->model = new LinkModel;
    }

    public function index()
    {
        $title = 'Links';

        $filter = $this->request->getGet('filter');
        $allowedFilters = ['stream', 'direct_download', 'torrent_download'];
        if(in_array($filter, $allowedFilters)){
            $this->model->where('type', $filter);

            switch ($filter) {
                case 'stream':
                    $title = 'Stream Links';
                    break;
                case 'direct_download':
                    $title = 'Direct Links';
                    break;
                case 'torrent_download':
                    $title = 'Torrent Links';
                    break;
            }
        }

        $links = $this->model->orderBy('id', 'DESC')
                             ->findAll();

        $title .= ' - ( ' . count( $links ) . ' )';

        return view('admin/links/list', compact('title', 'links', 'filter'));
    }

    public function reported()
    {
        $title = 'Reported Links';


        $links = $this->model->reported()
                           ->orderBy('reports_not_working', 'DESC')
                           ->orderBy('reports_wrong_link', 'DESC')
                           ->findAll();


        $data = compact('title', 'links');

        return view('admin/links/reported', $data);
    }

    public function edit( $id )
    {

        $title = 'Edit Link';

        $link = $this->getLink( $id );

        $movieModel = new MovieModel();
        $movie = $movieModel->select('title')
                            ->getMovie( $link->movie_id );

        return view('admin/links/edit', compact('title', 'link', 'movie'));
    }


    public function update( $id )
    {
        $link = $this->getLink( $id );

        $link->fill( $this->request->getPost() );

        if($link->hasChanged()){

            if($this->model->save( $link )){

                //if is it reported, remove it
                if($link->countReports() > 0){

                    if( $link->hasChanged('link') ){
                        $this->model->resetReports( $id );
                    }

                    return redirect()->to('/admin/links/reported')
                                     ->with('success', 'Link updated successfully');
                }


                return redirect()->to('/admin/links')
                                 ->with('success', 'Link updated successfully');

            }else{

                return redirect()->back()
                                 ->with('error', $this->model->errors())
                                 ->withInput();

            }

        }

        if($link->countReports() > 0){
            return redirect()->to('/admin/links/reported');
        }

        return redirect()->to('/admin/links');

    }

    public function clear( $id )
    {
        $link = $this->getLink( $id );

        if($link->countReports() > 0){

            if(! $this->model->resetReports( $id )){

                return redirect()->back()
                                 ->with('success', 'link removed from reported list');

            }

        }

        return redirect()->back();
    }

    public function delete( $id )
    {
        $link = $this->getLink( $id );

        if(! $this->model->delete( $link->id )){

            return redirect()->back()
                             ->with('error', $this->model->errors());

        }

        return redirect()->back()
                         ->with('success', 'link deleted successfully');

    }

    protected function getLink( $id )
    {
        $link = $this->model->where('id', $id)
                            ->first();

        if($link == null){

            throw new PageNotFoundException('link page not found');

        }

        return $link;

    }

}
