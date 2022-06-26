<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Page;
use App\Models\PagesModel;
use App\Models\Translations\PageTranslationsModel;
use CodeIgniter\Exceptions\PageNotFoundException;


class Pages extends BaseController
{

    protected $model;
    protected $translationModel;

    public function __construct()
    {
        $this->model = new PagesModel();
        $this->translationModel = new PageTranslationsModel();
    }

    public function index()
    {
        $title = 'Pages';

        $pages = $this->model->orderBy('created_at', 'desc')
                             ->findAll();

        $topBtnGroup = create_top_btn_group([
            'admin/pages/new' => 'New Page'
        ]);

        $data = compact('title', 'pages', 'topBtnGroup');
        return view( 'admin/pages/list', $data);
    }


    public function new()
    {
        $title = 'New Page';


        $page = new Page();

        $translations = null;
        if( is_multi_languages_enabled() ){
            //Translations
            $translations = $this->translationModel->getDummyList();
        }

        $topBtnGroup = create_top_btn_group([
            'admin/pages' => 'Back to Pages'
        ]);

        $data = compact('title', 'page', 'translations', 'topBtnGroup');
        return view( 'admin/pages/new', $data);
    }




    public function edit( $id )
    {
        $title = 'Edit Page';

        $page = $this->getPage( $id );

        $topBtnGroup = create_top_btn_group([
            'admin/pages/new' => 'Back to Pages'
        ]);

        $translations = null;
        if( is_multi_languages_enabled() ){
            //Translations
            $translations = $this->translationModel->findByPageId( $page->id );
        }


        $data = compact('title', 'page', 'translations',  'topBtnGroup');
        return view( 'admin/pages/edit', $data);
    }

    public function create()
    {
         if( $this->request->getMethod() == 'post' ){

             $page = new Page( $this->request->getPost() );

             if(empty( $page->slug )){
                 $page->slug = url_title( $page->title, '-', true );
             }

             if( $this->model->insert( $page ) ){

                 // save translations
                 if( is_multi_languages_enabled() ){

                     $this->model->addTranslations(
                         $this->model->getInsertID(),
                         $this->request->getPost( 'translations' )
                     );

                 }

                return redirect()->to('/admin/pages')
                                 ->with('success', 'New page created successfully');

             }

             return redirect()->back()
                              ->with('errors', $this->model->errors())
                              ->withInput();
         }


         throw new PageNotFoundException();
    }

    public function update($id)
    {
        if( $this->request->getMethod() == 'post' ){

            $page = $this->getPage( $id );
            $page->fill( $this->request->getPost() );

            if(isset( $page->translations )){
                unset( $page->translations );
            }


            if(isset( $page->files )){
                unset($page->files);
            }

            if( $page->hasChanged() ) {

                if( $this->model->save( $page ) ){

                    return redirect()->to('/admin/pages')
                                     ->with('success', 'page updated successfully');

                }

                return redirect()->back()
                                ->with('errors', $this->model->errors())
                                ->withInput();
            }

            if( is_multi_languages_enabled() ){

                $this->model->addTranslations(
                    $page->id,
                    $this->request->getPost( 'translations' )
                );

            }

            return redirect()->to('/admin/pages');

        }

        throw new PageNotFoundException();
    }

    public function delete( $id )
    {
        $page = $this->getPage( $id );

        if( $this->model->delete( $page->id ) ){

            return redirect()->back()
                             ->with('success','Page deleted successfully');

        }

        return redirect()->back()
                        ->with('errors','Unable to delete');
    }

    protected function getPage( $id )
    {

        $page = $this->model->where('id', $id)
                            ->first();

        if($page === null){
            throw new PageNotFoundException('Invalid page ID');
        }


        return $page;
    }



}
