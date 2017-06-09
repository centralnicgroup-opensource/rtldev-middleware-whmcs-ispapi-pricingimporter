<link rel="stylesheet" href="../modules/addons/ispapidpi/css/styles.css">
<!-- <script src="../modules/addons/ispapidpi/js/checkbox.js"></script> -->
<div class=steps data-steps=3>
  <label>
    <span>
      <div>
        <form method=POST>
          <input style="border:none;" type="submit" name="submit" value="STEP 1 - LOAD PRICES"/>
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
          <input style="border:none;" type="submit" name="submit" value="STEP 2 - UPDATE PRICES"/>
        </form>
      </div>
    </span>
    <i></i>
  </label><!--
  --><label class=labelClass>
    <span>STEP 3 - IMPORT PRICES</span>
    <i></i>
  </label>
</div>
<br>
<form action=addonmodules.php?module=ispapidpi method=POST>
  <label>Bulk Price update</label><br>
    <span>Using Factor: </span><input type=number step=0.01 id="postMultiplier" name=multiplier min=0 value={$smarty.post.multiplier}>
      <input type=submit name=update class="btn btn-primary" value=Multiply><br><br>

    <form action=addonmodules.php?module=ispapidpi method=POST>
  <div>
  <span>Fixed Amount: </span><input type=number step=0.01 id=FixedAmount name=add-fixed-amount min=0 value=1.00>
    <input id=addition type=button name=add_fixed_amount class="btn btn-primary" value=Add  oonclick="add_number()">
  </div>
    </form>
    <!-- </form> -->
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
      <td><input type=text name=PRICE_{$key}_{$key2} id=PRICE_{$key}_{$key2} value={($old_and_new_price*$smarty.post.multiplier)|string_format:"%.2f"}></input></td>
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
<input type="submit" name="import" class="btn btn-primary" value="Import"/>
</div>
{if isset($smarty.post.import)}
  <div class='infobox'><strong><span class='title'>Update successful!</span></strong><br>Your pricing list has been updated successfully.</div>
{/if}
</form>
</form>

<script>
$("#addition").click(function () {
    var fixedAmount = parseFloat($("#FixedAmount").val());
    var  sum = 0;
    $('[id^="PRICE_"]').each(function(){
        sum = (parseFloat($(this).val())+fixedAmount).toFixed(2);
        // console.log(sum);
        $(this).val(sum);
    })
})
</script>
