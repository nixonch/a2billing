<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of A2Billing (http://www.a2billing.net/)
 *
 * A2Billing, Commercial Open Source Telecom Billing platform,   
 * powered by Star2billing S.L. <http://www.star2billing.com/>
 * 
 * @copyright   Copyright (C) 2004-2009 - Star2billing S.L. 
 * @author      Belaid Arezqui <areski@gmail.com>
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 * @package     A2Billing
 *
 * Software License Agreement (GNU Affero General Public License)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * 
**/


include ("./lib/customer.defines.php");

getpost_ifset(array('id', 'key'));
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	exit;
}
// Validate exactly the body returned to PayPal, never GET or sanitized globals.
$paypal_body = file_get_contents('php://input');
$paypal_post = array();
parse_str($paypal_body, $paypal_post);
foreach ($paypal_post as $value) {
	if (!is_string($value)) {
		http_response_code(400);
		exit;
	}
}
if (!isset($paypal_post['payment_status'], $paypal_post['txn_type']) ||
	$paypal_post['payment_status'] !== 'Completed' || $paypal_post['txn_type'] !== 'subscr_payment') {
	exit;
}
if (!isset($id, $key) || !is_string($id) || !ctype_digit($id) || (int)$id <= 0 || !is_string($key) ||
	!isset($paypal_post['txn_id'], $paypal_post['mc_gross'], $paypal_post['mc_currency']) ||
	!preg_match('/^[A-Za-z0-9]{1,30}$/D', $paypal_post['txn_id']) ||
	!preg_match('/^[0-9]+(?:\.[0-9]{1,2})?$/D', $paypal_post['mc_gross']) ||
	(float)$paypal_post['mc_gross'] <= 0 || (float)$paypal_post['mc_gross'] > 9999999.99 ||
	$paypal_post['mc_currency'] !== strtoupper(BASE_CURRENCY)) {
	http_response_code(400);
	exit;
}

// Existing configuration uses ssl://www.paypal.com; use the HTTPS IPN endpoint.
$paypal_host = parse_url(str_replace('ssl://', 'https://', PAYPAL_VERIFY_URL), PHP_URL_HOST);
if (!in_array($paypal_host, array('www.paypal.com', 'ipnpb.paypal.com', 'www.sandbox.paypal.com', 'ipnpb.sandbox.paypal.com'), true) ||
	!function_exists('curl_init')) {
	write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: verification configuration unavailable');
	http_response_code(503);
	exit;
}
$paypal_verify_url = strpos($paypal_host, 'sandbox') !== false
	? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' : 'https://ipnpb.paypal.com/cgi-bin/webscr';
$paypal_curl = curl_init($paypal_verify_url);
curl_setopt_array($paypal_curl, array(
	CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => 'cmd=_notify-validate&' . $paypal_body,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_CONNECTTIMEOUT => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_SSL_VERIFYPEER => true,
	CURLOPT_SSL_VERIFYHOST => 2,
	CURLOPT_FOLLOWLOCATION => false,
	CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded', 'Connection: close')
));
$paypal_response = curl_exec($paypal_curl);
$paypal_http_code = curl_getinfo($paypal_curl, CURLINFO_HTTP_CODE);
curl_close($paypal_curl);
if ($paypal_response === false || $paypal_http_code !== 200) {
	write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: verification transport failed');
	http_response_code(503);
	exit;
}
if (trim($paypal_response) !== 'VERIFIED') {
	write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: notification not VERIFIED');
	http_response_code(400);
	exit;
}
$paypal_account = trim($A2B->config['epayment_method']['paypal_subscription_account']);
$paypal_receiver_matches = false;
foreach (array('business', 'receiver_email', 'receiver_id') as $field) {
	if ($paypal_account !== '' && isset($paypal_post[$field]) && strcasecmp($paypal_account, $paypal_post[$field]) === 0) {
		$paypal_receiver_matches = true;
	}
}
if (!$paypal_receiver_matches) {
	write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: wrong payment recipient');
	http_response_code(400);
	exit;
}
$txn_id = $paypal_post['txn_id'];
$payment_gross = $paypal_post['mc_gross'];
$payer_email = isset($paypal_post['payer_email']) ? $paypal_post['payer_email'] : '';
$DBHandle = DbConnect();
$table_card = new Table("cc_card", "username,useralias,UNIX_TIMESTAMP(creationdate) creationdate,vat,firstname,lastname,email");
$result = $table_card->Get_list($DBHandle, 'id = ?', null, null, null, null, null, null, null, 0, array($id));

