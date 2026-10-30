<?php

// Run with PHP 5.6 and PHP 7.x: php addons/contrib/test-cases/test-0002-php56-php7.php
// Uses in-memory query results; no database or Asterisk connection is opened.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

define('FSROOT', dirname(dirname(dirname(__DIR__))) . '/common/');
require_once FSROOT . 'lib/interface/constants.php';
require_once FSROOT . 'lib/Class.A2Billing.php';
require_once FSROOT . 'lib/Class.Table.php';
require_once FSROOT . 'lib/Class.RateEngine.php';

function compatibilityAssertSame($expected, $actual, $message)
{
    if ($actual !== $expected) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true)
            . ', got ' . var_export($actual, true));
    }
}

class CompatibilityBilling extends A2Billing
{
    public $messages = array();

    public function write_log($output, $tobuffer = 1, $line_file_info = '')
    {
        $this->messages[] = $output;
    }
}

class CompatibilityPackageResult
{
    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function RecordCount()
    {
        return count($this->rows);
    }

    public function fetchRow()
    {
        return array_shift($this->rows);
    }
}

class CompatibilityPackageDatabase
{
    public $rows = array();
    public $queries = array();

    public function Execute($query)
    {
        $this->queries[] = $query;
        return new CompatibilityPackageResult($this->rows);
    }

    public function ErrorNo()
    {
        return 0;
    }
}

// Both omitted and explicit null foreign-key arrays must leave links disabled.
foreach (array(array(), array(null, null), array(null, array('card_id')),
    array(array('cc_card'), null), array(array(), array())) as $arguments) {
    array_unshift($arguments, 'cc_call', 'id');
    $reflection = new ReflectionClass('Table');
    $table = $reflection->newInstanceArgs($arguments);
    compatibilityAssertSame(null, $table->FK_TABLES, 'Unused foreign-key tables');
    compatibilityAssertSame(null, $table->FK_EDITION_CLAUSE, 'Unused foreign-key fields');
}

$table = new Table('cc_call', 'id', array('cc_card'), array('card_id'), 42, false);
compatibilityAssertSame(array('cc_card'), $table->FK_TABLES, 'Foreign-key tables');
compatibilityAssertSame(array('card_id'), $table->FK_EDITION_CLAUSE, 'Foreign-key fields');
compatibilityAssertSame(42, $table->FK_ID_VALUE, 'Foreign-key ID');
compatibilityAssertSame(false, $table->FK_DELETE, 'Foreign-key delete behavior');
$table = new Table('cc_call', 'id', array('cc_card'), array('card_id', 'other_id'));
compatibilityAssertSame(null, $table->FK_TABLES, 'Mismatched foreign-key arrays');

// Exercise the real debug() signature and package selection through RateEngine.
$agi = null;
$A2B = new CompatibilityBilling();
$A2B->DBHandle = new CompatibilityPackageDatabase();
$A2B->config = array('database' => array('dbtype' => 'mysql'));
$A2B->margintotal = 1;
$A2B->mode = '';
$A2B->agiconfig = array(
    'verbosity_level' => 0,
    'logging_level' => DEBUG,
    'min_credit_2call' => 0,
    'maxtime_tounlimited_calls' => 5400,
    'maxtime_tofree_calls' => 3600
);
$A2B->DBHandle->rows = array(array(
    0 => 42, 'packagetype' => 0, 'billingtype' => 0, 'startday' => 1, 'freetimetocall' => 0
));
$engine = new RateEngine();
$rate = array_fill(0, 76, 0);
$rate[6] = 7;
$rate[12] = 1;
$rate[38] = 42;
$engine->ratecard_obj = array($rate);

compatibilityAssertSame(5400, $engine->rate_engine_calcultimeout($A2B, 10), 'Unlimited package timeout');
compatibilityAssertSame(true, $engine->freecall[0], 'Unlimited package selection');
compatibilityAssertSame(array('id' => 42, 'label' => 'Unlimited calls', 'type' => 0),
    $engine->package_to_apply[0], 'Selected package');
compatibilityAssertSame(true, in_array('[ID PACKAGE  TO APPLY=42 - packagetype=0]', $A2B->messages, true),
    'Package debug message');

$A2B->DBHandle->rows = array();
compatibilityAssertSame(600, $engine->rate_engine_calcultimeout($A2B, 10), 'Timeout without a matching package');
compatibilityAssertSame(false, $engine->freecall[0], 'No matching free package');
compatibilityAssertSame(null, $engine->package_to_apply[0], 'No selected package');
$engine->ratecard_obj[0][38] = -1;
$queryCount = count($A2B->DBHandle->queries);
compatibilityAssertSame(600, $engine->rate_engine_calcultimeout($A2B, 10), 'Ordinary paid timeout');
compatibilityAssertSame($queryCount, count($A2B->DBHandle->queries), 'No package query for an ordinary rate');

restore_error_handler();
echo 'PHP ' . PHP_VERSION . ": compatibility regression checks passed\n";
