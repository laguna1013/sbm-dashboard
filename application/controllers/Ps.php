<?php
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: *");
defined('BASEPATH') OR exit('No direct script access allowed');

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Ps extends CI_Controller {
	public function __construct(){
		parent::__construct();
		$this->load->helper('url');
		$this->load->library('session');
		$this->load->model('ps_model');
		$this->load->model('user_model');
  }
  public function add_item(){
    $item = array(
      'inventory_id' => $this->input->post('inventory_id'),
      'created_user_id' => $this->input->post('created_user_id'),
      'user_access' => $this->input->post('user_access'),
      'gross_weight' => $this->input->post('gross_weight'),
      'category' => $this->input->post('category'),
      'description' => $this->input->post('description'),
      'vendor_description' => $this->input->post('vendor_description'),
      'packing_info' => $this->input->post('packing_info'),
      'uom' => $this->input->post('uom'),
      'price' => $this->input->post('price'),
      'cbm' => $this->input->post('cbm'),
      'qty' => $this->input->post('qty'),
      'moq' => $this->input->post('moq'),
      'status' => $this->input->post('status'),
      'qty_display' => $this->input->post('qty_display'),
      'created_at' => date('Y-m-d h:i:s'),
      'updated_at' => date('Y-m-d h:i:s')
		);
    $res = $this->ps_model->add_item($item);
    $file_uploaded = 0;
    if($res == 1){
      $file = $this->input->post('image');
      if(isset($file) && (!empty($file))){
        $file_uploaded = $this->upload_file($file, $this->input->post('inventory_id'));
        if($file_uploaded != 1){
          $res = -2;
        }
      }
    }
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
  }
  public function update_item(){
    $item = array(
      'created_user_id' => $this->input->post('created_user_id'),
      'user_access' => $this->input->post('user_access'),
      'gross_weight' => $this->input->post('gross_weight'),
      'category' => $this->input->post('category'),
      'description' => $this->input->post('description'),
      'vendor_description' => $this->input->post('vendor_description'),
      'packing_info' => $this->input->post('packing_info'),
      'uom' => $this->input->post('uom'),
      'price' => $this->input->post('price'),
      'cbm' => $this->input->post('cbm'),
      'qty' => $this->input->post('qty'),
      'moq' => $this->input->post('moq'),
      'status' => $this->input->post('status'),
      'qty_display' => $this->input->post('qty_display'),
      'updated_at' => date('Y-m-d h:i:s')
		);
    $res = $this->ps_model->update_item($item, $this->input->post('inventory_id'));

    $file_uploaded = 0;
    if($res == 1){
      $file = $this->input->post('image');
      if(isset($file) && (!empty($file)) && (!strpos($file, 'uploads'))){
        $file_uploaded = $this->upload_file($file, $this->input->post('inventory_id'));
        if($file_uploaded != 1){
          $res = -2;
        }
      }else{
        $res = 1;
      }
    }
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
  }
  public function update_item_status(){
    $res = $this->ps_model->update_item_status($this->input->post('status'), $this->input->post('inventory_id'));
    echo json_encode(array(
      'status' => 'success',
      'status_code' => 200,
      'data' => $res
    ));
  }
  private function upload_file($file, $inventory_id){
    $target_dir = 'C:/inetpub/wwwroot/sbm-dashboard/uploads/'; // add the specific path to save the file
    $data = explode(',', $file);
    $decoded_file = base64_decode($data[1]); // decode the file
    $extension = explode('/', mime_content_type($file))[1]; // extract extension from mime type
    $file_name = uniqid() .'.'. $extension; // rename file as a unique name
    $file_dir = $target_dir . $file_name;
    try {
      file_put_contents($file_dir, $decoded_file); // save
      return $this->ps_model->update_item_image(base_url(). 'uploads/' . $file_name, $inventory_id);
    } catch (Exception $e) {
      return -1;
    }
  }
  public function get_item(){
    $res = $this->ps_model->get_item();
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
  }
  public function get_item_by_id(){
    $res = $this->ps_model->get_item_by_id($this->input->post('inventory_id'));
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
  }
  public function remove_item(){
    $res = $this->ps_model->remove_item($this->input->post('inventory_id'));
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
  }
	public function filter_items($items){
		$valid = array();
		$invalid_ids = array();
		foreach($items as $item){
			$flag = $this->ps_model->validate($item->inventory_id);
			if($flag == 0){
				array_push($valid, $item);
			}else{
				array_push($invalid_ids, $item->inventory_id);
			}
		}
		return array('items' => $valid, 'invalid_ids' => $invalid_ids);
	}
	public function add_batch_item(){
		$items = json_decode($this->input->post('items'));
		$filtered = $this->filter_items($items);
		$res = $this->ps_model->add_batch_item($filtered['items']);
		echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res,
			'invalid_ids' => $filtered['invalid_ids']
		));
	}
	public function add_order(){
		$order = array(
			'order_id' => $this->input->post('order_id'),
			'customer_id' => $this->input->post('customer_id'),
			'order_time' => $this->input->post('order_time')
		);
		$items = json_decode($this->input->post('items'));
		$res = $this->ps_model->add_order($order);
		if($res == 1){
			$res = $this->ps_model->add_order_items($this->input->post('order_id'), $items);
		}
		echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
	}
	public function get_orders(){
		$limit = $this->input->post('limit');
		$customer_id = $this->input->post('customer_id');
    $res = $this->ps_model->get_orders($customer_id, $limit);
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
  }
	public function get_all_orders(){
    $res = $this->ps_model->get_all_orders();
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
  }
	public function get_order_details(){
		$order_id = $this->input->post('order_id');
    $res = $this->ps_model->get_order_details($order_id);
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
  }
	public function get_ps_users(){
		$res = $this->user_model->get_ps_users();
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
	}
	public function approve_order(){
		$order_id = $this->input->post('order_id');
		$res = $this->ps_model->approve_order($order_id);
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
	}
	public function delete_order(){
		$order_id = $this->input->post('order_id');
		$res = $this->ps_model->delete_order($order_id);
    echo json_encode(array(
			'status' => 'success',
			'status_code' => 200,
			'data' => $res
		));
	}
	public function send_mail(){

		$spreadsheet = new Spreadsheet();
		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setCellValue('A1', "Branch ID");
		$sheet->setCellValue('B1', "sorting order by weights");
		$sheet->setCellValue('C1', "Inventory ID");
		$sheet->setCellValue('D1', "Vendor description");
		$sheet->setCellValue('E1', "Description");
		$sheet->setCellValue('F1', "Packing info");
		$sheet->setCellValue('G1', "Cost");
		$sheet->setCellValue('H1', "Q'ty");
		$sheet->setCellValue('I1', "Customer Unit of Measurment");
		$sheet->setCellValue('J1', "Subtotal");
		$sheet->setCellValue('K1', "G.W. (lb)");
		$sheet->setCellValue('L1', "Total G.W. (lb)");
		$sheet->setCellValue('M1', 'Subcharge(20%)');
		$sheet->setCellValue('N1', "max order q'ty");
		$sheet->setCellValue('O1', "cbm");
		$data = json_decode($this->input->post('order_details'));
		$file_name = $this->input->post('po_id') . '.xlsx';

		$rows = 2;
		foreach ($data as $val){
      $sheet->setCellValue('A' . $rows, $val->branch_id);
      $sheet->setCellValue('B' . $rows, $val->g_weight);
      $sheet->setCellValue('C' . $rows, $val->i_id);
      $sheet->setCellValue('D' . $rows, $val->v_desc);
			$sheet->setCellValue('E' . $rows, $val->desc);
      $sheet->setCellValue('F' . $rows, $val->p_info);
			$sheet->setCellValue('G' . $rows, $val->cost);
			$sheet->setCellValue('H' . $rows, $val->qty);
			$sheet->setCellValue('I' . $rows, $val->uom);
			$sheet->setCellValue('J' . $rows, $val->subtotal);
			$sheet->setCellValue('K' . $rows, $val->gw);
			$sheet->setCellValue('L' . $rows, $val->t_gw);
			$sheet->setCellValue('M' . $rows, $val->subcharge);
			$sheet->setCellValue('N' . $rows, $val->moq);
			$sheet->setCellValue('O' . $rows, $val->cbm);
      $rows++;
    }
		$writer = new Xlsx($spreadsheet);

		$writer->save("C:/inetpub/wwwroot/sbm-dashboard/uploads/orders/".$file_name);

		$order_info = array();
		$order_info['order_id'] = $this->input->post('po_id');
		$order_info['items'] = $data;
		$order_info['user_name'] = $this->input->post('user_name');

		$this->load->library('email');
		$config = array();
		$config['protocol'] = 'smtp';
		$config['smtp_host'] = 'a2plcpnl0005.prod.iad2.secureserver.net';
		$config['smtp_user'] = 'dashboard@sbmtec.com';
		$config['smtp_pass'] = '#R%c2O[G]WL@';
		$config['smtp_port'] = 587;
		$config['charset'] = 'utf-8';
		$config['wordwrap'] = TRUE;
		$config['mailtype'] = 'html';
		$this->email->initialize($config);

		$from = 'dashboard@sbmtec.com';
    $to = $this->input->post('to');
    $subject = $this->input->post('subject');
    $message = $this->load->view('email/po_mail', $order_info, true);

		$this->email->set_newline("\r\n");
    $this->email->from($from, 'Purchasing System');
    $this->email->to($to);
    $this->email->subject($subject);
    $this->email->message($message);
		$this->email->attach("C:/inetpub/wwwroot/sbm-dashboard/uploads/orders/".$file_name);
		if ($this->email->send()) {
			echo json_encode(array(
				'status' => 'success',
				'status_code' => 200,
				'data' => "Mail sent successfully!",
				'content' => $data
			));
    } else {
			echo json_encode(array(
				'status' => 'failed',
				'status_code' => 400,
				'data' => show_error($this->email->print_debugger())
			));
    }
	}
}