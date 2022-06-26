<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Genre;
use App\Models\GenreModel;
use App\Models\Translations\GenreTranslationsModel;
use CodeIgniter\Entity\Entity;
use CodeIgniter\Model;


class Genres extends BaseController
{

    protected $model;
    protected $translation;

    public function __construct()
    {
        $this->model = new GenreModel();

        if(get_config('is_multi_lang')){
            $this->translation = new GenreTranslationsModel();
        }
    }

    public function index()
    {
        $title = 'Genres';
        $translations = null;

        $genreId = session('genre_id');
        $genre = ! empty($genreId) ? $this->getGenre($genreId) : new Genre();

        $genres = $this->model->getAllGenresWithCounts();

        $title .= ' - ( ' . count( $genres ) . ' )';

        if( is_multi_languages_enabled() ){
            $translations = ! empty( $genreId ) ? $this->translation->findByGenreId( $genreId ) : $this->translation->getDummyList();
        }


        $data = compact('title', 'genre', 'genres', 'translations');
        return view('admin/genres/list', $data);
    }

    public function edit($id)
    {


        return redirect()->to('/admin/genres')
                         ->with('genre_id', $id);
    }

    public function delete($id)
    {
        $genre = $this->getGenre( $id );
        if($this->model->delete( $genre->id )){

            return redirect()->back()
                             ->with('success', 'Genre deleted successfully');

        }else{

            return redirect()->back()
                             ->with('errors', $this->model->errors());

        }
    }

    public function save($id = null)
    {
        if($this->request->getMethod() == 'post') {

            $genreName =  strtolower($this->request->getPost( 'name' ));

            $genre = ! empty($id) ? $this->getGenre($id) : new Genre();
            $genre->fill(['name' => $genreName]);

            //check genre is already deleted
            $deletedGenre = $this->model->onlyDeleted()->getGenreByName($genreName);

            if(! empty($deletedGenre)) {
                //restore record
                $this->model->restore( $deletedGenre->id );
            }else{
                if($genre->hasChanged()) {
                    if(! $this->model->save( $genre )){

                        return redirect()->back()
                                         ->with('genre_id', $genre->id)
                                         ->with('errors', $this->model->errors())
                                         ->withInput();

                    }
                }
            }

            //save translations
            if( is_multi_languages_enabled() ){

                $this->model->addTranslations( $genre->id, $this->request->getPost('translations') );

            }


            return redirect()->back()
                             ->with('success', 'Genre saved successfully');

        }
    }


    public function getGenre($id) : Genre
    {
        $genreModel = new GenreModel();
        $genre = $genreModel->where('id', $id)->first();
        if($genre == null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Invalid genre Id ' . $id);
        }
        return $genre;
    }



}
