<?php

namespace App\Controllers\Admin\Settings;


use App\Models\Translations\GenreTranslationsModel;
use App\Models\Translations\MovieTranslationsModel;
use App\Models\Translations\PageTranslationsModel;
use CodeIgniter\Exceptions\PageNotFoundException;


class Translations extends BaseSettings
{

    public function index()
    {
        $title = 'Translations Settings';

        $translationModel = new MovieTranslationsModel();
        $crashedLanguages = $translationModel->getCrashedLangCodes();

        return view('admin/settings/translations', compact('title', 'crashedLanguages'));
    }


    public function update()
    {

        if($this->request->getMethod() == 'post') {

            if($this->validate([
                'lang.*' => 'permit_empty|valid_lang_code',
                'main_language' => 'valid_lang_code',
            ])){

                $languages = $this->request->getPost('lang');
                $languages = ! empty( $languages ) ? array_values( $languages ) : [];

                $data = [
                    'main_language' => $this->request->getPost('main_language'),
                    'selected_languages' => json_encode( $languages ),
                    'is_multi_lang' => $this->request->getPost('is_multi_lang') == 1
                ];

                return $this->save( $data );
            }

            return redirect()->back()
                             ->with('errors', $this->validator->getErrors());

        }

        return redirect()->back();

    }


    public function remove_lang()
    {
        if( $this->request->getMethod() == 'post' ){

            if($this->validate([
                'language' => 'valid_lang_code',
            ])){

                $lang = $this->request->getPost('language');

                $translationModel = new MovieTranslationsModel();
                $genreTranslationModel = new GenreTranslationsModel();
                $pageTranslationModel = new PageTranslationsModel();

                //remove movies translations
                $translationModel->where('lang', $lang)
                                 ->delete();

                //remove genre translations
                $genreTranslationModel->where('lang', $lang)
                                      ->delete();

                //remove page translations
                $pageTranslationModel->where('lang', $lang)
                                     ->delete();

                return redirect()->back()
                                ->with('success',  lang_name( $lang ) . ' translation removed successfully');
            }

            return redirect()->back()
                             ->with('errors', $this->validator->getErrors());

        }

        throw new PageNotFoundException();
    }



}