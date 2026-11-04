<form method="post" action="{$customer_search_view.action|escape:'html'}">
    {foreach $customer_search_view.hidden as $name => $value}
    <input type="hidden" name="{$name|escape:'html'}" value="{$value|escape:'html'}">
    {/foreach}
    <table class="searchhandler_table1" width="{$customer_search_view.width|escape:'html'}" align="center">
        <tr><td colspan="2">{$customer_search_view.caption|escape:'html'}</td></tr>
        {foreach $customer_search_view.dates as $row}
        <tr><td class="bgcolor_002">{$row.label|escape:'html'}</td><td class="bgcolor_003">
            {foreach $row.bounds as $bound}
            <label><input type="checkbox" name="{$bound.checkbox|escape:'html'}" value="true" {if $customer_search_view.state[$bound.checkbox]}checked{/if}> {$bound.label|escape:'html'}</label>
            <select name="{$bound.day_name|escape:'html'}" class="form_input_select">
                {foreach $bound.days as $day}
                <option value="{$day}" {if $customer_search_view.state[$bound.day_name] == $day}selected{/if}>{$day}</option>
                {/foreach}
            </select>
            <select name="{$bound.month_name|escape:'html'}" class="form_input_select">
                {foreach $bound.months as $value => $label}
                <option value="{$value|escape:'html'}" {if $customer_search_view.state[$bound.month_name] == $value}selected{/if}>{$label|escape:'html'}</option>
                {/foreach}
            </select> &nbsp;
            {/foreach}
        </td></tr>
        {/foreach}
        <tr><td class="bgcolor_004"><label><input type="checkbox" name="use_age" value="true" {if $customer_search_view.state.use_age}checked{/if}> {gettext("Select customer created more than")}</label></td>
            <td class="bgcolor_005"><select name="month_earlier" class="form_input_select">
                {foreach $customer_search_view.age_months as $month}
                <option value="{$month}" {if $customer_search_view.state.month_earlier == $month}selected{/if}>{$month} {gettext("Months")}</option>
                {/foreach}
            </select></td></tr>
        {foreach $customer_search_view.text_fields as $field}
        <tr><td class="bgcolor_002">{$field.label|escape:'html'}</td><td class="bgcolor_003">
            <input type="text" name="{$field.name|escape:'html'}" value="{$customer_search_view.state[$field.name]|escape:'html'}" class="form_input_text">
            {foreach $customer_search_view.text_types as $type => $label}
            <label><input type="radio" name="{$field.type_name|escape:'html'}" value="{$type}" {if $customer_search_view.state[$field.type_name] == $type}checked{/if}> {$label|escape:'html'}</label>
            {/foreach}
        </td></tr>
        {/foreach}
        {foreach $customer_search_view.range_fields as $field}
        <tr><td class="bgcolor_004">{$field.label|escape:'html'}</td><td class="bgcolor_005">
            {foreach $field.bounds as $bound}
            <span style="white-space:nowrap"><input type="text" name="{$bound.name|escape:'html'}" value="{$customer_search_view.state[$bound.name]|escape:'html'}" size="10" class="form_input_text">
                {foreach $customer_search_view.numeric_types as $type => $label}
                <label><input type="radio" name="{$bound.type_name|escape:'html'}" value="{$type}" {if $customer_search_view.state[$bound.type_name] == $type}checked{/if}> {$label|escape:'html'}</label>
                {/foreach}
            </span> &nbsp;
            {/foreach}
        </td></tr>
        {/foreach}
        <tr><td class="bgcolor_002"></td><td class="bgcolor_003">
            {foreach $customer_search_view.select_fields as $field}
            <select name="{$field.name|escape:'html'}" class="form_input_select"><option value="">{$field.label|escape:'html'}</option>
                {foreach $field.options as $option}
                <option value="{$option[0]|escape:'html'}" {if $customer_search_view.state[$field.name] == $option[0] && $customer_search_view.state[$field.name] !== ''}selected{/if}>{$option[1]|escape:'html'}</option>
                {/foreach}
            </select>
            {/foreach}
        </td></tr>
        <tr><td class="bgcolor_004"></td><td class="bgcolor_005">
            <input type="image" src="{$customer_search_view.search_image|escape:'html'}" alt="{gettext("Search")}">
            {if $customer_search_view.active}<button type="submit" name="cancelsearch" value="true" class="form_input_button">{gettext("Cancel Search")}</button>{/if}
        </td></tr>
    </table>
</form>
