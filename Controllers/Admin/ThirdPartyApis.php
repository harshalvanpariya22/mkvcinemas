<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\LinkModel;
use App\Models\MovieModel;
use App\Models\ThirdPartyApi;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Model;


class ThirdPartyApis extends BaseController
{

    protected $model;

    public function __construct()
    {
        $this->model = new ThirdPartyApi();
    }

    public function index()
    {
        $title = 'Third Party APIs <small>( For Streaming )</small>';

        $apis = $this->model->findAll();

        $topBtnGroup = create_top_btn_group([
            'admin/third-party-apis/new' => 'New API'
        ]);

        return view('admin/third_party_apis/list', compact('title', 'apis', 'topBtnGroup'));
    }



    public function new()
    {

        $title = 'New Third Party API';
        $tpAPI = new \App\Entities\ThirdPartyApi();

        $topBtnGroup = create_top_btn_group([
            'admin/third-party-apis' => 'Back to APIs'
        ]);

        return view('admin/third_party_apis/new', compact('title', 'tpAPI', 'topBtnGroup'));

    }

    public function edit()
    {
        $title = 'Edit Third Party API';
        $tpAPI = $this->getApi( $this->request->getGet('id') );
        $topBtnGroup = create_top_btn_group([
            'admin/third-party-apis' => 'Back to APIs'
        ]);
        return view('admin/third_party_apis/edit', compact('title', 'tpAPI', 'topBtnGroup'));

    }

    public function create(): \CodeIgniter\HTTP\RedirectResponse
    {
        $tpAPI = new \App\Entities\ThirdPartyApi( $this->request->getPost() );

        if($this->model->insert( $tpAPI )){

            return redirect()->to(admin_url( '/third-party-apis' ))
                            ->with('success', 'New third party API added successfully');

        }

        return redirect()->back()
                         ->with('errors', $this->model->errors())
                         ->withInput();
    }

    public function update()
    {
        $tpAPI = $this->getApi( $this->request->getGet('id') );
        $tpAPI->fill( $this->request->getPost() );

        if($tpAPI->hasChanged()){
            if($this->model->save( $tpAPI )){

                //update links
                $linksModel = new LinkModel();
                $movieModel = new MovieModel();

                $isNeedUpdate = false;
                $type = '';

                if($tpAPI->hasChanged('movie_api')){
                    $type = 'movie';
                    $isNeedUpdate = true;
                }
                if($tpAPI->hasChanged('series_api')){
                    $type = 'episode';
                    $isNeedUpdate = true;
                }

                if($isNeedUpdate){
                    //update links
                    $type == 'movie' ? $linksModel->movies() : $linksModel->episodes();
                    $movies_links = $linksModel->where('links.api_id', $tpAPI->id)
                                               ->select('links.*')
                                               ->findAll();

                    if(! empty($movies_links)){
                        foreach ($movies_links as $link){
                            $type == 'movie' ? $movieModel->movies() : $movieModel->episodes();
                            $movie = $movieModel->getMovie( $link->movie_id );
                            $updatedUrl = ThirdPartyApi::inject($movie, $tpAPI);

                            $link->link = $updatedUrl;
                            if($link->hasChanged()){
                                $linksModel->save( $link );
                            }
                        }
                    }
                }


                //update links status
                if($tpAPI->hasChanged('status')){

                    $isBroken = (int) ($tpAPI->status == 'paused');

                    $linksModel->db->table('links')
                                   ->where('api_id', $tpAPI->id)
                                   ->set('is_broken', $isBroken)
                                   ->update();

                }

                return redirect()->to(admin_url( '/third-party-apis' ))
                                  ->with('success', $tpAPI->name . ' API updated successfully');
            }else{
                return redirect()->back()
                                 ->with('errors', $this->model->errors())
                                 ->withInput();
            }
        }

        return redirect()->to(admin_url( '/third-party-apis' ));

    }

    public function delete()
    {
        $tpAPI = $this->getApi( $this->request->getGet('id') );

        if($this->model->delete( $tpAPI->id )){
            return redirect()->back()
                             ->with('success', $tpAPI->name . ' deleted successfully');
        }

        return redirect()->back()
                         ->with('errors', $this->model->errors())
                         ->withInput();
    }


    protected function getApi($id)
    {
        $api = $this->model->where('id', $id)->first();

        if($api === null){
            throw new PageNotFoundException('Third party API not found');
        }

        return $api;
    }

}
