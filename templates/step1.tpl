<link rel="stylesheet" href="../modules/addons/ispapidpi/css/styles.css">
<!--  comment lines at label tags should not be removed in order to keep the design appropriate -->
<div class="steps" data-steps="3">
     <label class="labelClass">
        <span>STEP 1 - LOAD PRICES</span>
        <i></i>
     </label><!--
     --><label>
        <span>STEP 2 - UPDATE PRICES</span>
     </label><!--
     --><label>
        <span>STEP 3 - IMPORT PRICES</span>
        <i></i>
     </label>
  </div>

<div style="margin-bottom:10px;margin-top:10px;font-weight:bold">You are connected with <span style="color:green">{$user}</span> in <span style="color:green">{$entity}</span>.</div>

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
          {if (count($queryuserclasslist["PROPERTY"]["USERCLASS"]) == 0)}
            <span>Price Classes can be created in the HEXONET Control Panel</span>
            {else}
                <select name="price_class">
                    {foreach $queryuserclasslist["PROPERTY"]["USERCLASS"] as $price_class}
                        <option value={$price_class}>{$price_class}</option>
                    {/foreach}
                </select>
            <input type="submit" name="submit" class="btn btn-primary" value="Load"></input>
          {/if}
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
