<?php


class Invoice {

	private $id;
	private $title;
	private $description;
	private $date;
	private $status;
	private $paid_status;
	private $card;
	private $username;
	private $reference;

	function __construct($id) {

		$DBHandle = DbConnect();
		$instance_sub_table = new Table("cc_invoice", "*");
		$QUERY = " id = " . $id;
		$return = null;
		$return = $instance_sub_table->Get_list($DBHandle, $QUERY, 0);

		$value = $return[0];
		if (!is_null($value)) {
			$this->id = $value["id"];
			$this->card = $value["id_card"];
			$this->description = $value["description"];
			$this->title = $value["title"];
			$this->status = $value["status"];
			$this->paid_status = $value["paid_status"];
			$this->date = $value["date"];
			$this->reference = $value["reference"];
		}

		if (!is_null($this->card)) {
			$instance_sub_table = new Table("cc_card", "lastname, firstname,username");
			$QUERY = " id = " . $this->card;
			$return = null;
			$return = $instance_sub_table->Get_list($DBHandle, $QUERY, 0);
			$value = $return[0];

			if (!is_null($value)) {
				$this->username = $value["lastname"] . " " . $value["firstname"] . " " . "(" . $value["username"] . ")";
			}
		}

	}

	function getId() {

		return $this->id;
	}

	function getReference() {

		return $this->reference;
	}

	function getTitle() {

		return $this->title;
	}

	function getDescription() {

		return $this->description;
	}

	function getCard() {

		return $this->card;
	}

	function getPriority() {

		return $this->priority;
	}

	function getStatus() {

		return $this->status;

	}
	function getPaidStatus() {

		return $this->paid_status;

	}

	function getDate() {

		return substr($this->date, 0, 10);
	}

	function getUsernames() {

		return $this->username;
	}

	function loadItems() {
		if (!is_null($this->id)) {
			$result = array ();
			$DBHandle = DbConnect();
			$instance_sub_table = new Table("cc_invoice_item", "*");
			$QUERY = " id_invoice = " . $this->id;
			$return = null;
			$return = $instance_sub_table->Get_list($DBHandle, $QUERY, "date", "ASC");
			$i = 0;
			foreach ($return as $value) {
				$comment = new InvoiceItem($value['id'], $value['description'], $value['date'], $value["price"], $value["VAT"],$value["type_ext"],$value["id_ext"]);
				$result[$i] = $comment;
				$i++;
			}
			//sort r�sult by date
			return $result;

		} else
			return null;

	}

	function loadDetailledItems() {
		if (!is_null($this->id)) {
			$result = array ();
			$DBHandle = DbConnect();
			$instance_sub_table = new Table("cc_invoice_item", "*");
			$QUERY = " id_invoice = " . $this->id;
			$return = null;
			$return = $instance_sub_table->Get_list($DBHandle, $QUERY, "date", "ASC");
			$i = 0;
			foreach ($return as $value) {
				if ($value['id_ext'] && $value['type_ext'] == "CALLS") {

					$billing_table = new Table("cc_billing_customer", "date,start_date");
					$billing_clause = "id = " . $value['id_ext'];
					$result_billing = $billing_table->Get_list($DBHandle, $billing_clause);
					if (is_array($result_billing) && !empty ($result_billing[0]['date'])) {
						$call_table = new Table("cc_call", "*");
						$call_clause = " card_id = " . $this->card . " AND stoptime< '" . $result_billing[0]['date'] . "'";
						if (!empty ($result_billing[0]['start_date'])) {
							$call_clause .= " AND stoptime >= '" . $result_billing[0]['start_date'] . "'";
						}
						$return_calls = $call_table->Get_list($DBHandle, $call_clause);
						foreach ($return_calls as $call) {
							$min = floor($call['sessiontime'] / 60);
							$sec = $call['sessiontime'] % 60;
							$item = new InvoiceItem(null, "CALL : " . $call['calledstation'] . " DURATION : " . $min . " min " . $sec . " sec", $call['starttime'], $call["sessionbill"], $value["VAT"], true);
							$result[$i] = $item;
							$i++;
						}
					}
				} else {
					$item = new InvoiceItem($value['id'], $value['description'], $value['date'], $value["price"], $value["VAT"],$value["type_ext"],$value["id_ext"]);
					$result[$i] = $item;
					$i++;
				}
			}
			//sort r�sult by date
			return $result;

		} else
			return null;

	}

