{foreach $smarty.session.checked_tld_data as $key=>$value}
  <tr id="row">
    <td>.{$key}</td>
    {foreach $value as $key2=>$price}
      <td name=Myprices>{$price}</td>
      <td><input type=text name=PRICE_{$key}_{$key2} value={$price}></input></td>
    {/foreach}
    <td>USD</td>
    <td><select name=currency[]>
    {foreach $currency_data as $id=>$code}
      <option value={$id}>{$code}</option>
    {/foreach}
  </tr>
{/foreach}
