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


include ("../lib/admin.defines.php");
include ("../lib/admin.module.access.php");
include ("../lib/Form/Class.FormHandler.inc.php");
include ("./form_data/FG_var_backup.inc");
include ("../lib/admin.smarty.php");

if (!has_rights(ACX_MAINTENANCE)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

$HD_Form->setDBHandler(DbConnect());
$HD_Form->init();

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

if (!isset ($form_action))
	$form_action = "list"; //ask-add
if (!isset ($action))
	$action = $form_action;

$backup_error = '';
if ($form_action == 'add') {
	check_demo_mode();
	$backup_file = stripslashes($path);
	$do_gzip = substr($backup_file, -3) == '.gz';
	if ($do_gzip) $backup_file = substr($backup_file, 0, -3);
	if (DB_TYPE != 'postgres') {
		$run_backup = MYSQLDUMP . ' -all --databases ' . escapeshellarg(DBNAME) . ' -u' . escapeshellarg(USER) . ' -p' . escapeshellarg(PASS) . ' > ' . escapeshellarg($backup_file);
	} else {
		putenv('PGPASSWORD=' . PASS);
		$run_backup = PG_DUMP . ' -c -d -U ' . escapeshellarg(USER) . ' -h ' . escapeshellarg(HOST) . ' -f ' . escapeshellarg($backup_file) . ' ' . escapeshellarg(DBNAME);
	}
	exec($run_backup, $output, $error);
	if ($error != 0) {
		$backup_error = gettext('An error occur when the system tried to backup of the database.');
	} elseif ($do_gzip) {
		exec(GZIP_EXE . ' ' . escapeshellarg($backup_file), $output, $error_zip);
		if ($error_zip != 0) $backup_error = gettext('An error occur when the system tried to compress the backup realized.');
	}
	if ($backup_error !== '') {
		$form_action = 'ask-add';
	} else {
		$HD_Form->FG_GO_LINK_AFTER_ACTION_ADD = 'A2B_entity_restore?id=';
	}
}

$list = $HD_Form->perform_action($form_action);
if ($form_action == 'add' && !$HD_Form->is_add_saved()) {
	$backup_error = gettext('The backup file was created, but its database record could not be saved.');
	$form_action = 'ask-add';
}

// #### HEADER SECTION
$smarty->display('main.tpl');

// #### HELP SECTION
if ($popup_select != 4) echo $CC_help_database_backup;
if ($backup_error !== '') echo '<div class="msg_error">' . $backup_error . '<br>' . gettext('Please check yours system settings for the backup section ') . '</div>';

// #### TOP SECTION PAGE
$HD_Form->create_toppage($form_action);

$HD_Form->create_form($form_action, $list, $id = null);

// #### FOOTER SECTION
$smarty->display('footer.tpl');

