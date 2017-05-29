{foreach $smarty.session.checked_tld_data as $key=>$value}
  <tr id="row">
    <td>.{$key}</td>
    {foreach $value as $key2=>$price}
      <td name=Myprices>{$price}</td>
      <td><input type=text name=PRICE_{$key}_{$key2} value={$price}></input></td>
    {/foreach}
    <td>USD</td>
    <td><select name=currency[]>
      {$request}= mysql_query("SELECT * FROM tblcurrencies")
      {while $currencies = mysql_fetch_array($request)}
        $currency_id = $currencies["id"]
        $currency = $currencies["code"]
        <option value ={$currency_id}>{$currency}</option>
      {/while}
  </select></td>
  </tr>
{/foreach}
