<?php

namespace Config;

use App\Filters\AjaxFilter;
use App\Filters\Auth;
use App\Filters\DownloadLinksThrottle;
use App\Filters\EmbedFirewall;
use App\Filters\LinksReportThrottle;
use App\Filters\MoviesRequestThrottle;
use App\Filters\StreamLinksThrottle;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array
     */
    public $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'ajax' => AjaxFilter::class,
        'embedfirewall' => EmbedFirewall::class,
        'links_reports_throttle' => LinksReportThrottle::class,
        'stream_links_throttle' => StreamLinksThrottle::class,
        'download_links_throttle' => DownloadLinksThrottle::class,
        'movies_request_throttle' => MoviesRequestThrottle::class,
        'auth' => Auth::class
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array
     */
    public $globals = [
        'before' => [
            // 'honeypot',
            // 'csrf',
            // 'invalidchars',
        ],
        'after' => [
            'toolbar',
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['csrf', 'throttle']
     *
     * @var array
     */
    public $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array
     */
    public $filters = [
        'ajax' => [
           'before' => ['admin/ajax/*', 'ajax/*']
        ],
        'csrf' => [
            'before' => 'request/create'
        ],
        'embedfirewall' => [
            'before' => ['embed/*']
        ],
        'movies_request_throttle' => [
            'before' => ['request/create']
        ],
        'links_reports_throttle' => [
            'before' => ['ajax/report_download_links', 'ajax/report_stream_link']
        ],
        'download_links_throttle' => [
            'before' => ['link/*']
        ],
        'stream_links_throttle' => [
            'before' => ['ajax/get_stream_link']
        ],
        'auth' => [
            'before' => ['admin/*']
        ]
    ];
}
