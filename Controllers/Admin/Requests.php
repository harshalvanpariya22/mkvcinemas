<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RequestsModel;
use App\Models\RequestSubscriptionModel;
use CodeIgniter\Exceptions\PageNotFoundException;


class Requests extends BaseController
{

    protected $model;

    public function __construct()
    {
        $this->model = new RequestsModel();
    }

    public function index()
    {
        $requests = $this->model->orderBy('FIELD(status, "pending")', 'desc', false)
                                ->orderBy('updated_at', 'desc')
                                ->orderBy('requests', 'desc')
                                ->select('requests.*, (SELECT COUNT(id) FROM requests_subscription WHERE request_id = requests.id) AS subscribes')
                                ->findAll();

        $title = 'User Requests - ( ' . count( $requests ) . ' )';

        $topBtnGroup = create_top_btn_group([
            'admin/requests/subs/all' => 'View All Subs',
            'admin/requests/delete/imported' => 'Delete Imported Req.'
        ]);

        return view('admin/requests/list', compact('title', 'requests', 'topBtnGroup'));
    }



    public function subs( $id )
    {
        $reqSubscription = new RequestSubscriptionModel();

        if($id !== 'all'){
            $request = $this->getRequest( $id );
            $subscribers = $reqSubscription->select('email')
                                           ->orderBy('email','asc')
                                           ->asArray()
                                           ->getAllSubscriptionByReqId( $request->id );
        }else{
            $subscribers = $reqSubscription->orderBy('email','asc')
                                           ->asArray()
                                           ->findAll();
        }

        if(! empty( $request )){
            echo '<h2>' .  esc( $request->title ) . ' -- Subscribers - ( ' . count( $subscribers ) . ' ) </h2>';
        }

        if(! empty($subscribers)){
            $subscribers = extract_array_data($subscribers, 'email');
            echo implode('<br>', $subscribers);
        }else{
            echo 'subscribers not found';
        }

    }

    public function delete( $id )
    {

        $success = false;
        if($id !== 'imported'){

            $request = $this->getRequest( $id );
            if($this->model->delete( $request->id )){
                $success = true;
            }

        }else{

            if($this->model->where('status', 'imported')->delete()){
                $success = true;
            }

        }

        if($success){
            return redirect()->back()->with('success', 'Request/s deleted successfully');
        }

        return redirect()->back()->with('errors', 'something went wrong');
    }



    protected function getRequest( $id )
    {
        $request = $this->model->where('id', $id)
                               ->first();

        if($request === null){
            throw new PageNotFoundException('Request not found');
        }

        return $request;
    }

}
