<link rel="stylesheet" href="../modules/addons/ispapidpi/css/styles.css">
<!-- <script src="../modules/addons/ispapidpi/js/checkbox.js"></script> -->

<div class=steps data-steps=3>
  <label>
    <span>
      <div>
        <form method=POST>
          <input style="border:none;" type="submit" name="submit" value="STEP 1"/>
        </form>
      </div>
    </span>
    <i></i>
  </label><!--

  --><label>
    <span>
      <div>
        <form method=POST>
          <input type=hidden name=price_class value={$smarty.session.price_class} </input>
          <input style="border:none;" type="submit" name="submit" value="STEP 2"/>
        </form>
      </div>
    </span>
    <i></i>
  </label><!--
  --><label class=labelClass>
    <span>STEP 3</span>
    <i></i>
  </label>
</div>
<br>
<form action=addonmodules.php?module=ispapidpi method=POST>
  <label>Update your prices by using a factor:</label>
    <input type=number step=0.01 name=multiplier min=0 value=1.00>
      <input type=submit name=update value=Update>
  </form>
  <br>

  <form action=addonmodules.php?module=ispapidpi method=POST>
    <table>
        <tr>
          <th>TLD</th>
          <th colspan=2>Register</th>
          <th colspan=2>Renew</th>
          <th colspan=2>Transfer</th>
          <th colspan=2>Currency</th>
        </tr>
        <tr>
          <th></th>
          <th style=width:16%>Cost</th>
          <th style=width:16%>Sale</th>
          <th style=width:16%>Cost</th>
          <th style=width:16%>Sale</th>
          <th style=width:16%>Cost</th>
          <th style=width:16%>Sale</th>
          <th style=width:16%>Cost</th>
          <th style=width:16%>Sale</th>
        </tr>

{foreach $smarty.session.checked_tld_data as $key=>$value}
  <tr id="row">
    <td>.{$key}</td>
    {foreach $value as $key2=>$old_and_new_price}
      <td name=Myprices>{$old_and_new_price}</td>
      <td><input type=text name=PRICE_{$key}_{$key2} value={($old_and_new_price*$multiplier)|string_format:"%.2f"}></input></td>
    {/foreach}
    <td>USD</td>
    <td><select name=currency[]>
    {foreach $currency_data as $id=>$code}
      <option value={$id}>{$code}</option>
    {/foreach}
  </select></td>
  </tr>
{/foreach}

</table>
<br>
<div>
<input type="checkbox" name="dns_management" value="on">DNS Management</input>
<input type="checkbox" name="email_forwarding" value="on">Email Forwarding</input>
<input type="checkbox" name="id_protection" value="on">ID Protection</input>
<input type="checkbox" name="epp_code" value="on">EPP Code</input>
<br> <br>
<input type="submit" name="import" value="Import"/>
</div>
</form>
