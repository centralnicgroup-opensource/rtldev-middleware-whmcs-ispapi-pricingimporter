<link rel="stylesheet" href="../modules/addons/ispapidpi/css/styles.css">
<!-- <script src="../modules/addons/ispapidpi/js/checkbox.js"></script> -->

<div class="steps" data-steps="3">
     <label class="labelClass">
        <span>Step 1 - Load prices</span> 
        <i></i>
     </label><!--
     --><label>
        <span>Step 2 - Update prices</span>
     </label><!--
     --><label>
        <span>Step 3 - Import prices</span>
        <i></i>
     </label>
  </div>

<br>

<div class="container_step1">
    <div>
        <h2>Use my own HEXONET costs</h2>
        <form action="addonmodules.php?module=ispapidpi" method="POST">
            <input type="hidden" name="price_class" value="DEFAULT_PRICE_CLASS" />
            <input type="submit" class="btn btn-primary" name="default-price-class-button" value="Load"/>
            <br><br>
        </form>

    </div>
    <div>
        <h2>Use a HEXONET Price Class </h2>
        <form action="addonmodules.php?module=ispapidpi" method="POST">
                <select name="price_class">
                    {foreach $queryuserclasslist["PROPERTY"]["USERCLASS"] as $price_class}
                        <option value={$price_class}>{$price_class}</option>
                    {/foreach}
                </select>
            <input type="submit" name="submit" class="btn btn-primary" value="Load"></input>
        </form>

    </div>
    <div>
        <h2>Use a CSV file</h2>
        <form action="addonmodules.php?module=ispapidpi" method="POST">
            <input type="submit" class="btn btn-default btn-xs" name="download-sample-csv" value="Download a sample CSV file"/>
        </form>
        <br/>
        <form action="addonmodules.php?module=ispapidpi" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="price_class" value="CSV-FILE" />
            <input type="file" name="file" id="file"/>
            <input type="submit" name="csv-file-selected" class="btn btn-primary" style="margin-top:5px;" value="Load"/>
            <br><br>
        </form>

    </div>
</div>
