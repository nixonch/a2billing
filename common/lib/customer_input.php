<?php

/** Validation shared by customer forms, JSON editors and imports. No SQL/HTML encoding here. */
class CustomerInput
{
    public static function fail($field)
    {
        header('HTTP/1.0 400 Bad Request');
        exit(htmlspecialchars(gettext('Invalid value') . ': ' . $field, ENT_QUOTES, 'UTF-8'));
    }

    public static function valid($field, $value)
    {
        $field = preg_replace('/\[\]$/', '', $field);
        if (!is_scalar($value) && $value !== null) return false;
        $value = (string)$value;
        if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/', $value)) return false;
        if ($value === '') return true;
        if ($field === 'tonum') return (bool)preg_match('/\A\+?[0-9*#]+\z/', $value);
        // History searches also accept SQL's percent wildcard as search data.
        if ($field === 'phonenumber') return self::valid('destination', str_replace('%', '', $value));

        // These values are reused as SQL lookup keys and Asterisk command arguments.
        if (in_array($field, array('destination', 'destinationnum', 'dest', 'cid',
                'add_callerid', 'called', 'calling', 'exten', 'exten_leg_a'))) {
            return (bool)preg_match('~\A[\pL\pN_ .+*#@:/&=()\-]+\z~u', $value);
        }
        if ($field === 'ivrname') return (bool)preg_match('/\A[\pL\pN_ .+()\-]+\z/u', $value);
        if ($field === 'waitdigits') {
            return (bool)preg_match('/\A(?:[0-9*#]+|-[1-5])\z/', $value) || in_array($value, array(
                gettext('Nothing pressed (repeat)'), gettext('Input wrong (repeat)'), gettext('Nothing pressed (finish)'),
                gettext('Input wrong (finish)'), gettext('Extension input allowed')), true);
        }
        if (in_array($field, array('playsound', 'playsoundcallee', 'calleesound', 'sound1'))) {
            return self::audioName($value, true);
        }
        if ($field === 'greet_filename') return self::audioName($value);
        if ($field === 'addprefixinternational') return (bool)preg_match('/\A[+0-9]*\z/', $value);
        if (in_array($field, array('language', 'langlocale', 'voicename', 'gender', 'currency', 'country'))) {
            return (bool)preg_match('/\A[a-zA-Z0-9_\-]+\z/', $value);
        }
        if ($field === 'speakingRate') return (bool)preg_match('/\A[0-9]+(?:\.[0-9]+)?\z/', $value);
        // Signup retains the GeoIP zone after the numeric timezone ID (e.g. 1;Europe/Berlin).
        if ($field === 'id_timezone') {
            if (!preg_match('~\A-?[0-9]+(?:;[a-zA-Z0-9_/+\-]*)?\z~', $value)) return false;
            $timezone = explode(';', $value, 2);
            return !isset($timezone[1]) || $timezone[1] === ''
                || in_array($timezone[1], DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true);
        }
        if (in_array($field, array('id', 'idcust', 'IDCust', 'id_cc_card', 'id_cc_did', 'card_id', 'diller_id',
                'repeats', 'waitsecsfordigits', 'timeout', 'timeout1', 'max_attempt', 'simult',
                'maxduration', 'inputa', 'inputb', 'inputc', 'speeddial', 'choose_speeddial'))) {
            return (bool)preg_match('/\A-?[0-9]+\z/', $value);
        }
        if ($field === 'weekdays') return (bool)preg_match('/\A[0-6](?:,?[0-6])*\z/', $value);
        if ($field === 'timefrom' || $field === 'timetill') {
            return ctype_digit($value) && (float)$value <= 86400;
        }
        if (in_array($field, array('name', 'secret', 'callerid', 'localstationid', 'localheaderinfo', 'email',
                'speech2mail', 'email_notification'))) return !preg_match('/[\r\n]/', $value);
        return true;
    }

    public static function check($field, $value)
    {
        if (!self::valid($field, $value)) self::fail($field);
        return $value;
    }

    public static function audioName($value, $path = false)
    {
        if (!is_string($value) || $value === '') return false;
        $pattern = $path ? '~\A[\pL\pN_. /()+\-]+\z~u' : '~\A[\pL\pN_. ()+\-]+\z~u';
        if (!preg_match($pattern, $value) || $value[0] === '/') return false;
        foreach (explode('/', $value) as $part) {
            if ($part === '' || $part === '.' || $part === '..') return false;
        }
        return true;
    }