if (!is_array($result) || count($result) !== 1) {
	write_log(LOGFILE_EPAYMENT, basename(__FILE__) . ' line:' . __LINE__ . "-PAYPAL Reccurring Payment Failed : card id( $id ) not found");
	http_response_code(400);
	exit;
}

$card = $result[0];
$username = $result[0]['username'];
$creationdate = $result[0]['creationdate'];
$useralias = $result[0]['useralias'];
$vat = $result[0]['vat'];
$firstname = $result[0]['firstname'];
$lastname = $result[0]['lastname'];
$email = $result[0]['email'];
$newkey = securitykey(EPAYMENT_TRANSACTION_KEY, $username . "^" . $id . "^" . $useralias . "^" . $creationdate);

if (hash_equals($newkey, $key)) {
	write_log(LOGFILE_EPAYMENT, basename(__FILE__) . ' line:' . __LINE__ . "----------- Transaction Key Verified ------------");
} else {
	write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: invalid customer key');
	http_response_code(400);
	exit;
}

if (!is_numeric($vat) || !is_finite((float)$vat) || $vat < 0) {
	http_response_code(500);
	exit;
}
$amount_paid = $payment_gross;
$amount_without_vat = $amount_paid / (1 + $vat / 100);
$nowDate = date("Y-m-d H:i:s");

// The existing UNIQUE txn_id index is the claim, including concurrent requests.
// Claim before any balance/journal write: MyISAM cannot roll back partial writes.
$claimed = $DBHandle->Execute('INSERT INTO cc_paypal (txn_id, payment_status, txn_type, mc_gross, mc_currency, item_number) VALUES (?, ?, ?, ?, ?, ?)',
	array($txn_id, 'Processing', 'subscr_payment', $amount_paid, $paypal_post['mc_currency'], $id));
if (!$claimed) {
	$paypal_table = new Table('cc_paypal', 'payment_status');
	$previous = $paypal_table->Get_list($DBHandle, 'txn_id = ?', null, null, null, null, null, null, null, 0, array($txn_id));
	if (is_array($previous) && count($previous) === 1 && $previous[0]['payment_status'] === 'Completed') {
		write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: duplicate ignored, txn_id=' . $txn_id);
		http_response_code(200);
	} else {
		write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: claim failed or unfinished payment requires review, txn_id=' . $txn_id);
		http_response_code(503);
	}
	exit;
}
$require_recurring_write = function ($result) {
	if (!$result) throw new \RuntimeException('Recurring payment database write failed');
	return $result;
};

