<!-- 
<table class="tableClass">
  <tr>
    <th><span><input type="checkbox" onchange="checkAll(this)" class="checkall" /></span></th>
    <th>TLD</th>
    <th>Register</th>
    <th>Renew</th>
    <th>Transfer</th>
    <th>Currency</th>
  </tr>
  {foreach from=$tld_register_renew_transfer_currency_filter item=element}
  <tr>
    <td><input type=checkbox class=tocheck  name=checkbox-tld[] value=item></input></td>
  <td>item</input></td>
  foreach($element as $key){
    //prints prices in each row
    echo "<td name='Myprices'>".$key."</td>";
  }
  echo "</tr>";

echo '
</table>
<br>
<input type="submit" name="check-button" value="Next">
 </form>
 ';


 Example with simple variable:<br>
 Hello {$name}

 <br><br>

 Example with array:<br>
 <ul>
  {foreach from=$myarray item=element}
  <li>{$element.name} ({$element.age})</li>
  {/foreach}
 </ul> -->
