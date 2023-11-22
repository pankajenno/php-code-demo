<?php
defined('BASEPATH') or exit('No direct script access allowed');

class EnquiryList extends Admin_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->permission->checkAdminPermission();

		$this->load->model('admin/enquiry/EnquiryListModel');
	}

	public function index()
	{
		$heading = 'Enquiry List';
		$this->page_title->push($heading);
		$this->breadcrumbs->unshift(1, $heading, 'view-leads');

		$this->data['pageHeader']	= $heading;
		$this->data['pagetitle']	= $this->page_title->show();
		$this->data['breadcrumb']	= $this->breadcrumbs->show();

		$this->data['leadStatusList'] = $this->CommonModel->select(DbTable::LEAD_STATUS_MASTER, 'id, lead_status', array(), 'lead_status');
		$this->data['sourceList'] = $this->CommonModel->select(DbTable::SOURCE_MASTER, 'id, source', array('is_del' => 'N'));

		$this->data['leadTypeList'] = array(
			"booking"	=> 'Online',
			"pickup"	=> 'Store Pickup',
			"swiss"		=> 'Swiss',
			"chat-call"	=> 'Chat/Call'
		);

		if (!empty($_POST) && $this->input->post('download_enquiries') != null) {
			$this->form_validation->set_rules('select_type', 'type', 'trim|required');
			$this->form_validation->set_rules('start_date', 'start date', 'trim|required');
			$this->form_validation->set_rules('end_date', 'end date', 'trim|required');

			$this->form_validation->set_message('required', 'Please enter %s');

			if ($this->form_validation->run()) {
				$diff = $this->commonfunctions->dateDifference($this->input->post('start_date'));

				if ($diff > 90 && false)		// 3 months
				{
					$endDate = date('Y-m-d', strtotime('+3 months', strtotime($this->input->post('start_date'))));
				} else {
					$endDate = $this->input->post('end_date');
				}

				$filterData = array(
					'select_type'	=> $this->input->post('select_type'),
					'start_date'	=> $this->input->post('start_date'),
					'end_date'		=> $endDate,
				);
				$reportData = $this->EnquiryListModel->getEnquiryDetailsForDownload($filterData);
				//echo "<pre>"; print_r($reportData); exit;

				foreach ($reportData as $key => $value) {
					$reportData[$key]['purchase_date']			= date(DATE_TIME_FORMAT, strtotime($value['purchase_date']));
					$reportData[$key]['enquiry_date']			= date(DATE_TIME_FORMAT, strtotime($value['enquiry_date']));
					$reportData[$key]['updated_on']				= date(DATE_TIME_FORMAT, strtotime($value['updated_on']));
					$reportData[$key]['shipment_updated_date']	= date(DATE_TIME_FORMAT, strtotime($value['shipment_updated_date']));
					$reportData[$key]['invoice_date']			= date(DATE_TIME_FORMAT, strtotime($value['invoice_date']));
				}
				$fieldsList = array(
					'enquiry_id'				=> 'Enquiry Id',
					'location_code'				=> 'Store',
					'enquiry_billing_name'		=> 'Billing Name',
					'enquiry_billing_contact_number'		=> 'Billing Mobile No',
					'enquiry_billing_email'		=> 'Billing Email Id',
					'enquiry_date'				=> 'Enquiry Date',
					'lead_status'				=> 'Lead Status',
					'source'					=> 'Source',
					'lead_type'					=> 'Lead Type',
					'full_name'					=> 'Shipping Name',
					'emailid'					=> 'Shipping Email',
					'mobile'					=> 'Shipping Mobile',
					'product_id'				=> 'Product SKU',
					'brand'						=> 'Brand',
					'product_price'				=> 'Product Price',
					'billing_price'				=> 'Billing Price',
					'awb_number'				=> 'AWB Number',
					'shipment_latest_status'	=> 'Shipment Latest Status',
					'shipment_updated_date'		=> 'Shipment Updated Date',
					'payu_id'					=> 'PayU Id',
					'transaction_id'			=>	'Transaction Id',
					'purchase_date'				=> 'Purchase Date',
					'lead_source'				=> 'Lead Source',
					//'comment_text'				=> 'Short Comment',
					'delivery_city'				=>	'Pincode',
					'address'					=>	'Shipping Address',
					'enquiry_billing_address'	=>	'Billing Address',
					'invoice_number'			=>	'Invoice Number',
					'invoice_date'				=>	'Invoice Date',
					'declared_value'			=>	'Invoice Amount',
				);

				$this->load->library('common/CustomExcel');

				$fileName = 'enquiry-list-' . date("Y-m-d-H-i");
				$this->customexcel->generateExcel($fieldsList, $fileName, $reportData);
			}
		}

		$this->template->admin_render('admin/enquiry/enquiry_list/index', $this->data);
	}

	public function createLead()
	{

		$heading = 'New Enquiry';
		$this->page_title->push($heading);
		$this->breadcrumbs->unshift(1, $heading, 'admin/create-lead');

		$this->data['pageHeader']	= $heading;
		$this->data['pagetitle']	= $this->page_title->show();
		$this->data['breadcrumb']	= $this->breadcrumbs->show();

		$this->data['planToPurchase']	= array('Within a week', 'Within 15 days', 'Within a month', 'Within an year', 'Never');
		$this->data['storeList']		= $this->CommonModel->getAllStoresList();

		$condition	= array('is_del' => 'N');
		$select		= 'id, title as source';
		if ($this->session->userdata['role_name'] == "shop") {
			$condition	= array('is_del' => array('N'), 'title' => array('Store Telle-Call', 'Walk In'));

			$this->data['sourceList'] = $this->CommonModel->selectwithIN(DbTable::SOURCE_MASTER, $select, $condition, 'source');
		} else {
			$this->data['sourceList'] = $this->CommonModel->select(DbTable::SOURCE_MASTER, $select, $condition, 'source');
		}

		$websiteEnqId = $this->CommonModel->select(DbTable::SOURCE_MASTER, 'id', array('is_del' => 'N', 'source' => 'Website'), '');

		$formData = array();

		$enquiryID	= $this->input->get('enquiry')  != null ? $this->input->get('enquiry')  : 0;
		$prospectID	= $this->input->get('prospect') != null ? $this->input->get('prospect') : 0;
		$inCallID	= $this->input->get('incall') 	!= null ? $this->input->get('incall') 	: 0;
		$appointmentID	= $this->input->get('appointment') 	!= null ? $this->input->get('appointment') 	: 0;
		$exchangeEnquiryId	= $this->input->get('exchange_id') 	!= null ? $this->input->get('exchange_id') 	: 0;
		$exchangeEnquirySignature	= $this->input->get('signature') != null ? $this->input->get('signature') : 0;
		if ($exchangeEnquiryId != '') {
			$signatureCalculate = md5($exchangeEnquiryId . "__" . date("Ymd"));
			if ($exchangeEnquirySignature != $signatureCalculate) {
				echo "Invalid Request";
				exit;
			} else {
				$this->page_title->push("Exchange Order > ");
				$this->data['pagetitle']	= $this->page_title->show();
				$this->data['exchange_enquiry_id'] = $exchangeEnquiryId;
			}
		}

		$leadType = "chat-call";
		$differencePrice = 0;
		if ($enquiryID > 0) {
			$leadType = "";

			$select		= 'first_name, last_name, emailid, mobile, source, product, location, enquiry_date, address, enquiry_billing_address, delivery_city, pincode, plan_to_purchase,enquiry_billing_name,enquiry_billing_state,enquiry_billing_pincode,enquiry_billing_contact_number,enquiry_billing_city,is_gift,enquiry_billing_email,billing_price,magento_order_id';

			$condition	= array('id' => $enquiryID);
			$details	= $this->CommonModel->select(DbTable::ENQUIRY_MASTER, $select, $condition);

			if (count($details) > 0) {
				$formData['enquiry_id_get']		= $enquiryID;
				$formData['full_name']			= $details[0]['first_name'] . ' ' . $details[0]['last_name'];
				$formData['email_id']			= $details[0]['emailid'];
				$formData['mobile_no']			= $details[0]['mobile'];
				$formData['enquiry_source']		= $details[0]['source'];
				$formData['store_id']			= $details[0]['location'];
				$formData['original_sku'] 		= $formData['product_id']			= $details[0]['product'];
				$formData['pincode']			= $details[0]['pincode'];
				$formData['enquiry_date']		= date('Y-m-d', strtotime($details[0]['enquiry_date']));
				$formData['delivery_city']		= $details[0]['delivery_city'];
				$formData['address']			= $details[0]['address'];
				$formData['billing_address']	= $details[0]['billing_address'];
				$formData['enquiry_billing_name']	= $details[0]['enquiry_billing_name'];
				$formData['enquiry_billing_city']	= $details[0]['enquiry_billing_city'];
				$formData['enquiry_billing_state']	= $details[0]['enquiry_billing_state'];
				$formData['enquiry_billing_pincode']	= $details[0]['enquiry_billing_pincode'];
				$formData['enquiry_billing_contact_number']	= $details[0]['enquiry_billing_contact_number'];
				$formData['plan_to_purchase']	= $details[0]['plan_to_purchase'];
				$formData['other_product_name']	= $details[0]['other_product_name'];
				$formData['is_gift']	= $details[0]['is_gift'];
				$formData['enquiry_billing_email']	= $details[0]['enquiry_billing_email'];
				$formData['billing_price']	= $details[0]['billing_price'];
				$formData['magento_order_id']	= $details[0]['magento_order_id'];
			}
		} else if ($prospectID > 0) {
			$leadType = "";

			$select		= 'name, email, mobile, source, city';
			$condition	= array('id' => $prospectID);
			$details	= $this->CommonModel->select(DbTable::PROSPECTS, $select, $condition);

			if (count($details) > 0) {
				$formData['prospect_id_get']	= $prospectID;
				$formData['full_name']			= $details[0]['name'];
				$formData['email_id']			= $details[0]['email'];
				$formData['mobile_no']			= $details[0]['mobile'];
				$formData['enquiry_date']		= date('Y-m-d');
				$formData['enquiry_source']		= $details[0]['source'];
				$formData['delivery_city']		= $details[0]['city'];
			}
		} else if ($inCallID > 0) {
			$leadType = "";

			$select		= 'knowlarity_number, customer_number';
			$condition	= array('id' => $inCallID);
			$details	= $this->CommonModel->select(DbTable::CALL_LOGS, $select, $condition);

			if (count($details) > 0) {
				$formData['in_call_id_get']		= $inCallID;
				$formData['mobile_no']			= $details[0]['customer_number'];
				$formData['enquiry_date']		= date('Y-m-d');
				$formData['enquiry_source']		= 64;

				$sql = "SELECT id, location FROM " . DbTable::LOCATION_MASTER . " WHERE virtual_number LIKE ?";
				$storeDetails = $this->db->query($sql, "%" . substr($details[0]["knowlarity_number"], -10) . "%")->result_array();

				if (count($storeDetails) > 0) {
					$formData['store_id'] = $storeDetails[0]['id'];
				}
			}
		} elseif ($appointmentID > 0) {
			$leadType = "";

			$select		= '*';
			$condition	= array('id' => $appointmentID);
			$details	= $this->CommonModel->select(DbTable::APPOINTMENT, $select, $condition);
			if (count($details) > 0) { /*echo '<pre>';
				print_r($details);
				echo '</pre>';exit;*/
				$formData['appointment_id_get']	= $appointmentID;
				$formData['full_name']			= $details[0]['name'];
				$formData['email_id']			= $details[0]['email'];
				$formData['mobile_no']			= $details[0]['mobile_no'];
				$formData['enquiry_date']		= date('Y-m-d');
				$formData['enquiry_source']		= $details[0]['source'];
				$formData['delivery_city']		= $details[0]['city'];
				$formData['store_id']			= $details[0]['store_code'];
				$formData['pincode']			= $details[0]['pincode'];
				$formData['location']			= $details[0]['location'];
			}
		} elseif ($exchangeEnquiryId > 0) {
			$leadType = "";

			$select		= 'first_name, last_name, emailid, mobile, source, product, location, enquiry_date, address, enquiry_billing_address, delivery_city, pincode, plan_to_purchase,enquiry_billing_name,enquiry_billing_state,enquiry_billing_pincode,enquiry_billing_contact_number,enquiry_billing_city,is_gift,enquiry_billing_email,billing_price,magento_order_id';

			$condition	= array('id' => $exchangeEnquiryId);
			$details	= $this->CommonModel->select(DbTable::ENQUIRY_MASTER, $select, $condition);

			if (count($details) > 0) {
				$formData['full_name']			= $details[0]['first_name'] . ' ' . $details[0]['last_name'];
				$formData['email_id']			= $details[0]['emailid'];
				$formData['mobile_no']			= $details[0]['mobile'];
				$formData['enquiry_source']		= $details[0]['source'];
				$formData['store_id']			= $details[0]['location'];
				$formData['original_sku'] 		= $formData['product_id']			= $details[0]['product'];
				$formData['pincode']			= $details[0]['pincode'];
				$formData['enquiry_date']		= date('Y-m-d');
				$formData['delivery_city']		= $details[0]['delivery_city'];
				$formData['address']			= $details[0]['address'];
				$formData['billing_address']	= $details[0]['billing_address'];
				$formData['enquiry_billing_name']	= $details[0]['enquiry_billing_name'];
				$formData['enquiry_billing_city']	= $details[0]['enquiry_billing_city'];
				$formData['enquiry_billing_state']	= $details[0]['enquiry_billing_state'];
				$formData['enquiry_billing_pincode']	= $details[0]['enquiry_billing_pincode'];
				$formData['enquiry_billing_contact_number']	= $details[0]['enquiry_billing_contact_number'];
				$formData['plan_to_purchase']	= $details[0]['plan_to_purchase'];
				$formData['other_product_name']	= $details[0]['other_product_name'];
				$formData['is_gift']	= $details[0]['is_gift'];
				$formData['enquiry_billing_email']	= $details[0]['enquiry_billing_email'];
				$formData['billing_price']	= $details[0]['billing_price'];
				$formData['magento_order_id']	= $details[0]['magento_order_id'];
			}
		}

		$this->data['enquiryInfo'] = $formData;

		if (!empty($_POST)) {
			$this->form_validation->set_rules('full_name', 'name', 'trim|required|max_length[100]');
			$this->form_validation->set_rules('email_id', 'email id', 'trim|max_length[100]|valid_email');
			$this->form_validation->set_rules('enquiry_billing_email', 'billing email id', 'trim|max_length[100]|valid_email');
			$this->form_validation->set_rules('mobile_no', 'mobile no', 'trim|required|max_length[15]');
			$this->form_validation->set_rules('enquiry_billing_contact_number', 'billing contact no', 'trim|required|max_length[15]');
			$this->form_validation->set_rules('enquiry_source', 'enquiry source', 'trim|required');
			$this->form_validation->set_rules('store_id', 'store id', 'trim|required');
			$this->form_validation->set_rules('product_id', 'product id', 'trim|required');
			// $this->form_validation->set_rules('enquiry_date', 'enquiry date', 'trim|required');
			$this->form_validation->set_rules('pincode', 'pincode', 'trim|max_length[6]');
			$this->form_validation->set_rules('enquiry_billing_pincode', 'billing pincode', 'trim|max_length[6]');
			$this->form_validation->set_rules('delivery_city', 'delivery city', 'trim|max_length[100]');
			$this->form_validation->set_rules('enquiry_billing_city', 'billing city', 'trim|max_length[100]');
			$this->form_validation->set_rules('enquiry_billing_state', 'billing state', 'trim|max_length[100]');
			$this->form_validation->set_rules('address', 'address', 'trim|max_length[500]');
			$this->form_validation->set_rules('billing_address', 'billing address', 'trim|max_length[500]');
			$this->form_validation->set_rules('plan_to_purchase', 'plan to purchase', 'trim');
			$this->form_validation->set_rules('billing_price', 'billing price', 'required');

			$this->form_validation->set_message('required', 'Please enter %s');
			$this->form_validation->set_message('min_length', '%s must contain atleast %d characters');
			$this->form_validation->set_message('max_length', '%s must not exceed %d characters length');
			$this->form_validation->set_message('numeric', 'Please enter valid %s');

			if ($this->form_validation->run()) {
				$this->db->trans_start();
				$is_gift = $this->input->post('is_gift');
				$mobile = $this->input->post('mobile_no');
				$storeId = $this->input->post('store_id');
				$postExchangeId = $this->input->post('exchange_enquiry_id');
				$billingprice = $this->input->post('billing_price');
				$transferId = $this->input->get('enquiry');
				if (intval($postExchangeId) != 0) {
					$select		= 'billing_price,magento_order_id,magento_cust_id';

					$condition	= array('id' => $postExchangeId);
					$postExchangeDetails	= $this->CommonModel->select(DbTable::ENQUIRY_MASTER, $select, $condition);
					if ($postExchangeDetails[0]['billing_price'] > $billingprice) {
						setAlert("Billing price must be greater than or equals to old item billing price", 'error');
						redirect('view-enquiry/' . $postExchangeId);
					}
					$differencePrice = $billingprice - $postExchangeDetails[0]['billing_price'];
					$magento_order_id = $postExchangeDetails[0]['magento_order_id'];
					$magento_cust_id = $postExchangeDetails[0]['magento_cust_id'];
				}
				if (intval($postExchangeId) == 0 && intval($transferId) == 0) {
					$checker = json_decode($this->EnquiryListModel->checkCreateLeadOldData($mobile, $storeId));

					if ($checker->status == 1) {
						setAlert($checker->message, 'error');
						redirect('create-lead');
					}
				}

				$enquiryData = array(
					"company"			=> $this->session->userdata['company_id'],
					"first_name"		=> $this->input->post('full_name'),
					"last_name"			=> '',
					"emailid"			=> $this->input->post('email_id'),
					// "lead_type"			=> "chat-call",
					"mobile"			=> $this->input->post('mobile_no'),
					"source"			=> $this->input->post('enquiry_source'),
					"status"			=> "6",
					"product"			=> $this->input->post('product_id'),
					"original_sku"		=> 	$this->input->post('product_id'),
					"location"			=> $this->input->post('store_id'),
					"nearest_location"	=> $this->input->post('store_id'),
					"enquiry_date"		=> date("Y-m-d H:i:s"),
					// "enquiry_date"		=> $this->input->post('enquiry_date'),
					"address"			=> str_replace("'", "`", $this->input->post('address')),
					"enquiry_billing_address"	=> str_replace("'", "`", $this->input->post('billing_address')),
					"enquiry_billing_name"	=> $this->input->post('enquiry_billing_name'),
					"enquiry_billing_city"	=> $this->input->post('enquiry_billing_city'),
					"enquiry_billing_state"	=> $this->input->post('enquiry_billing_state'),
					"enquiry_billing_pincode"	=> $this->input->post('enquiry_billing_pincode'),
					"enquiry_billing_contact_number"	=> $this->input->post('enquiry_billing_contact_number'),
					"enquiry_billing_email"				=> $this->input->post('enquiry_billing_email'),
					"delivery_city"		=> $this->input->post('delivery_city'),
					"pincode"			=> $this->input->post('pincode'),
					"plan_to_purchase"	=> $this->input->post('plan_to_purchase'),
					"billing_price"		=> $this->input->post('billing_price'),
					"remark"			=> "Walk-in",
					"created_by"		=> $this->session->userdata['first_name'] . ' ' . $this->session->userdata['last_name'] . ' ' . $this->session->userdata['role_name'],
					'other_product_name' => $this->input->post('other_product_name'),
					'is_gift' =>  isset($is_gift)  ? 1   : 0,
					'difference_price' => $differencePrice,
				);

				if ($leadType != "") {
					$enquiryData['lead_type'] = $leadType;
				}

				if ($enquiryID > 0) {
					$condition	= array('id' => $enquiryID);
					$result		= $this->CommonModel->update(DbTable::ENQUIRY_MASTER, $condition, $enquiryData);
				} else {
					if (intval($postExchangeId) > 0) {
						$enquiryData['exchanged_from'] = $postExchangeId;
						$enquiryData['magento_order_id'] = $magento_order_id;
						$enquiryData['magento_cust_id'] = $magento_cust_id;
					}
					$enquiryData['visible_to_store'] = 1;
					$result = $this->CommonModel->create(DbTable::ENQUIRY_MASTER, $enquiryData);
				}

				if ($result) {
					if (intval($postExchangeId) > 0) {
						$this->CommonModel->update(DbTable::ENQUIRY_MASTER, array('id' => $postExchangeId), array('status' => 8, 'exchanged_to' => $result));
					}

					if ($enquiryID > 0 && !empty($_POST)) {
						if ($this->input->post('store_id') != $details[0]['location']) {

							$tstoredetails = $this->CommonModel->select(DbTable::LOCATION_MASTER, 'location', array('id' => $this->input->post('store_id'), 'is_del' => 'N'));

							$to_store = count($tstoredetails) > 0 ? $tstoredetails[0]['location'] : 0;

							if ($details[0]['location'] != 0 || !empty($details[0]['location'])) {
								$fstoredetails = $this->CommonModel->select(DbTable::LOCATION_MASTER, 'location', array('id' => $details[0]['location'], 'is_del' => 'N'));
								$from_store = count($fstoredetails) > 0 ? $fstoredetails[0]['location'] : 0;
							} else {
								$from_store = 'OTHR';
							}

							$message = "Store assign from " . $from_store . " to store code is " . $to_store . ".";
							$this->saveComment($enquiryID, $message);
						}
					}
					if ($enquiryID == 0) {
						$enquiryID = $result;
					}

					if ($prospectID > 0) {
						$updateData	= array('enquiry_id' => $enquiryID);
						$condition	= array('id' => $prospectID);
						$result		= $this->CommonModel->update(DbTable::PROSPECTS, $condition, $updateData);
					}

					if ($appointmentID > 0) {
						$updateData	= array('enquiry_id' => $enquiryID, 'enquiry_datetime' => date('Y-m-d H:i:s'));
						$condition	= array('id' => $appointmentID);
						$result		= $this->CommonModel->update(DbTable::APPOINTMENT, $condition, $updateData);
					}

					$this->db->trans_commit();

					/* Start Changes done by sukanya 27.03.2020*/
					if ($this->input->post('enquiry_source') == $websiteEnqId) {
						$this->EnquiryListModel->sendLeadSms($enquiryID);
					}
					/* End Changes done by sukanya 27.03.2020*/

					setAlert('Enquiry details saved successfully');
					redirect('create-lead');
				} else {
					$this->db->trans_rollback();
					setAlert('Unable to create enquiry', 'error');
				}
			}
		}

		$this->template->admin_render('admin/enquiry/enquiry_list/create', $this->data);
	}
}