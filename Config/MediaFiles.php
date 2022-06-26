<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class MediaFiles extends BaseConfig
{

    public $postersDir = 'posters';
    public $bannersDir = 'banners';
    public $posterMaxSize = 2 * 1024; // 2 MB
    public $bannerMaxSize = 5 * 1024; // 5 MB


}