try {

$Query = "INSERT INTO cc_payments ( customers_id, customers_name, customers_email_address, item_name, payment_method,cc_number,orders_status, " .
" last_modified, date_purchased, orders_date_finished, orders_amount, currency, currency_value) values (" .
" ?, ?, ?, 'RECURRING PAYMENT', 'PAYPAL', ?, '2', ?, ?, ?, ?, ?, '1')";
$require_recurring_write($DBHandle->Execute($Query, array($id, $firstname . ' ' . $lastname, $email,
	substr($payer_email, 0, 32), $nowDate, $nowDate, $nowDate, $amount_paid, BASE_CURRENCY)));

$instance_table = new Table("cc_card", "username, id");
$param_update = 'credit = credit + ?';
$FG_EDITION_CLAUSE = 'id = ?';
$require_recurring_write($instance_table->Update_table($DBHandle, $param_update, $FG_EDITION_CLAUSE, null, array($amount_without_vat, $id)));
write_log(LOGFILE_EPAYMENT, basename(__FILE__) . ' line:' . __LINE__ . "-Recurring payment" . " Update_table cc_card : $param_update - CLAUSE : $FG_EDITION_CLAUSE");

$field_insert = "date, credit, card_id, description";
$value_insert = '?, ?, ?, ?';
$instance_sub_table = new Table("cc_logrefill", $field_insert);
$id_logrefill = $require_recurring_write($instance_sub_table->Add_table($DBHandle, $value_insert, null, null, 'id', false, null,
	array($nowDate, $amount_without_vat, $id, gettext('Reccurring payment : automated refill'))));
write_log(LOGFILE_EPAYMENT, basename(__FILE__) . ' line:' . __LINE__ . "-Recurring payment" . " Add_table cc_logrefill : $field_insert - VALUES $value_insert");

$field_insert = "date, payment, card_id, id_logrefill, description";
$value_insert = '?, ?, ?, ?, ?';
$instance_sub_table = new Table("cc_logpayment", $field_insert);
$id_payment = $require_recurring_write($instance_sub_table->Add_table($DBHandle, $value_insert, null, null, 'id', false, null,
	array($nowDate, $amount_paid, $id, $id_logrefill, gettext('Reccurring payment : automated refill'))));
write_log(LOGFILE_EPAYMENT, basename(__FILE__) . ' line:' . __LINE__ . "-Recurring payment" . " Add_table cc_logpayment : $field_insert - VALUES $value_insert");

//ADD an INVOICE
$reference = generate_invoice_reference();
$field_insert = "date, id_card, title ,reference, description,status,paid_status";
$date = $nowDate;
$card_id = $id;
$title = gettext("CUSTOMER REFILL");
$description = gettext("Invoice for refill");
$value_insert = '?, ?, ?, ?, ?, 1, 1';
$instance_table = new Table("cc_invoice", $field_insert);
$id_invoice = $require_recurring_write($instance_table->Add_table($DBHandle, $value_insert, null, null, 'id', false, null,
	array($date, $card_id, $title, $reference, $description)));

//load vat of this card
if (!empty ($id_invoice) && is_numeric($id_invoice)) {
	$amount = $amount_without_vat;
	$description = gettext("Automated Refill : recurring payment");
	$field_insert = "date, id_invoice ,price,vat, description";
	$instance_table = new Table("cc_invoice_item", $field_insert);
	$value_insert = '?, ?, ?, ?, ?';
	$require_recurring_write($instance_table->Add_table($DBHandle, $value_insert, null, null, 'id', false, null,
		array($date, $id_invoice, $amount, $vat, $description)));
}

//link payment to this invoice
$table_payment_invoice = new Table("cc_invoice_payment", "*");
$fields = " id_invoice , id_payment";
$require_recurring_write($table_payment_invoice->Add_table($DBHandle, '?, ?', $fields, null, null, false, null, array($id_invoice, $id_payment)));

//END INVOICE
//Agent commision
// test if this card have a agent
$table_transaction = new Table();
$result_agent = $table_transaction->SQLExec($DBHandle, "SELECT cc_card_group.id_agent FROM cc_card LEFT JOIN cc_card_group ON cc_card_group.id = cc_card.id_group WHERE cc_card.id = $id");

if (is_array($result_agent) && !is_null($result_agent[0]['id_agent']) && $result_agent[0]['id_agent'] > 0) {
	//test if the agent exist and get its commission
	$id_agent = $result_agent[0]['id_agent'];
	$agent_table = new Table("cc_agent", "commission");
	$agent_clause = "id = " . $id_agent;
	$result_agent = $agent_table->Get_list($DBHandle, $agent_clause);

	if (is_array($result_agent) && is_numeric($result_agent[0]['commission']) && $result_agent[0]['commission'] > 0) {
		$field_insert = "id_payment, id_card, amount,description,id_agent";
		$commission = ceil(($amount_paid * ($result_agent[0]['commission']) / 100) * 100) / 100;
		$description_commission = gettext("AUTOMATICALY GENERATED COMMISSION!");
		$description_commission .= "\nID CARD : " . $id;
		$description_commission .= "\nID PAYMENT : " . $id_payment;
		$description_commission .= "\nPAYMENT AMOUNT: " . $amount_paid;
		$description_commission .= "\nCOMMISSION APPLIED: " . $result_agent[0]['commission'];
		$value_insert = '?, ?, ?, ?, ?';
		$commission_table = new Table("cc_agent_commission", $field_insert);
		$id_commission = $require_recurring_write($commission_table->Add_table($DBHandle, $value_insert, null, null, 'id', false, null,
			array($id_payment, $id, $commission, $description_commission, $id_agent)));
	}
}

$require_recurring_write($DBHandle->Execute('UPDATE cc_paypal SET payment_status = ? WHERE txn_id = ?', array('Completed', $txn_id)));
write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: completed, txn_id=' . $txn_id);
http_response_code(200);
} catch (\Exception $error) {
	// Retain the claim even if the balance write already succeeded; never credit twice.
	$DBHandle->Execute('UPDATE cc_paypal SET payment_status = ? WHERE txn_id = ?', array('Error', $txn_id));
	write_log(LOGFILE_EPAYMENT, 'Recurring PayPal: partial failure requires review, txn_id=' . $txn_id);
	http_response_code(500);
	exit;
}