    public static function values(array $values)
    {
        foreach ($values as $field => $value) {
            if (in_array($field, array('shedule', 'soundArray', 'destArray')) && !is_array($value)) self::fail($field);
            if (is_array($value)) {
                if (in_array($field, array('shedule', 'soundArray', 'destArray'))) {
                    foreach ($value as $row) {
                        if (!is_array($row)) self::fail($field);
                        $keys = $field === 'shedule'
                            ? array('weekdays[]', 'timefrom[]', 'timetill[]', 'inputa[]', 'inputb[]', 'inputc[]')
                            : ($field === 'soundArray' ? array('timeout', 'playsound') : array('waitdigits', 'destinationnum', 'playsoundcallee', 'soundArray'));
                        if (array_diff(array_keys($row), $keys)) self::fail($field);
                        $required = $field === 'shedule' ? array_slice($keys, 0, 3) : $keys;
                        if (array_diff($required, array_keys($row))) self::fail($field);
                        self::values($row);
                    }
                } elseif (in_array($field, array('weekdays', 'timefrom', 'timetill', 'inputa', 'inputb', 'inputc',
                        'dillertariffs', 'dillergroups'))) {
                    foreach ($value as $item) self::check(in_array($field, array('dillertariffs', 'dillergroups')) ? 'id' : $field, $item);
                } else {
                    self::fail($field);
                }
            } else {
                self::check($field, $value);
            }
        }
        return $values;
    }

    public static function json($json)
    {
        if (!is_string($json)) self::fail('json_data');
        // The existing jqDynaForm client sends an addslashes-encoded JSON document.
        $values = json_decode($json, true);
        if (!is_array($values)) $values = json_decode(stripslashes($json), true);
        if (!is_array($values)) self::fail('json_data');
        return $values;
    }

    public static function html($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function script($value)
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public static function jsString($value)
    {
        return substr(self::script(stripslashes((string)$value)), 1, -1);
    }

    /** Validate actual controls immediately before FormHandler writes anything. */
    public static function form($form, $adding)
    {
        $fields = $adding ? $form->FG_TABLE_ADITION : $form->FG_TABLE_EDITION;
        foreach ($fields as $field) {
            $name = $field[1];
            if (!array_key_exists($name, $form->_vars) || strpos($field[4], 'readonly') !== false) continue;
            $value = $form->_vars[$name];
            $type = strtoupper($field[3]);
            if (strpos($type, 'POPUPDAYTIME') === 0) {
                $keys = array('weekdays[]', 'timefrom[]', 'timetill[]');
                if (substr($type, -1) === '3') $keys = array_merge($keys, array('inputa[]', 'inputb[]', 'inputc[]'));
                if (!is_array($value)) self::fail($name);
                foreach ($value as $index => $row) {
                    // SQL placeholders in the legacy schedule writer are positional.
                    if (array_diff(array_keys($row), $keys) || array_diff($keys, array_keys($row))) self::fail($name);
                    $ordered = array();
                    foreach ($keys as $key) $ordered[$key] = $row[$key];
                    $form->_vars[$name][$index] = $ordered;
                }
                unset($form->_processed[$name]);
                continue;
            }
            $multiple = strpos($field[4], 'multiple') !== false;
            if (is_array($value) && !$multiple) self::fail($name);
            foreach ($multiple ? (array)$value : array($value) as $item) {
                self::check($name, $item);
                if ($type !== 'TEXTAREA' && preg_match('/[\r\n]/', (string)$item)) self::fail($name);
                if ($name === 'phone' && $form->FG_TABLE_NAME === 'cc_speeddial') self::check('destination', $item);
                $options = null;
                if ($type === 'SELECT' && strtoupper($field[7]) === 'LIST') {
                    $options = array();
                    foreach ((array)$field[11] as $option) $options[] = (string)$option[1];
                } elseif ($type === 'SELECT' && strtoupper($field[7]) === 'SQL') {
                    $table = new Table($field[8], $field[9]);
                    $rows = $table->Get_list($form->DBHandle, str_replace('%id', isset($form->_vars['id']) ? $form->_vars['id'] : '', $field[10]));
                    $options = array();
                    foreach ((array)$rows as $option) $options[] = (string)$option[1];
                } elseif ($type === 'RADIOBUTTON') {
                    $options = array();
                    foreach (explode(',', $field[10]) as $option) {
                        $parts = explode(':', $option);
                        $options[] = trim(end($parts));
                    }
                }
                if ($options !== null) {
                    if ($name === 'id_timezone') {
                        // A zone suffix does not change which cc_timezone row was selected.
                        $item = explode(';', (string)$item, 2)[0];
                        foreach ($options as $index => $option) $options[$index] = explode(';', $option, 2)[0];
                    }
                    // Forms also declare sentinel choices (e.g. ALWAYS=-1) as OPTION markup.
                    preg_match_all('/<option\b[^>]*\bvalue\s*=\s*["\']([^"\']*)["\']/i', (string)$field[15], $extra);
                    $options = array_merge($options, $extra[1]);
                    if ((string)$item !== '' && !in_array((string)$item, $options, true)) self::fail($name);
                }
            }
        }
    }
}
