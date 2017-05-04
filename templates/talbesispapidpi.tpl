<table class="tableClass">
  <tr>
    <th><span><input type=checkbox onchange=checkAll(this) class=checkall /></span></th>
    <th>TLD</th>
    <th>Register</th>
    <th>Renew</th>
    <th>Transfer</th>
    <th>Currency</th>
  </tr>
  {foreach $tld_register_renew_transfer_currency_filter as $tld => $value}
        <tr>
            <td><input type=checkbox class=tocheck  name=checkbox-tld[] value={$tld}></input></td>
            <td>{$tld}</input></td>
            {foreach $value as $key}
                <td name=Myprices>{$key}</td>
            {/foreach}
        </tr>
   {/foreach}
</table>
<br>
<input type="submit" name="check-button" value="Next">
