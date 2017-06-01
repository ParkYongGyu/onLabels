<?php
class Order extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url','html'));
		$this->load->library('session');
		$this->load->database();
		$this->load->model('user_model');
		$this->load->model('order_model', 'orders');
	}
	
	function index()
	{
		if ($this->session->userdata('uid') !== null)
		{
			$details = $this->user_model->get_user_by_id($this->session->userdata('uid'));
			$data['uname'] = $details[0]->username;
			$data['uemail'] = $details[0]->email;
			$this->load->view('new_order_view', $data);	
		}
		else redirect(base_url());
	}

	public function ajax_list()
    {
        $list = $this->orders->get_orders();       
        $data = array();
        $no = $_POST['start'];
        foreach ($list as $orders) {
       // print_r($data);die;
        $no++;
        $row = array();
        $row[] = '<input type="checkbox" value="'.$orders->sc_ordered_id.'" class="form-group tick">';
        $row[] = substr($orders->sc_ordered_id, -7);
        $row[] = date("Y.m.d h:i A",strtotime($orders->ordered_date));
        
        $row[] = $orders->sc_market;
        
        $row[] = $orders->order_title."<br>수량 : ".$orders->cnt."개 <br>가격 : ".$orders->amount.
        '<br><br><a class="collapse_tbl" role="button" data-toggle="collapse" href="#no'.$no.'" aria-expanded="false" aria-controls="#'.$no.'">
													<span class="fa fa-caret-right"> </span> 배송정보
												</a>
												<div class="collapse out" id="no'.$no.'">
													주문자	:  '.$orders->seller_first_name.' '.$orders->seller_last_name.' <br/>
													연략처   :  - <br/>
													Email  : - <br/>
													주소<br/>
														 '.$orders->seller_street1.' <br/>
														 '.$orders->seller_street2.'<br/>
														 '.$orders->seller_country.'<br/>
													</div>';
        
        $row[] = $orders->order_user_name."<br>"."피드백 : ".$orders->feedback_score."점 주문수 : ".$orders->cnt."회";
        $row[] = '<span><a href=""><img src="'.base_url("assets2/img/icon-1.png").'"></a></span>
				  <span><a href=""><img src="'.base_url("assets2/img/icon-2.png").'"></a></span>
				  <span><a href=""><img src="'.base_url("assets2/img/icon-3.png").'"></a></span>';

        $data[] = $row;

        //$_POST['draw']='';
        }

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->orders->count_all(),
            "recordsFiltered" => $this->orders->count_filtered(),
            "data" => $data,
        );
        //output to json format
       echo json_encode($output);
    }


}