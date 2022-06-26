<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RequestsModel;
use App\Models\RequestSubscriptionModel;
use CodeIgniter\Exceptions\DebugTraceableTrait;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Services;

class Request extends BaseController
{

    protected $model;
    protected $subscription;

    public function __construct()
    {
        $this->model = new RequestsModel();
        $this->subscription = new RequestSubscriptionModel();
    }

    public function index()
    {

        if(! get_config( 'request_system' )){
            throw new PageNotFoundException();
        }

        $title = lang( 'Request.page_title' );

        return view(theme_path('request'), compact('title'));
    }


    public function create()
    {
        if($this->request->getMethod() !== 'post' || ! get_config( 'request_system' )){

            throw new PageNotFoundException();

        }

        if(is_request_captcha_enabled() && ! $this->validate([
                'captcha' => 'required|valid_math_captcha'
            ])){

            return redirect()->back()
                             ->with('error', $this->validator->getError());
        }

        $items = $this->request->getPost('items');
        $email = $this->request->getPost('email');
        $error = null;

        if(! empty( $items )){

            foreach ($items as $key => $item) {

                $request = $this->model->getRequestByTmdbId( $key );
                $reqId = null;

                if($request === null){
                    $item['tmdb_id'] = $key;
                    if($this->model->insert( $item )){
                        $reqId = $this->model->getInsertID();
                    }
                }else{
                    //update requests
                    $this->model->requested( $request->id );
                    $reqId = $request->id;
                }

                if(get_config('req_email_subscription')){

                    //update subscription
                    if(! empty( $reqId ) && ! empty( $email )){
                        $subscription = $this->subscription->getSubscription( $reqId, $email );
                        if($subscription === null){
                            //add subscription
                            $this->subscription->subscribe($reqId, $email);
                        }
                    }

                }

            }

            return redirect()->back()
                             ->with('success', lang('Request.successfully_requested'));

        }else{

            $error = 'Empty selection';

        }


        return redirect()->back()
                         ->with('error', $error);
    }


}
