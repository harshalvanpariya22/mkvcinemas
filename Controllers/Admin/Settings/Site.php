<?php

namespace App\Controllers\Admin\Settings;


class Site extends BaseSettings
{

    public function index()
    {
        $title = 'Site Settings';

        return view('admin/settings/site/index', compact('title'));
    }


    public function update()
    {
        if($this->request->getMethod() == 'post'){

            $validationRules = [
                'site_title' => 'permit_empty|alpha_numeric_punct',
                'site_name' => 'permit_empty|alpha_numeric_space',
                'footer_content' => 'permit_empty',
                'library_items_per_page' => 'required|integer|greater_than[0]|less_than[61]',
                'home_items_per_page' => 'permit_empty|integer|greater_than[-1]|less_than[31]',
                'items_per_trending_page' => 'permit_empty|integer|greater_than[-1]|less_than[101]',
                'items_per_recommend_page' => 'permit_empty|integer|greater_than[-1]|less_than[101]',
                'items_per_new_release_page' => 'permit_empty|integer|greater_than[-1]|less_than[101]',
                'items_per_imdb_top_page' => 'permit_empty|integer|greater_than[-1]|less_than[101]',
                'watch_history_limit' => 'permit_empty|integer|greater_than[-1]|less_than[51]',
                'view_slug' => 'permit_empty|alpha_dash|min_length[1]',
                'embed_slug' => 'permit_empty|alpha_dash|min_length[1]',
                'download_slug' => 'permit_empty|alpha_dash|min_length[1]',
                'link_slug' => 'permit_empty|alpha_dash|min_length[1]',
                'library_slug' => 'permit_empty|alpha_dash|min_length[1]',
            ];

            if($this->validate( $validationRules )){

                $generalData = $this->request->getPost([
                    'site_title',
                    'site_name',
                    'site_description',
                    'site_keywords',
                    'site_copyright',
                    'footer_content',
                    'library_items_per_page',
                    'home_items_per_page',
                    'items_per_trending_page',
                    'items_per_recommend_page',
                    'items_per_new_release_page',
                    'items_per_imdb_top_page',
                    'watch_history_limit',
                    'is_sidebar_disabled',
                    'custom_header_codes',
                    'custom_footer_codes',
                    'ad_block_detector'
                ]);

                $generalData['is_sidebar_disabled'] =  $generalData['is_sidebar_disabled'] == 1;
                $generalData['ad_block_detector'] =  $generalData['ad_block_detector'] == 1;


                if(! empty( $generalData['custom_header_codes'] )){
                    $generalData['custom_header_codes'] = base64_encode( $generalData['custom_header_codes'] );
                }

                if(! empty( $generalData['custom_footer_codes'] )){
                    $generalData['custom_footer_codes'] = base64_encode( $generalData['custom_header_codes'] );
                }

                $customSlugsData = $this->request->getPost([
                    'view_slug',
                    'embed_slug',
                    'download_slug',
                    'link_slug',
                    'library_slug'
                ]);

                $this->updateCustomSlugs( $customSlugsData );

                //save logo & favicon
                $this->saveLogo();
                $this->saveFavicon();

                return $this->save( $generalData );

            }

            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();


        }

        return redirect()->back();

    }

    protected function updateCustomSlugs(array $slugs )
    {

        $existSlugs = [
            'embed_slug' => embed_slug(),
            'download_slug' => download_slug(),
            'view_slug' => view_slug(),
            'link_slug' => link_slug(),
            'library_slug' => library_slug()
        ];

        foreach ($slugs as $key =>  $val) {

            if(! array_key_exists($key, $existSlugs)){
                continue;
            }

            $slugsForCheck = array_diff_key($existSlugs, array_flip([$key]));

            if(! in_array($val, $slugsForCheck)){

                $this->save( [$key => $val] );

            }else{

                $this->validator->setError($key, "{$key} : your selected slug value is blacklisted. choose another one");

            }

        }


    }





    protected function saveLogo()
    {

        $logoImg = $this->request->getFile('logo_file');

        if($logoImg->isValid()){
            $validationRule = [
                'logo_file' => [
                    'label' => 'Logo file',
                    'rules' => 'uploaded[logo_file]'
                        . '|is_image[logo_file]'
                        . '|mime_in[logo_file,image/jpg,image/jpeg,image/png,image/webp]'
                        . '|max_size[logo_file,2048]',
                ]
            ];

            if($this->validate( $validationRule )){


                $logoName = 'logo.' . $logoImg->getExtension();
                $dir = FCPATH . 'uploads/';
                $logoImg->move( $dir, $logoName, true);

                $this->save( [ 'site_logo' => $logoName ] );
            }
        }

    }

    protected function saveFavicon()
    {

        $faviconImg = $this->request->getFile('favicon_file');

        if( $faviconImg->isValid() ){
            $validationRule = [
                'favicon_file' => [
                    'label' => 'Favicon file',
                    'rules' => 'uploaded[favicon_file]'
                        . '|max_size[favicon_file,200]',
                ]
            ];
            if(in_array( $faviconImg->getMimeType(), ['image/x-icon','image/vnd.microsoft.icon'] )){

                if($this->validate( $validationRule )){

                    $favName = 'favicon.' . $faviconImg->getExtension();
                    $dir = FCPATH . 'uploads/';
                    $faviconImg->move( $dir, $favName, true);

                    $this->save( [ 'site_favicon' => $favName ] );
                }

            }

        }

    }


}