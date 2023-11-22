<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;

class ApiController extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('api_status_codes_helper');
		$this->load->helper('url');
		$this->load->helper('file');
		$this->error	= 0;
		$this->errMsg	= array();
	}

	public function get_product_reviews_post()
	{
		$response = array('status' => 0);

		$error	= 0;
		$errMsg	= array();

		if($this->post('web_sku') == null || $this->post('web_sku') == '')
		{
			$error++;
			$errMsg[] = "Web SKU is missing";
		}

		if($error == 0)
		{
			$this->load->model("api/ApiModel");

			$filterData = array(
				'web_sku' => $this->post('web_sku'),
			);

			$productReviews = $this->ApiModel->getAllProductReviewsForSku($filterData);

			$response['status']				= 1;
			$response['product_reviews']	= $productReviews;
		}
		else
		{
			$response['msg'] = implode(', ', $errMsg);
		}

		$this->logRequest($response);
		$this->set_response($response, REST_Controller::HTTP_CREATED);
	}

	private function logRequest($response)
	{
		$this->commonfunctions->mkdir('API_REQ_RESP_LOG_SAVE_PATH');
		$path = $this->commonfunctions->getDirFullPath('API_REQ_RESP_LOG_SAVE_PATH');

		$request = debug_backtrace();

		$fileLocation = $path . date("Y-m-d").".txt";

		$content  = date('Y-m-d H:i:s')."\n";
		$content .= 'URL: '.$request[0]['object']->uri->uri_string."\n";
		$content .= 'Request: '.json_encode($this->post())."\n";
		$content .= 'Response: '.json_encode($response)."\n";
		$content .= "------------------------------------------------\n";

		$file = fopen($fileLocation, "a");
		fwrite($file, $content);
		fclose($file);

		return TRUE;
	}

	public function create_appointment_lead_post()
	{
		$response['status'] = 'error';

		$this->mandatoryFields[] = "name";
		$this->mandatoryFields[] = "mobile_no";
		/*$this->mandatoryFields[] = "LastName";*/
		$this->mandatoryFields[] = "appointment_date";
		$this->mandatoryFields[] = "appointment_time";
		$this->mandatoryFields[] = "store_code";
		$this->mandatoryFields[] = "meeting_on";

		$this->validatePostFields();

		if(!(filter_var($this->post('email'), FILTER_VALIDATE_EMAIL)))
		{
			$this->error++;
			$this->setErrorMessage('INVALID_EMAIL', 'EmailId');
		}
	
		if(!(date("Y-m-d", strtotime($this->post('appointment_date'))) == date($this->post('appointment_date')))) {
			$this->error++;
			$this->setErrorMessage('INVALID_APPOINTMENT_DATE', 'appointment_date');
		}

		$sourcedetails = $this->CommonModel->select(DbTable::SOURCE_MASTER, 'id', array('source'=>$this->post('source'),'is_del'=>'N'));

		if(count($sourcedetails)==0)
		{
			$this->error++;
			$this->setErrorMessage('INVALID_SOURCE', 'source');
		}

		if($this->error == 0)
		{			
			$leadStatusdetails = $this->CommonModel->select(DbTable::LEAD_STATUS_MASTER, 'id', array('lead_status'=>'NEW','status'=>'Y'));
			
			$storedetails = $this->CommonModel->select(DbTable::LOCATION_MASTER, 'id', array('location'=>$this->post('store_code'),'is_del'=>'N'));

			$saveData = array(
				'name'=> $this->post('name'),
				'email'=> $this->post('email'),
				'mobile_no'=> $this->post('mobile_no'),
				'source'=> $sourcedetails[0]['id'],
				'status'=>	count($leadStatusdetails) > 0 ? $leadStatusdetails[0]['id'] : 0,
				'created_on'=> date('Y-m-d H:i:s'),
				'city'=> $this->post('city'),
				'pincode'=> $this->post('pincode'),
				'gender'=> $this->post('gender'),
				'meeting_on'=> $this->post('meeting_on'),
				'updated_on'=> '',
				'created_by'=> 0,            
				'store_code' => count($storedetails) > 0 ? $storedetails[0]['id'] : 0,
				'location' => $this->post('store_code'),
				'message' => $this->post('message'),
				'appointment_datetime' => $this->post('appointment_date').' '.$this->post('appointment_time'),
			);

			$appointmentID = $this->CommonModel->create(DbTable::APPOINTMENT, $saveData);

			$response['status']		= 'Success';
			/*$response['id']	= $enquiryID;*/
			$response['appointment_id']	= $appointmentID;
			$response['message'] =  'Appointment Lead has been created';
		}

		if(count($this->errMsg) > 0)
		{
			$response['errors']	= $this->errMsg;
		}

		$this->logRequest($response);
		$this->set_response($response, REST_Controller::HTTP_CREATED);
	}
}