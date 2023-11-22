<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ShipmentsModel extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('default', true);
	}

	private function _add_role_base_filter()
	{
		$this->CommonModel->_add_role_base_filter($this->db);
	}

	public function getEnquiryDetails($enquiryID)
	{
		$this->db->select('id AS enquiry_id, em.first_name, em.last_name, em.address, em.city, em.pincode, em.emailid, em.mobile, em.alt_contact, em.pincode,em.lead_source,em.magento_order_id, em.source, em.delivery_mode');
		$this->db->from(DbTable::ENQUIRY_MASTER.' em');
		$this->db->where('em.id', $enquiryID);
		$this->_add_role_base_filter();
		return $this->db->get()->result_array();
	}

	public function getShipmentDetails($awbNo)
	{
		$this->db->select('s.*,em.emailid,em.lead_source,em.magento_order_id');
		$this->db->from(DbTable::SHIPMENT_MANIFEST.' s');
		$this->db->join(DbTable::ENQUIRY_MASTER.' em','em.id=s.enquiry_id','left');
		if ( filter_var($awbNo, FILTER_VALIDATE_INT) === false ) {
			$this->db->where('s.awb_number', $awbNo);
		}else{
			$this->db->where('s.awb_number', $awbNo);
			$this->db->or_where('s.enquiry_id', $awbNo);
		}
		return $this->db->get()->result_array();
	}

	public function getAllStoresListForShipment()
	{
		$this->db->select('lm.id, lm.location, lm.pincode, lm.address, lm.gst_number, lm.city, store_name, CONCAT(um.first_name, " ", um.last_name) AS manager, um.mobile AS contact, um.alt_contact, lm.virtual_number, um.emailid');
		$this->db->from(DbTable::LOCATION_MASTER.' lm');
		$this->db->join(DbTable::USER_MASTER.' um', 'um.location = lm.id', 'left');
		$this->db->where('lm.is_del', 'N');
		$this->db->where('lm.location != ', 'OTHR');
		$this->db->order_by('lm.store_name', 'ASC');

		return $this->db->get()->result_array();
	}
}