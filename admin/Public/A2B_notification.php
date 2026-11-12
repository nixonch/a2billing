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
include ("../lib/admin.smarty.php");

if (!$ACXACCESS) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

getpost_ifset(array (
	'id',
	'page',
	'action',
	'ids',
	'form_action',
	'popup_select'
));

if ($action=="viewall"&& !empty($ids)) {
    $DBHandle = DbConnect();
    $table = new Table("cc_notification_admin", "*");
    $fields = "id_notification, id_admin, viewed";
    $ids = stripslashes($ids);
    $ids_array = json_decode($ids);
    foreach ($ids_array as $id){
	$values = " $id , " . $_SESSION['admin_id'] . ",1 ";
	$return = $table->Add_table($DBHandle, $values, $fields);
    }
     echo "true";
    
    die();
}

if (!empty ($action) && is_numeric($id)) {
	switch ($action) {
		case "view" :
			$DBHandle = DbConnect();
			$table = new Table("cc_notification_admin", "*");
			$fields = "id_notification, id_admin, viewed";
			$values = " $id , " . $_SESSION['admin_id'] . ",1 ";
			$return = $table->Add_table($DBHandle, $values, $fields);
			if ($return)
				echo "true";
			else
				echo "false";
			die();
			break;
		default :
			die();
			break;
	}

	$DBHandle = DbConnect();
	$table = new Table("cc_notification_admin", "*");
	$fields = "id_notification, id_admin, viewed";
	$values = " $id , " . $_SESSION['admin_id'] . ",1 ";
	$return = $table->Add_table($DBHandle, $values, $fields);
	if ($return)
		echo "true";
	else
		echo "false";
	die();
}

$HD_Form = new FormHandler("cc_notification", gettext("Notification"));
$HD_Form->FG_DELETION = has_rights(ACX_DELETE_NOTIFICATIONS);
$HD_Form->FG_ENABLE_LOG = 0;
$HD_Form->AddEditElement(gettext("DATE"), "date", "", "INPUT", "size=25", null, null, null, null, null, null, null, null);
$HD_Form->FieldEditElement("date");
$HD_Form->FG_INTRO_TEXT_ASK_DELETION = gettext("Do you want delete this notification ?");
$HD_Form->FG_FK_DELETE_ALLOWED = true;
$HD_Form->FG_FK_TABLENAMES = array("cc_notification_admin");
$HD_Form->FG_FK_EDITION_CLAUSE = array("id_notification");
$HD_Form->setDBHandler(DbConnect());
$HD_Form->init();

if (in_array($form_action, array('ask-delete', 'ask-del-confirm', 'delete'), true)) {
	if (!is_numeric($id)) die(gettext("error deletion"));
	$HD_Form->FG_EDITION_CLAUSE = str_replace('%id', $id, $HD_Form->FG_EDITION_CLAUSE);
	$list = $HD_Form->perform_action($form_action);
	$smarty->display('main.tpl');
	$HD_Form->create_form($form_action, $list);
	exit;
}

if(empty($page))$page=1;

$DBHandle = DbConnect();


// #### HEADER SECTION
if ($form_action == "list" || $popup_select < 2)
    $smarty->display('main.tpl');

echo $CC_help_notifications;
$nb_by_page = 15;
$nb_total = NotificationsDAO::getNbNotifications();
$nb_page = ceil($nb_total/$nb_by_page);
$page = max(1, min((int)$page, max(1, $nb_page)));
$list_notifications = NotificationsDAO::getNotifications($_SESSION['admin_id'],(($page-1)*$nb_by_page),$nb_by_page);

?>

<style type="text/css">
.newrecord {
	font-weight : bold;
	color : #444444;
	cursor : pointer;
}
</style>

