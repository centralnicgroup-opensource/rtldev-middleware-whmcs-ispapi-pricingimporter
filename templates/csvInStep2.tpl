<link rel="stylesheet" href="../modules/addons/ispapidpi/css/styles.css">

<div class="steps" data-steps="3">
  <label>
        <span>
      <div>
        <form method="POST">
          <input style="border:none;" type="submit" name="submit" value="STEP 1"/>
        </form>
      </div>
    </span>
    <i></i>
  </label><!--
  --><label class="labelClass">
    <span>STEP 2</span>
  </label><!--
  --><label>
  <span>STEP 3</span>
  <i></i>
  </label>
</div>
<br>
  <form action="addonmodules.php?module=ispapidpi" method="POST">
    <label>Select the TLDs you want to import:</label>
    <br>
 <table class="tableClass">
   <tr>
     <th><span><input type="checkbox" onchange="checkAll(this)" class="checkall" /></span></th>
     <th>TLD</th>
     <th>Register</th>
     <th>Renew</th>
     <th>Transfer</th>
     <th>Currency</th>
   </tr>
  {foreach $csv_as_new_array as $tld=>$value}
    <tr>
      <td><input type=checkbox class=tocheck name=checkbox-tld[] value={$tld}></input></td>
      <td>.{$tld}</input></td>
      {foreach $value as $key}
        <td name='Myprices'>{$key}</td>
      {/foreach}
   </tr>
 {/foreach}
 </table>
 <br>
 <input type="submit" name="check-button" value="Next">
  </form>

<script type="text/javascript">
  function checkAll(ele) {
    var checkboxes = document.getElementsByTagName("input");
    if (ele.checked) {
      for (var i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].type == "checkbox") {
          checkboxes[i].checked = true;
        }
      }
    }
    else {
      for (var i = 0; i < checkboxes.length; i++) {
        console.log(i)
        if (checkboxes[i].type == "checkbox") {
          checkboxes[i].checked = false;
        }
     }
  }
}
</script>
