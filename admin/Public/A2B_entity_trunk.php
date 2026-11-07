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
include ("./form_data/FG_var_trunk.inc");
include ("../lib/admin.smarty.php");

if (!has_rights(ACX_TRUNK)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

$HD_Form->setDBHandler(DbConnect());
$HD_Form->init();

if (!isset ($form_action))
	$form_action = "list"; //ask-add
if (!isset ($action))
	$action = $form_action;

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

$list = $HD_Form->perform_action($form_action);

// #### HEADER SECTION
    $smarty->display('main.tpl');

// #### HELP SECTION
    if ($form_action == 'list') {
	if (!$popup_select)	echo $CC_help_trunk_list;
    } else if ($popup_select!=4) {
	echo $CC_help_trunk_edit;
    }

//echo $CALL_LABS;

//  #### SEARCH SECTION
    if ($form_action == "list") {
	$HD_Form->create_search_form();
    }

// #### TOP SECTION PAGE
    $HD_Form->create_toppage($form_action);

    $HD_Form->create_form($form_action, $list, $id = null);

    if ($popup_select == 4 && in_array($form_action, array('ask-edit', 'edit', 'add-content', 'del-content', 'update-content'))) {
        $trunk_random_fields = array();
        foreach ($HD_Form->FG_TABLE_EDITION as $index => $field) {
            if (in_array($field[1], array('cc_trunk_rand_a', 'cc_trunk_rand_b'))) {
                $split = explode(':', $field[14]);
                $trunk_random_fields[$index] = $split[1];
            }
        }
?>
<script type="text/javascript">
(function () {
    var form = document.forms.myForm;
    if (!form) { return; }
    var fields = <?php echo json_encode($trunk_random_fields); ?>;
    var pending = false;
    var originalSendto = window.sendto;
    var originalUpdatecontent = window.updatecontent;
    var errorText = <?php echo json_encode(gettext('Unable to update random trunks. Check the result before trying again.')); ?>;

    // Each random-trunk field occupies a list row and the following add row.
    function blockRows(root, field) {
        var select = root.querySelector('select[name="' + field + '[]"]');
        var row = select && Array.from(select.closest('table.editform_table1')?.rows || []).find(row => row.contains(select));
        var previous = row && row.previousElementSibling;
        return previous && previous.querySelector('table.editform_table2') ? [previous, row] : null;
    }

    function refreshRandom(action, record, fieldInst, instance, percentage) {
        if (pending) { return false; }
        var field = fields[record];
        var current = blockRows(form, field);
        if (!current) { return false; }
        var data = new FormData(form);
        data.set('form_action', action);
        data.set('sub_action', record);
        if (fieldInst) { data.set(fieldInst, instance); }
        if (action === 'update-content') { data.set('percentage', percentage); }
        var selected = data.getAll(field + '[]');
        if (action === 'add-content' && !selected.length) { return false; }
        var error = current[1].querySelector('[data-trunk-random-error]');
        if (error) { error.remove(); }
        pending = true;
        var controls = current.flatMap(row => Array.from(row.querySelectorAll('input,select,button'), control => [control, control.disabled]));
        current.forEach(row => row.setAttribute('aria-busy', 'true'));
        controls.forEach(control => { control[0].disabled = true; });

        fetch(form.action, { method: 'POST', body: data, credentials: 'same-origin' })
            .then(async function (response) {
                if (!response.ok || response.redirected) { throw new Error(errorText); }
                var html = await response.text();
                if (document.forms.myForm !== form || !form.isConnected) { return; }
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var nextForm = doc.forms.myForm;
                var next = nextForm && blockRows(nextForm, field);
                if (!next || nextForm.elements.id.value !== form.elements.id.value) {
                    throw new Error(errorText);
                }
                // The content handlers do not return a status: verify the result in the rendered list.
                var valueInput = doc.getElementById(field + instance);
                if ((action === 'add-content' && !selected.every(function (id) { return next[0].querySelector('[id="' + field + id + '"]'); })) ||
                    (action === 'del-content' && valueInput) ||
                    (action === 'update-content' && (!valueInput || valueInput.value === '' || Number(valueInput.value) !== Number(percentage)))) {
                    throw new Error(errorText);
                }
                current.forEach(function (row, index) { row.replaceWith(next[index]); });
            })
            .catch(function () {
                if (document.forms.myForm !== form || !form.isConnected) { return; }
                var message = document.createElement('div');
                message.setAttribute('data-trunk-random-error', '');
                message.setAttribute('role', 'alert');
                message.textContent = errorText;
                current[1].cells[1].appendChild(message);
            })
            .finally(function () {
                controls.forEach(function (control) { control[0].disabled = control[1]; });
                current.forEach(function (row) { row.removeAttribute('aria-busy'); });
                pending = false;
            });
        return false;
    }

    window.sendto = function (action, record, fieldInst, instance) {
        if (fields[record] && (action === 'add-content' || action === 'del-content')) {
            return refreshRandom(action, record, fieldInst, instance);
        }
        return originalSendto.apply(this, arguments);
    };
    window.updatecontent = function (id, record, fieldInst, instance) {
        if (fields[record]) {
            return refreshRandom('update-content', record, fieldInst, instance, document.getElementById(id).value);
        }
        return originalUpdatecontent.apply(this, arguments);
    };
    // Cancel image-button submission and anchor navigation; the inline handlers still run.
    form.addEventListener('click', function (event) {
        if (!event.target.closest('a,input[type="image"]')) { return; }
        if (Object.values(fields).some(field => blockRows(form, field)?.some(row => row.contains(event.target)))) {
            event.preventDefault();
        }
    }, true);
    form.addEventListener('submit', function (event) {
        if (pending) { event.preventDefault(); }
    });
})();
</script>
<?php
    }

// #### FOOTER SECTION
    if (!$popup_select) {
	$smarty->display('footer.tpl');
    }
