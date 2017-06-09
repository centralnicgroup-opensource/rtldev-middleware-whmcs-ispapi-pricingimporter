<link rel="stylesheet" href="../modules/addons/ispapidpi/css/styles.css">
<!-- <script src="../modules/addons/ispapidpi/js/checkbox.js"></script> -->
<div class="steps" data-steps="3">
  <label>
        <span>
      <div>
        <form method="POST">
          <input style="border:none;" type="submit" name="submit" value="STEP 1 - LOAD PRICES"/>
        </form>
      </div>
    </span>
    <i></i>
  </label><!--
  --><label class="labelClass">
    <span>STEP 2 - UPDATE PRICES</span>
  </label><!--
  --><label>
  <span>STEP 3 - IMPORT PRICES</span>
  <i></i>
  </label>
</div>
<br>
  <form action="addonmodules.php?module=ispapidpi" method="POST">
    <label><h2>Select the TLDs you want to import:</h2></label>
    <br>
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
            <td>.{$tld}</input></td>
            {foreach $value as $key}
                <td name=Myprices>{$key}</td>
            {/foreach}
        </tr>
   {/foreach}

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
<input type="submit" name="check-button" class="btn btn-primary" value="Next">

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
