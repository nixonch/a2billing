#!/usr/bin/php
<?php
// Preview: php DataBase/mysql-8.x/sync-currencies.php
// Apply:   php DataBase/mysql-8.x/sync-currencies.php --apply
// --config=/path/to/a2billing.conf selects a separate installation or test DB.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}
error_reporting(E_ALL);
$options = getopt('', array('apply', 'config:'));
$success = false;
register_shutdown_function(function () use (&$success) { if (!$success) exit(1); });
$lock = fopen('/var/run/a2billing/currencies_update.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Cannot lock currency update; another update may be running.\n");
    exit(1);
}
$library = dirname(dirname(__DIR__)) . '/common/lib/';
require_once $library . 'vendor/autoload.php';
require_once $library . 'interface/constants.php';
require_once $library . 'Class.Table.php';
require_once $library . 'Class.A2Billing.php';
require_once $library . 'Misc.php';
$catalog = require $library . 'currencies_iso4217.php';
$agi = null;
$A2B = new A2Billing();
$config_file = isset($options['config']) ? $options['config'] : DEFAULT_A2BILLING_CONFIG;
if (!is_readable($config_file)) {
    fwrite(STDERR, "Cannot read A2Billing configuration: $config_file\n");
    exit(1);
}
if (!$A2B->load_conf($agi, $config_file)) {
    fwrite(STDERR, "Cannot load A2Billing configuration.\n");
    exit(1);
}
if ($A2B->config['database']['dbtype'] !== 'mysql') {
    fwrite(STDERR, "This migration requires the MySQL installation.\n");
    exit(1);
}
$base = strtoupper($A2B->config['global']['base_currency']);
if (!isset($catalog[$base])) {
    fwrite(STDERR, "The configured base currency is outside the current ISO 4217 monetary list: $base\n");
    exit(1);
}
$values = currencies_fetch_values($base, array_keys($catalog), $error);
if ($values === false || count($values) !== count($catalog)) {
    fwrite(STDERR, $values === false ? $error . "\n" :
        'Missing exchange rates: ' . implode(', ', array_keys(array_diff_key($catalog, $values))) . "\n");
    exit(1);
}
if (!$A2B->DbConnect()) {
    fwrite(STDERR, "Cannot connect to the database.\n");
    exit(1);
}
$db = $A2B->DBHandle;
function currencyMigrationQuery($db, $sql, $params = array()) {
    $result = $db->Execute($sql, $params);
    if (!$result) throw new RuntimeException('Currency migration SQL failed: ' . $db->ErrorMsg());
    return $result;
}
function currencyMigrationReferences($db, $columns, $remove) {
    $used = array();
    if (count($remove) === 0) return $used;
    $placeholders = implode(',', array_fill(0, count($remove), '?'));
    foreach ($columns as $column) {
        $field = '`' . $column['COLUMN_NAME'] . '`';
        $references = currencyMigrationQuery($db, 'SELECT DISTINCT ' . $field . ' AS currency FROM `' .
            $column['TABLE_NAME'] . '` WHERE ' . $field . ' IN (' . $placeholders . ')', array_keys($remove))->GetRows();
        foreach ($references as $row) $used[$row['currency']] = true;
    }
    return $used;
}
$scale = $db->GetOne("SELECT NUMERIC_SCALE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() " .
    "AND TABLE_NAME='cc_currencies' AND COLUMN_NAME='value'");
if ($scale === false) throw new RuntimeException('Cannot inspect currency rate precision.');
$columns = $db->GetAll("SELECT c.TABLE_NAME, c.COLUMN_NAME FROM information_schema.COLUMNS c " .
    "JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME " .
    "WHERE c.TABLE_SCHEMA = DATABASE() AND t.TABLE_TYPE = 'BASE TABLE' AND c.TABLE_NAME <> 'cc_currencies' " .
    "AND c.COLUMN_NAME IN ('currency', 'mc_currency', 'basecurrency')");
if ($columns === false) throw new RuntimeException('Cannot inspect currency references.');
$locks = array('cc_currencies' => '`cc_currencies` WRITE');
foreach ($columns as $column) {
    // Identifiers come from the database schema, not command-line input.
    if (!preg_match('/^[a-zA-Z0-9_]+$/D', $column['TABLE_NAME'] . $column['COLUMN_NAME'])) {
        throw new RuntimeException('Unsupported currency reference identifier.');
    }
    $locks[$column['TABLE_NAME']] = '`' . $column['TABLE_NAME'] . '` READ';
}
// MyISAM has no transactions. Prevent new references between the usage check and deletion.
currencyMigrationQuery($db, 'LOCK TABLES ' . implode(', ', $locks));
try {
    $rows = currencyMigrationQuery($db, 'SELECT id, currency FROM cc_currencies ORDER BY id')->GetRows();
    $existing = array();
    foreach ($rows as $row) $existing[$row['currency']] = $row['id'];
    $remove = array_diff_key($existing, $catalog);
    $add = array_diff_key($catalog, $existing);
    $used = currencyMigrationReferences($db, $columns, $remove);
    if (count($used) > 0) {
        throw new RuntimeException('Obsolete currencies are still used; no changes applied: ' . implode(', ', array_keys($used)));
    }
    echo 'Add ' . count($add) . ': ' . implode(', ', array_keys($add)) . "\n";
    echo 'Remove ' . count($remove) . ': ' . implode(', ', array_keys($remove)) . "\n";
    if (isset($options['apply'])) {
        if ($scale < 10) {
            currencyMigrationQuery($db, 'ALTER TABLE cc_currencies MODIFY value DECIMAL(17,10) UNSIGNED NOT NULL DEFAULT 0');
            // ALTER can release locks; acquire them again and recheck usage before deletion.
            currencyMigrationQuery($db, 'LOCK TABLES ' . implode(', ', $locks));
            $used = currencyMigrationReferences($db, $columns, $remove);
            if (count($used) > 0) throw new RuntimeException('Currencies became used during the schema update: ' . implode(', ', array_keys($used)));
        }
        $tuples = $params = array();
        foreach ($catalog as $code => $name) {
            $tuples[] = '(?, ?, ?, ?, CURRENT_TIMESTAMP)';
            array_push($params, $code, $name, $values[$code], $base);
        }
        currencyMigrationQuery($db, 'INSERT INTO cc_currencies (currency, name, value, basecurrency, lastupdate) VALUES ' .
            implode(',', $tuples) . ' ON DUPLICATE KEY UPDATE name = VALUES(name), value = VALUES(value), ' .
            'basecurrency = VALUES(basecurrency), lastupdate = VALUES(lastupdate)', $params);
        if (count($remove) > 0) {
            $placeholders = implode(',', array_fill(0, count($remove), '?'));
            currencyMigrationQuery($db, 'DELETE FROM cc_currencies WHERE currency IN (' . $placeholders . ')', array_keys($remove));
        }
        $message = 'ISO 4217 currency catalog synchronized: ' . count($catalog) . ' currencies; base ' . $base . '.';
        write_log(LOGFILE_CRONT_CURRENCY_UPDATE, basename(__FILE__) . ' ' . $message);
        echo $message . "\n";
    } else {
        echo "Preview only. Use --apply to save the changes.\n";
    }
    $success = true;
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    currencyMigrationQuery($db, 'UNLOCK TABLES');
}
