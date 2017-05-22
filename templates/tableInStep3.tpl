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
    <input type=number step=0.01 name=multiplier min=0>
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