	function loadPayments() {
		if (!is_null($this->id)) {
			$DBHandle = DbConnect();
			$instance_sub_table = new Table("cc_invoice_payment,cc_logpayment", "*");
			$CLAUSE = " id_invoice = " . $this->id . " AND id_payment = cc_logpayment.id";
			$result = null;
			$result = $instance_sub_table->Get_list($DBHandle, $CLAUSE, "date", "ASC");
			return $result;

		} else
			return null;
	}

	function delPayment($idpayment) {
		if (!is_null($this->id)) {
			$DBHandle = DbConnect();
			$instance_sub_table = new Table("cc_invoice_payment", "*");
			$CLAUSE = " id_invoice = " . $this->id . " AND id_payment = $idpayment";
			$result = null;
			$instance_sub_table->Delete_table($DBHandle, $CLAUSE);
		} else
			return null;
	}

	function addPayment($idpayment) {
		if (!is_null($this->id)) {
			$DBHandle = DbConnect();
			$instance_sub_table = new Table("cc_invoice_payment", "*");
			$fields = " id_invoice , id_payment";
			return $instance_sub_table->Add_table($DBHandle, '?, ?', $fields, null, null, false, null, array($this->id, $idpayment));
		} else
			return null;
	}

	// Customer checkout may close an issued invoice only when its database payments
	// cover its database items, including VAT. IN prevents duplicate links counting twice.
	function markPaidIfCovered() {
		if (is_null($this->id)) return false;
		$DBHandle = DbConnect();
		$query = 'UPDATE cc_invoice SET paid_status = 1 WHERE id = ? AND status = 1 AND paid_status = 0 AND ' .
			'(SELECT ROUND(COALESCE(SUM(price * (1 + VAT / 100)), 0), 5) FROM cc_invoice_item WHERE id_invoice = ?) <= ' .
			'(SELECT ROUND(COALESCE(SUM(payment), 0), 5) FROM cc_logpayment WHERE card_id = ? AND id IN ' .
			'(SELECT id_payment FROM cc_invoice_payment WHERE id_invoice = ?))';
		if (!$DBHandle->Execute($query, array($this->id, $this->id, $this->card, $this->id))) {
			throw new \RuntimeException('Cannot settle invoice');
		}
		if ((int)$DBHandle->Affected_Rows() !== 1) return false;
		$this->applyPaymentStatus(1);
		return true;
	}

	// Administrative manual status changes keep their existing meaning.
	function changeStatus($status) {
		if (is_null($this->id)) return false;
		$DBHandle = DbConnect();
		if (!$DBHandle->Execute('UPDATE cc_invoice SET paid_status = ? WHERE id = ? AND paid_status <> ?', array($status, $this->id, $status))) {
			return false;
		}
		if ((int)$DBHandle->Affected_Rows() !== 1) return false;
		$this->applyPaymentStatus($status);
		return true;
	}

	private function applyPaymentStatus($status) {
		$this->paid_status = $status;
		$DBHandle = DbConnect();
		foreach ($this->loadItems() as $item) {
			if ($item->getExtType() === 'DID' && is_numeric($item->getExtId())) {
				$param = $status == 0 ? 'reminded = 1, month_payed = month_payed - 1' : 'reminded = 0, month_payed = month_payed + 1';
				$query = 'UPDATE cc_did_use SET ' . $param . ' WHERE id_did = ? AND id_cc_card = ? AND activated = 1 ' .
					"AND (releasedate IS NULL OR releasedate < '1984-01-01 00:00:00')";
				if (!$DBHandle->Execute($query, array($item->getExtId(), $this->card))) {
					throw new \RuntimeException('Cannot update paid DID period');
				}
			}
		}
	}

	function insertInvoiceItem($desc, $price, $VAT) {

		$DBHandle = DbConnect();
		$instance_sub_table = new Table("cc_invoice_item", "*");
		$QUERY_FIELDS = 'id_invoice, description,price, VAT';
		$QUERY_VALUES = "'$this->id', '$desc','$price', '$VAT'";
		$return = $instance_sub_table->Add_table($DBHandle, $QUERY_VALUES, $QUERY_FIELDS, 'cc_invoice_item', 'id');

	}

	public static function getStatusDisplay($status) {

		switch ($status) {
			case 0 :
				return "OPEN";
			case 1 :
				return "CLOSE";

		}

	}

	public static function getPaidStatusDisplay($status) {

		switch ($status) {
			case 0 :
				return "UNPAID";
			case 1 :
				return "PAID";

		}

	}

}


