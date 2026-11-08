<?php
// Shared presentation hooks for legacy Smarty 3 and namespaced Smarty 5.
function a2b_smarty_configure($smarty)
{
    $smarty->registerPlugin('function', 'a2b_frame_options', 'a2b_smarty_frame_options', false);
    $smarty->registerPlugin('function', 'a2b_edit_help', 'a2b_smarty_edit_help', false);
    $smarty->registerPlugin('function', 'a2b_popup_context', 'a2b_smarty_popup_context', false);
    $smarty->registerPlugin('function', 'a2b_profiler', 'a2b_smarty_profiler', false);
    $smarty->registerPlugin('function', 'a2b_countries', 'a2b_smarty_countries', false);
    $smarty->registerPlugin('function', 'a2b_payment_logos', 'a2b_smarty_payment_logos', false);
    foreach (array('is_file', 'gettext', 'basename', 'json_encode') as $modifier) {
        $smarty->registerPlugin('modifier', $modifier, $modifier);
    }
    return $smarty;
}

function a2b_smarty_create()
{
    if (class_exists('Smarty\\Smarty')) {
        $smarty = new \Smarty\Smarty();
    } else {
        require_once __DIR__.'/vendor/smarty/smarty/libs/Smarty.class.php';
        $smarty = new Smarty();
    }
    $smarty->setCompileId($smarty instanceof \Smarty\Smarty ? 'a2b-native-smarty5' : 'a2b-native');
    return a2b_smarty_configure($smarty);
}

function a2b_smarty_frame_options($params, $template)
{
    if (!headers_sent()) header('X-Frame-Options: SAMEORIGIN');
    return '';
}

function a2b_smarty_edit_help($params, $template)
{
    global $form_action, $popup_select;
    if ((string)$popup_select !== '4') return '';
    if (!in_array($form_action, array('ask-edit', 'edit', 'add-content', 'del-content', 'update-content'), true)) return '';
    if (class_exists('FormHandler', false)) {
        $form = FormHandler::GetInstance();
        if ($form && $form->is_popup_edit_saved()) return '';
    }
    return '<style type="text/css">.toggle_show2hide{display:none!important}.edit-help{display:block!important;margin:10px 0;text-align:center;font-size:14px;line-height:1.4}</style>';
}

function a2b_smarty_popup_context($params, $template)
{
    if (!class_exists('FormHandler', false)) return '';
    $form = FormHandler::GetInstance();
    if (!$form) return '';
    $processed = $form->getProcessed();
    $popup_add = $form->is_popup_add_request();
    if (!$form->FG_EDITION_POPUP && !$form->is_popup_delete_request() && !$popup_add) return '';
    if (!$popup_add && (!isset($processed['popup_select']) || (string)$processed['popup_select'] !== '4')) return '';

    // JSON forms may decode their popup mode after Smarty has been initialized.
    global $popup_select;
    $popup_select = (int)$processed['popup_select'];
    $template->assign('popupwindow', $popup_select);
    if (!$form->is_popup_edit_saved()) return '';

    // End the response before the entity can output menus, help or other forms.
    $smarty = method_exists($template, 'getSmarty') ? $template->getSmarty() : $template->smarty;
    a2b_smarty_popup_result($smarty);
}

function a2b_smarty_popup_result($smarty, $message_html = null, $refresh_payload = null)
{
    global $popup_select;
    $popup_select = 4;
    $smarty->assign('popupwindow', 4);
    echo $smarty->fetch('header.tpl');
    FormHandler::create_popup_result($message_html, true, $refresh_payload);
    echo "\n</div></div>\n</body>\n</html>";
    exit;
}

function a2b_smarty_profiler($params, $template)
{
    global $profiler, $G_instance_Query_trace;
    if (!empty($profiler->installed) && !empty($profiler->modedebug)) {
        ob_start();
        $profiler->display($G_instance_Query_trace);
        return ob_get_clean();
    }
    return '';
}

function a2b_smarty_countries($params, $template)
{
global $countrycode;
		$country = $countrycode;
		$DBHandle_max = DbConnect();
		$instance_table = new Table("cc_country");
		$QUERY = "SELECT countrycode, countryname, countryprefix FROM cc_country";
		$countrylist = $instance_table -> SQLExec ($DBHandle_max, $QUERY);
		if ($countrylist) {
		    foreach ($countrylist AS $value) {
			if ($value[0]==$countrycode) {
			    $country = $value[1].($value[2]!="0"?' +'.$value[2]:"");
			    break;
			}
		    }
		}
    global $timezone_list;
    $template->assign('a2b_country', $country);
    $template->assign('a2b_countrycode', $countrycode);
    $template->assign('a2b_countrylist', is_array($countrylist) ? $countrylist : array());
    $template->assign('a2b_timezone_list', is_array($timezone_list) ? $timezone_list : array());
    return '';
}

function a2b_smarty_payment_logos($params, $template)
{
    return !empty($params['sidebar']) ? a2b_smarty_sidebar_logos() : a2b_smarty_login_logos();
}

