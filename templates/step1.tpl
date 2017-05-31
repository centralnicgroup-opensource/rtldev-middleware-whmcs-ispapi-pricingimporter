<link rel="stylesheet" href="../modules/addons/ispapidpi/css/styles.css">
<!-- <script src="../modules/addons/ispapidpi/js/checkbox.js"></script> -->

<div class="steps" data-steps="3">
     <label class="labelClass">
        <span>Step 1</span>
        <i></i>
     </label><!--
     --><label>
        <span>Step 2</span>
     </label><!--
     --><label>
        <span>Step 3</span>
        <i></i>
     </label>
  </div>

<form action="addonmodules.php?module=ispapidpi" method="POST">
<br>
<input type="hidden" name="price_class" value="DEFAULT_PRICE_CLASS" />
  <input type="submit" name="default-button" value="Use my default HEXONET costs"/>
  <br><br>
  <label>or</label>
  </form>

  <form action="addonmodules.php?module=ispapidpi" method="POST">
    <label>Select one of my HEXONET Price Classes:</label>
      <br>
      <select name="price_class">
        {foreach $queryuserclasslist["PROPERTY"]["USERCLASS"] as $price_class}
          <option value={$price_class}>{$price_class}</option>
        {/foreach}
          </select>
          <input type="submit" name="submit" value="Select"></input>
          </form>
          <form action="addonmodules.php?module=ispapidpi" method="POST" enctype="multipart/form-data">
          <label>or</label>
          <br>
          <input type="hidden" name="price_class" value="CSV-FILE" />
          <label for="file">Upload a CSV file</label><input type="file" name="file" id="file"/> <br />
            <input type="submit" name="csv-file-selected" value="Next"/>
            <br><br>
            </form>
            <form action="addonmodules.php?module=ispapidpi" method="POST">
              <input type="submit" name="download-sample-csv" value="Here you can download a sample CSV file" style="background:none;border:0;text-decoration:underline"/>
            </form>