<div id="main-list">
<?php
$js_id_array = array();
if((is_array($list_notifications) ? sizeof($list_notifications) : 0)>0 && $list_notifications[0]!=null) {  ?>
<table width="90%" style ="margin-left:auto;margin-right:auto;" cellspacing="2" cellpadding="2" border="0">
	<tr>
		<td colspan="6" align="center">
		    <a id ="viewall" href="javascript:;" ><?php echo gettext("View All") ?></a>
		</td>
	</tr>
	<?php if($nb_page>1){ ?>
	<tr>
		<td colspan="3" align="left">
		<?php if($page>1){ ?>
			<a href="A2B_notification.php?page=<?php echo $page-1; ?>"> &lt; <?php echo gettext("Newer") ?> </a>
		<?php } ?>
		&nbsp;
		</td>
		<td colspan="3" align="right">
		&nbsp;
                <?php if($page<$nb_page){ ?>
		<a href="A2B_notification.php?page=<?php echo $page+1; ?>"><?php echo gettext("Older") ?> &gt;</a>
                <?php } ?>
		</td>
	</tr>
	<?php } ?>
	<tr class="form_head">
		<td class="tableBody"  width="15%" align="center" style="padding: 2px;">
		<?php echo gettext("DATE"); ?>
		</td>
		<td class="tableBody"  width="30%" align="center" style="padding: 2px;">
		<?php echo gettext("FROM"); ?>
		</td>
		<td class="tableBody"  width="40%" align="center" style="padding: 2px;">
		<?php echo gettext("SUBJECT"); ?>
		</td>
		<td class="tableBody"  width="7%" align="center" style="padding: 2px;">
		<?php echo gettext("PRIORITY"); ?>
		</td>
		<td class="tableBody"  width="7%" align="center" style="padding: 2px;">
		&nbsp;
		</td>
		
	</tr>
 
	<?php 
		$i=0;
		foreach ($list_notifications as $notification) {
			if($notification->getNew()) $js_id_array[] = (int) $notification->getId();

			switch ($notification->getPriority()) {
				case 2: if($notification->getNew()) $bg="#F98886";
						else $bg="#F1ACAC";
						break;
				case 1: if($notification->getNew()) $bg="#6AE331";
						else $bg="#A2F580";
						break;
				case 0: 
				default:if($i%2==0) $bg="#fcfbfb";
						else  $bg="#f2f2ee";
						break;
			}
	?>
			<tr id="<?php echo $notification->getId(); ?>" bgcolor="<?php echo $bg; ?>" <?php if($notification->getNew()){ ?> class="newrecord" <?php } ?> >
			
				<td class="tableBody" align="center">
				  <?php echo $notification->getDate(); ?>
				</td>
				<td class="tableBody"  align="center">
				  <?php echo $notification->getFromDisplay(); ?>
				</td>
				<td class="tableBody"  align="center">
				  <?php echo $notification->getKeyMsg(); 
				  $url = $notification->getUrl()?>
				  <?php if(!empty($url)){?>
				            &nbsp; <a href="<?php echo $url;?>"> <img src="<?php echo Images_Path."/link.png"?>" style="vertical-align:bottom;" border="0" /></a>
				  <?php }?>
				</td>
				<td class="tableBody"  align="center">
				  <?php echo $notification->getPriorityMsg(); ?>
				</td>
				<td class="tableBody"  align="center">
				<?php if($notification->getNew()){ ?>
					<strong style="font-size:8px; color:#B00000; background-color:white; border:solid 1px;"> &nbsp;NEW&nbsp;</strong>
				<?php }elseif(has_rights (ACX_DELETE_NOTIFICATIONS)){ ?>
					<img data-id="<?php echo $notification->getId(); ?>" onmouseover="this.style.cursor='pointer'" class="delete" src="<?php echo Images_Path ?>/delete.png" title="<?php echo gettext("Delete this Notification")?>" alt="<?php echo gettext("Delete this Notification")?>" border="0"/>
				<?php } ?>
				</td>
			</tr>
		<?php 
		$i++;	
		}
		?>
		<?php if($nb_page>1){ ?>
		<tr>
			<td colspan="3" align="left">
			<?php if($page>1){ ?>
				<a href="A2B_notification.php?page=<?php echo $page-1; ?>"> &lt; <?php echo gettext("Newer") ?> </a>
			<?php } ?>
			&nbsp;
			</td>
			<td colspan="3" align="right">
			&nbsp;
                         <?php if($page<$nb_page){ ?>
			<a href="A2B_notification.php?page=<?php echo $page+1; ?>"><?php echo gettext("Older") ?> &gt;</a>
                        <?php } ?>
			</td>
		</tr>
		<?php } ?>
	
</table>
<?php 
}else{ ?>

<br/>
<div style="width : 95%; text-align : center;height:200px; margin-left:auto;margin-right:auto;" >
	<br/>
	<br/>
	<strong><?php echo gettext("No Notifications") ?></strong>
</div>
<br/>

<?php
}

?>
<input type="hidden" id="notification-page" value="<?php echo $page; ?>">
<input type="hidden" id="notification-ids" value="<?php echo htmlspecialchars(json_encode($js_id_array), ENT_QUOTES, 'UTF-8'); ?>">
</div>
<?php

$smarty->display( 'footer.tpl');

?>
<script type="text/javascript">
$(document).ready(function () {

	$(document).on('click', '.newrecord', function () {
		$.get("A2B_notification.php", { id: ""+ this.id, action: "view" },
			  function(data){
			    if(data=="true") location.reload(true);
			  });			
        });

	$(document).on('click', '#viewall', function () {
		$.get("A2B_notification.php", { page: $('#notification-page').val(), action: "viewall", ids: $('#notification-ids').val() },
			  function(data){
			    if(data=="true") location.reload(true);
			  });
        });

	$(document).on('click', '.delete', function (event) {
		alertBase(<?php echo json_encode($HD_Form->get_deletion_link(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> + this.getAttribute('data-id'),
			<?php echo json_encode(gettext("Delete this Notification"), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
			true, false, '#c33d3c', event.pageX-55, event.pageY-100, 'UR', null, null, null, null, null,
			function (payload) {
				if (payload.type !== 'refreshInside') return false;
				return refreshInsideCallback({type: 'refreshInside', link: <?php echo json_encode($_SERVER['PHP_SELF'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
					listParameters: {page: $('#notification-page').val()}});
			});
	});
	$('.view_comment_icon').click(function () {
		  $("#"+this.id+":checkbox").attr("checked", true);
		  $("#action").val('view_comment')
		  $("#idc").val(this.id);
		  $('form').submit();
        });
	$('.view_ticket').click(function () {
		$("#action").val('view_ticket')
		$('form').submit();
        });
	$('.view_ticket_icon').click(function () {
		  $('.view_ticket').attr("checked", true);
		  $("#action").val('view_ticket')
		  $('form').submit();
        });
});
</script>
