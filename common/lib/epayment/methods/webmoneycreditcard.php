<?php

define('MODULE_PAYMENT_WM_TEXT_TITLE', 'WebMoney');
define('MODULE_PAYMENT_WM_TEXT_DESCRIPTION', 'WebMoney');
define('MODULE_PAYMENT_WM_LMI_PAYMENT_DESC', 'Connection service');

class webmoneycreditcard {
	var $code, $title, $description, $enabled, $current_wm_currency;

	public function __construct() {
		global $order;

		$this->code = 'webmoneycreditcard';
		$this->title = MODULE_PAYMENT_WM_TEXT_TITLE;
		$this->description = MODULE_PAYMENT_WM_TEXT_DESCRIPTION;
		$this->sort_order = 1;
		$this->enabled = ((MODULE_PAYMENT_WM_STATUS_10 == 'True') ? true : false);

		$this->form_action_url = 'https://merchant.webmoney.ru/lmi/payment.asp';
	}

	public function keys() {
		return array(
				  'MODULE_PAYMENT_WM_STATUS_10',		'MODULE_PAYMENT_WM_WMID_10'
				, 'MODULE_PAYMENT_WM_PURSE_WMU_10',		'MODULE_PAYMENT_WM_PURSE_WMR_10'
				, 'MODULE_PAYMENT_WM_CACERT_10',		'MODULE_PAYMENT_WM_LMI_SECRET_KEY_10'
				, 'MODULE_PAYMENT_WM_LMI_SIM_MODE_10',		'MODULE_PAYMENT_WM_LMI_HASH_METHOD_10'
				);
	}

	public function javascript_validation() {
		return false;
	}

	public function selection() {

		
		   global $order;
		   if (MODULE_PAYMENT_WM_PURSE_WMU_10) $purse_type[] = array('id' => 'WMU', 'text' => 'Ukraine Hryvnia (UAH)');
		   if (MODULE_PAYMENT_WM_PURSE_WMR_10) $purse_type[] = array('id' => 'WMP', 'text' => 'Russian Rouble (RUB)');

		   $selection = array(
		   'id' => $this->code,
		   'module' => $this->title,
		   'fields' => array(array('title' => 'Choose type', 'field' => tep_draw_pull_down_menu('wm_purse_type', $purse_type)))
		   );

		   return $selection;
		
		//return array('id' => $this->code, 'module' => $this->title);
	}

	public function get_CurrentCurrency()
	{
		switch($_POST['wm_purse_type'])
		{
			case 'WMU': $getcur = "UAH"; break;
			case 'WMZ': $getcur = "USD"; break;
			case 'WME': $getcur = "EUR"; break;
			case 'WMP': $getcur = "RUB"; break;
			default   : $getcur = ""; break;
		}
		   return $getcur;
	}

	public function pre_confirmation_check() {
		return false;
	}

	public function confirmation() {
		return false;
	}

	public function process_button($transactionID = 0, $key= "") {
		global $order, $currencies, $currency;

		$process_button_string = tep_draw_hidden_field('LMI_PAYMENT_AMOUNT', $order->info['total']);
		$process_button_string .= tep_draw_hidden_field('LMI_PAYMENT_DESC', MODULE_PAYMENT_WM_LMI_PAYMENT_DESC);
//		$process_button_string .= tep_draw_hidden_field('LMI_PAYMENT_NO', '1');

		if ($_POST['wm_purse_type'] == 'WMU') {
		    $process_button_string .= tep_draw_hidden_field('LMI_PAYEE_PURSE', MODULE_PAYMENT_WM_PURSE_WMU_10);
		    $process_button_string .= tep_draw_hidden_field('LMI_ALLOW_SDP', '10');
		} elseif ($_POST['wm_purse_type'] == 'WMZ')
		   $process_button_string .= tep_draw_hidden_field('LMI_PAYEE_PURSE', MODULE_PAYMENT_WM_PURSE_WMZ_10);
		elseif ($_POST['wm_purse_type'] == 'WME')
		   $process_button_string .= tep_draw_hidden_field('LMI_PAYEE_PURSE', MODULE_PAYMENT_WM_PURSE_WME_10);
		elseif ($_POST['wm_purse_type'] == 'WMP')
		   $process_button_string .= tep_draw_hidden_field('LMI_PAYEE_PURSE', MODULE_PAYMENT_WM_PURSE_WMR_10);

		$process_button_string .= tep_draw_hidden_field('LMI_SIM_MODE', MODULE_PAYMENT_WM_LMI_SIM_MODE_10);

		$process_button_string .= tep_draw_hidden_field('LMI_SUCCESS_URL', tep_href_link("userinfo", '', 'SSL'));
//		$process_button_string .= tep_draw_hidden_field('LMI_SUCCESS_URL', tep_href_link("checkout_success", '', 'SSL'));
		$process_button_string .= tep_draw_hidden_field('LMI_FAIL_URL', tep_href_link("checkout_payment", '', 'SSL'));

		$process_button_string .= tep_draw_hidden_field('LMI_RESULT_URL', tep_href_link("checkout_process", '', 'SSL'));

		$process_button_string .= tep_draw_hidden_field('transactionID',$transactionID);
		$process_button_string .= tep_draw_hidden_field('sess_id', session_id());
		$process_button_string .= tep_draw_hidden_field('key', $key);

		/*For debug only*/
		//write_log(LOGFILE_EPAYMENT, basename(__FILE__).' - WM URL'.$process_button_string);
		return $process_button_string;
	}

	public function get_OrderStatus()
	{
		//Если есть номер платежа в системе WebMoney Transfer, выполненный в процессе обработки

		if ($_POST['LMI_SYS_TRANS_NO'] != ""){
			return 2; //то возвращаем статус завершенного успешно
		}
		else { 
			return -2; //иначе статус Failed
		}
	}

	public function before_process() {
		return false;
	}

	public function after_process() {
		return false;
	}

}


?>

