<link rel="stylesheet" href="../modules/addons/ispapidpi/css/styles.css">
<!--  comment lines at label tags should not be removed in order to keep the design appropriate -->
{if isset($smarty.post.import)}
  <div class="infobox"><strong><span class="title">Update successful!</span></strong><br>Your pricing list has been updated successfully.</div><br>
{/if}

<div class="steps" data-steps="3">
  <label><span><div><form method="POST"><input style="border:none;" type="submit" name="submit" value="STEP 1 - LOAD PRICES"/></form></div></span><i></i></label><!--
  --><label><span><div><form method="POST"><input type="hidden" name="price_class" value="{$smarty.session.price_class}"/><input style="border:none;" type="submit" name="submit" value="STEP 2 - UPDATE PRICES"/></form></div></span><i></i></label><!--
  --><label class=labelClass><span>STEP 3 - IMPORT PRICES</span><i></i></label>
</div>
<br>
<form action="addonmodules.php?module=ispapidpi" method="POST">
    <h2>Bulk Price update</h2>
    <span style="font-weight:bold;">Using Factor: </span><input type="number" placeholder="1.00" step="0.01" id="postMultiplier" value="{$smarty.post.multiplier|string_format:"%.2f"}" name="multiplier" min="0.00"/>
    <input type="submit" name="update" class="btn btn-primary" value="Multiply"/>

    <span style="margin-left:40px;font-weight:bold;">Fixed Amount: </span><input type="number" step="0.01" id="FixedAmount" name="addfixedamount" min="0" value="{$smarty.post.addfixedamount}"/>
    <input id="addition" type="button" name="add_fixed_amount" class="btn btn-primary" value="Add"/>

    <br><br>
    <b style="color:#a94442;background:#f2dede;padding:5px;">We would like to make following note regarding the cost currency: Please be aware that we are not charging all TLDs in USD.</b>
    <br><br>

    <table>
        <tr>
            <th>TLD</th>
            <th colspan="2">Register</th>
            <th colspan="2">Renew</th>
            <th colspan="2">Transfer</th>
            <th colspan="2">Currency</th>
        </tr>
        <tr>
            <th></th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
        </tr>

        {counter start=-1 skip=1 print=FALSE}
        {foreach $smarty.session.checked_tld_data as $key=>$value}
        <tr id="row">
            <td valign="top">
                {if preg_match("/^xn--/", $key)}.{$idnmap[$key]}<br/><small>(.{$key})</small>{else}.{$key}{/if}
            </td>
            {foreach $value as $key2=>$old_and_new_price}
                {if $key2|upper != "CURRENCY"}
                    <td name="Myprices" valign="top">{$old_and_new_price}</td>
                    <td valign="top"><input class="sale1" type="text" name="PRICE_{$key|upper}_{$key2|upper}" id="PRICE_{$key|upper}_{$key2|upper}" value="{($old_and_new_price*$smarty.post.multiplier+$smarty.post.addfixedamount)|string_format:"%.2f"}"/></td>
                {/if}
                {if $key2|upper == "CURRENCY"}
                    <td valign="top">{$old_and_new_price}</td>
                {/if}
            {/foreach}
            <td valign="top">
                {assign var="selectedvalue" value="{$smarty.post.currency[{counter}]}"}
                <select name="currency[]">
                    {foreach $currency_data as $id=>$code}
                        <option {if $selectedvalue==$id}selected="selected"{/if} value="{$id}">{$code}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        {/foreach}
    </table>

    <br>
    <div>
        <h2>Domain Addons</h2>
        <div class="[ form-group ]">
            <input type="checkbox" name="dns_management" value={$smarty.post.dns_management} checked="checked" id="fancy-checkbox-primary-custom-icons" autocomplete="off" />
            <div class="[ btn-group ]">
                <label for="fancy-checkbox-primary-custom-icons" class="[ btn btn-primary ]">
                    <span class="[ glyphicon glyphicon-plus ]"></span>
                    <span class="[ glyphicon glyphicon-minus ]"></span>
                </label>
                <label for="fancy-checkbox-primary-custom-icons" class="[ btn btn-default ]">
                    DNS Management
                </label>
            </div>
            <label> </label>
            <input type="checkbox" name="email_forwarding" value="{$smarty.post.email_forwarding}" checked="checked" id="fancy-checkbox-primary-custom-icons1" autocomplete="off" />
            <div class="[ btn-group ]">
                <label for="fancy-checkbox-primary-custom-icons1" class="[ btn btn-primary ]">
                    <span class="[ glyphicon glyphicon-plus ]"></span>
                    <span class="[ glyphicon glyphicon-minus ]"></span>
                </label>
                <label for="fancy-checkbox-primary-custom-icons1" class="[ btn btn-default ]">
                    Email Forwarding
                </label>
            </div>
            <label> </label>
            <input type="checkbox" name="id_protection" value={$smarty.post.id_protection} checked="checked" id="fancy-checkbox-primary-custom-icons2" autocomplete="off" />
            <div class="[ btn-group ]">
                <label for="fancy-checkbox-primary-custom-icons2" class="[ btn btn-primary ]">
                    <span class="[ glyphicon glyphicon-plus ]"></span>
                    <span class="[ glyphicon glyphicon-minus ]"></span>
                </label>
                <label for="fancy-checkbox-primary-custom-icons2" class="[ btn btn-default ]">
                    ID Protection
                </label>
            </div>
            <label> </label>
            <input type="checkbox" name="epp_code" value={$smarty.post.epp_code} checked="checked" id="fancy-checkbox-primary-custom-icons3" autocomplete="off" />
            <div class="[ btn-group ]">
                <label for="fancy-checkbox-primary-custom-icons3" class="[ btn btn-primary ]">
                    <span class="[ glyphicon glyphicon-plus ]"></span>
                    <span class="[ glyphicon glyphicon-minus ]"></span>
                </label>
                <label for="fancy-checkbox-primary-custom-icons3" class="[ btn btn-default ]">
                    EPP Code
                </label>
            </div>
        </div>
        <!--
        <input type="checkbox" name="dns_management" value="on">DNS Management</input>
        <input type="checkbox" name="email_forwarding" value="on">Email Forwarding</input>
        <input type="checkbox" name="id_protection" value="on">ID Protection</input>
        <input type="checkbox" name="epp_code" value={$smarty.post.epp_code} checked='checked'>EPP Code</input>
        -->
        <br><br>
        <input type="submit" name="import" class="btn btn-primary" value="Import"/>
    </div>
</form>

<script>
$("#addition").click(function () {
    var fixedAmount = parseFloat($("#FixedAmount").val());
    var  sum = 0;
    $('[id^="PRICE_"]').each(function(){
        sum = (parseFloat($(this).val())+fixedAmount).toFixed(2);
        $(this).val(sum);
    })
})
</script>
