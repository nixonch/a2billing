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


chdir(__DIR__);
require_once __DIR__ . '/../lib/admin.defines.php';
require_once __DIR__ . '/../lib/admin.module.access.php';
require_once __DIR__ . '/../lib/PhpSysInfo.php';

$resourceRequest = array_key_exists('sysinfo', $_GET);
if ($resourceRequest) header_register_callback('a2b_sysinfo_headers');
if ((isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '') !== 'ADMIN' ||
    !isset($_SESSION['pr_login'], $_SESSION['pr_password']) ||
    !has_rights(ACX_MAINTENANCE) || !has_rights(ACX_ADMINISTRATOR)) {
    if ($resourceRequest) {
        http_response_code(403);
    } else {
        header('HTTP/1.0 401 Unauthorized');
        header('Location: PP_error.php?c=accessdenied');
    }
    exit;
}
check_demo_mode_intro();
if ($resourceRequest) {
    a2b_sysinfo_serve(__DIR__ . '/../lib/vendor/phpsysinfo/phpsysinfo', $_GET['sysinfo']);
    exit;
}
require_once __DIR__ . '/../lib/admin.smarty.php';
$smarty->display('main.tpl');
?>
<iframe id="phpsysinfo" src="A2B_phpsysinfo?sysinfo=index%2Ephp" title="phpSysInfo" scrolling="no"
    style="display:block;width:100%;max-width:1140px;margin:0 auto;border:0;overflow:hidden;"></iframe>
<script src="javascript/phpsysinfo.js"></script>
<?php
$smarty->display('footer.tpl');

