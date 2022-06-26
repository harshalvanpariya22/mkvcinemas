<?php

namespace App\Controllers\Admin\Settings;


class General extends BaseSettings
{

    public function index()
    {
        $title = 'General Settings';

        return view('admin/settings/general/index', compact('title'));
    }


    public function update()
    {
        if($this->request->getMethod() == 'post') {

            $validationRules = [
                'tmdb_api_key' => 'permit_empty|alpha_numeric',
                'omdb_api_key' => 'permit_empty|alpha_numeric',
                'dl_link_waiting_time' => 'required|integer|greater_than[2]'
            ];

            if($this->validate( $validationRules )){

                $data = $this->request->getPost();

                $data['download_quality_formats'] = separate_by_comma( $data['download_quality_formats'] );
                $data['download_resolution_formats'] = separate_by_comma( $data['download_resolution_formats'] );
                $data['stream_quality_formats'] = separate_by_comma( $data['stream_quality_formats'] );

                $data['is_stream_gcaptcha_enabled'] =  isset( $data['is_stream_gcaptcha_enabled'] );
                $data['is_count_down_timer'] =  isset( $data['is_count_down_timer'] );
                $data['is_download_link_captcha'] =  isset( $data['is_download_link_captcha'] );

                $data['is_links_report'] =  isset( $data['is_links_report'] );
                $data['download_system'] =  isset( $data['download_system'] );

                $data['request_system'] =  isset( $data['request_system'] );
                $data['req_email_subscription'] =  isset( $data['req_email_subscription'] );
                $data['is_request_captcha_enabled'] =  isset( $data['is_request_captcha_enabled'] );


                //save media files
                $this->saveDefaultMediaFiles();

                return $this->save( $data );

            }

            return redirect()->back()
                             ->with('errors', $this->validator->getErrors());

        }

        return redirect()->back();

    }

    protected function saveDefaultMediaFiles()
    {

        $posterImg = $this->request->getFile('default_poster_file');
        $bannerImg = $this->request->getFile('default_banner_file');

        //save poster image
        if($posterImg->isValid()){
            $validationRule = [
                'default_poster_file' => [
                    'label' => 'Default poster image',
                    'rules' => 'uploaded[default_poster_file]'
                        . '|is_image[default_poster_file]'
                        . '|mime_in[default_poster_file,image/jpg,image/jpeg,image/png,image/webp]'
                        . '|max_size[default_poster_file,2048]',
                ]
            ];

            if($this->validate( $validationRule )){


                $posterName = 'default-poster.' . $posterImg->getExtension();
                $dir = FCPATH . 'uploads/';
                $posterImg->move( $dir, $posterName, true);

                $this->save( [ 'default_poster' => $posterName ] );
            }
        }

        //save banner image
        if($bannerImg->isValid()){
            $validationRule = [
                'default_banner_file' => [
                    'label' => 'Default banner image',
                    'rules' => 'uploaded[default_banner_file]'
                        . '|is_image[default_banner_file]'
                        . '|mime_in[default_banner_file,image/jpg,image/jpeg,image/png,image/webp]'
                        . '|max_size[default_banner_file,2048]',
                ]
            ];

            if($this->validate( $validationRule )){

                $bannerName = 'default-banner.' . $bannerImg->getExtension();
                $dir = FCPATH . 'uploads/';
                $bannerImg->move( $dir, $bannerName, true);

                $this->save( [ 'default_banner' => $bannerName ] );

            }
        }

    }



}