function a2b_smarty_login_logos()
{
$DBHandle = DbConnect();
    $instance_table = new Table();
    $QUERY = "SELECT configuration_key FROM cc_configuration where configuration_key in ('MODULE_PAYMENT_AUTHORIZENET_STATUS','MODULE_PAYMENT_PAYPAL_BASIC_STATUS','MODULE_PAYMENT_WORLDPAY_STATUS','MODULE_PAYMENT_PLUGNPAY_STATUS','MODULE_PAYMENT_WM_STATUS') AND configuration_value='True'";
    $payment_methods = $instance_table->SQLExec($DBHandle, $QUERY);
    $QUERY = "SELECT configuration_value FROM cc_configuration where configuration_key='MODULE_PAYMENT_WM_WMID'";
    $wmid = $instance_table->SQLExec($DBHandle, $QUERY);
    $show_logo = '';
    for ($index = 0; $index < count(is_array($payment_methods) ? $payment_methods : array()); $index++) {
	if ($payment_methods[$index][0] == "MODULE_PAYMENT_PAYPAL_BASIC_STATUS") {
	    $show_logo .= '<a href="https://www.paypal.com/en/mrb/pal=PGSJEXAEXKTBU" target="_blank"><img src="' . KICON_PATH . '/payments_paypal.gif" width="120" alt="Paypal"/></a>' . ' &nbsp; ';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_PLUGNPAY_STATUS") {
	    $show_logo .= '<a href="http://www.plugnpay.com/" target="_blank"><img src="' . KICON_PATH . '/plugnpay.png" width="110" alt="plugnpay.com"/></a>' . ' &nbsp; ';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_WM_STATUS") {
	    $show_logo .= '<a href="http://www.web.money/" target="_blank"><img src="' . KICON_PATH . '/webmoney_virified.png" width="88" alt="WebMoney"/></a>' . ' &nbsp; ' . '<a href="https://passport.web.money/asp/certview.asp?wmid='.$wmid[0][0].'" target="_blank"><img src="' . KICON_PATH . '/v_blue_on_transp_en.png" width="88" alt="Check the seller\'s WMID" border="0" /></a>';
	}
    }

    $show_logo .= ' <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noopener noreferrer"><img src="images/agplv3-88x31.png" width="88" height="31" alt="AGPL 3"/></a> &nbsp; ';
    $aiphone_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
    $aiphone_utm_source = rawurlencode(trim((string) parse_url('http://' . $aiphone_host, PHP_URL_HOST), '[]'));
    $show_logo .= '<a href="https://chromewebstore.google.com/detail/pofkcckikkdhiieipefhkaelgnbdkcib?utm_source=' . $aiphone_utm_source . '&amp;utm_medium=referral&amp;utm_campaign=a2billing_customer_login" target="_blank" rel="noopener noreferrer"><img src="images/aiphone-88x31.svg" width="88" height="31" alt="AiPhone"/></a>';
    return $show_logo;
}

function a2b_smarty_sidebar_logos()
{
$DBHandle = DbConnect();
    $instance_table = new Table();
    $QUERY = "SELECT configuration_key FROM cc_configuration where configuration_key in ('MODULE_PAYMENT_AUTHORIZENET_STATUS','MODULE_PAYMENT_PAYPAL_BASIC_STATUS','MODULE_PAYMENT_WORLDPAY_STATUS','MODULE_PAYMENT_PLUGNPAY_STATUS','MODULE_PAYMENT_WM_STATUS') AND configuration_value='True'";
    $payment_methods = $instance_table->SQLExec($DBHandle, $QUERY);
    $QUERY = "SELECT configuration_value FROM cc_configuration where configuration_key='MODULE_PAYMENT_WM_WMID'";
    $wmid = $instance_table->SQLExec($DBHandle, $QUERY);
    $show_logo = '';
    $aiphone_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
    $aiphone_utm_source = rawurlencode(trim((string) parse_url('http://' . $aiphone_host, PHP_URL_HOST), '[]'));
    $show_logo .= ' <a href="https://chromewebstore.google.com/detail/pofkcckikkdhiieipefhkaelgnbdkcib?utm_source=' . $aiphone_utm_source . '&amp;utm_medium=referral&amp;utm_campaign=a2billing_customer_slidebar" target="_blank" rel="noopener noreferrer"><img src="images/aiphone-88x31.svg" width="88" height="31" alt="AiPhone"/></a>&nbsp;';
    for ($index=0; $index<count(is_array($payment_methods) ? $payment_methods : array()); $index++) {
	if ($payment_methods[$index][0] == "MODULE_PAYMENT_PLUGNPAY_STATUS") {
	    $show_logo .= '<a href="http://www.plugnpay.com/" target="_blank"><img src="' . KICON_PATH . '/plugnpay.png" width="110" alt="plugnpay.com"/></a>';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_WM_STATUS") {
	    $show_logo .= '<a href="http://www.web.money/" target="_blank"><img src="' . KICON_PATH . '/webmoney_virified.png" width="88" alt="WebMoney"/></a>';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_PAYPAL_BASIC_STATUS") {
	    $show_logo .= '<a href="https://www.paypal.com/en/mrb/pal=PGSJEXAEXKTBU" target="_blank"><img src="' . KICON_PATH . '/payments_paypal.gif" width="120" alt="Paypal"/></a>';
	}
    }
    return $show_logo;
}
