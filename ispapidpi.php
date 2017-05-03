<?php
session_start();
$module_version = "2.0";
//if (!defined("WHMCS"))
//    die("This file cannot be accessed directly");
function ispapidpi_config($params) {
global $module_version;
    $configarray = array(
    "name" => "ISPAPI Pricing Importer",
    "description" => "This module allow you to download your pricing list in CSV format and upload it modified again. You can also add new TLDs in the file.",
    "version" => $module_version,
    "author" => "HEXONET",
    "language" => "english",
    "fields" => array("username" => array ("FriendlyName" => "Admin username", "Type" => "text", "Size" => "30", "Description" => "[REQUIRED]", "Default" => "admin",)));
    return $configarray;
}

function ispapidpi_activate() {
	return array('status'=>'success','description'=>'Installed');
}

function ispapidpi_deactivate() {
	return array('status'=>'success','description'=>'Uninstalled');
}
//filter tld data array for only tlds with usd currency
function filter_array($array,$term){
      $matches = array();
      foreach($array as $key=>$value){
          if($value['currency'] == $term)
              $matches[$key]=$value;
      }
      return $matches;
  }

//this function is called based on the user selection --> selection of price class or to use defualt hexonet costs
function priceclass_or_defualtcosts($priceclass_or_defaultcost)
{
  $tld_pattern = "/PRICE_CLASS_DOMAIN_([^_]+)_/";
  $tlds = [];
  foreach($priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"] as $key => $value)
  {
    if(preg_match($tld_pattern,$value,$match))
    {
      $tlds[] = $match[1];
    }
  }
  //remove duplicates
  $tlds = array_unique($tlds);
  //collect tld, register, renew and transfer prices in an array
  $tld_register_renew_transfer_currency = array();
  foreach ($tlds as $key => $tld)
  {
    $pattern_for_registerprice ="/PRICE_CLASS_DOMAIN_".$tld."_ANNUAL$/";
    $register_match = preg_grep($pattern_for_registerprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
    $register_match_keys = array_keys($register_match);
    // $tld_data[] = $tld;
    foreach ($register_match_keys as $key)
    {
      if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"]))
      {
        //values of the keys
  //register and renew
        $register_price =  $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
        $tld_register_renew_transfer_currency[$tld]['register']= $register_price;
        $tld_register_renew_transfer_currency[$tld]['renew']= $register_price;
      }
    }
//Transfer
    $pattern_for_transferprice = "/PRICE_CLASS_DOMAIN_".$tld."_TRANSFER$/";
    $transfer_match = preg_grep($pattern_for_transferprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
    $transfer_match_keys = array_keys($transfer_match);
    // echo "<br>";
    foreach ($transfer_match_keys as $key)
    {
      if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"]))
      {
        //values of the keys
        $transfer_price =  $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
        $tld_register_renew_transfer_currency[$tld]['transfer']= $transfer_price;
      }
      else {
        $tld_register_renew_transfer_currency[$tld]['transfer'] = "hello";
      }
    }
    //get tld currency
    $pattern_for_currency = "/PRICE_CLASS_DOMAIN_".$tld."_CURRENCY$/";
    $currency_match = preg_grep($pattern_for_currency, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
    $currency_match_keys= array_keys($currency_match);
    foreach($currency_match_keys as $key)
    {
      if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"]))
      {
        $tld_currency = $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
        $tld_register_renew_transfer_currency[$tld]['currency'] = $tld_currency;
      }
    }
  }

  //filter tlds that are with currency USD
  $tld_register_renew_transfer_currency_filter = filter_array($tld_register_renew_transfer_currency,'USD');

  $tld_register_renew_transfer_currency_filter =  array_change_key_case($tld_register_renew_transfer_currency_filter, CASE_LOWER);
  $_SESSION["tld-register-renew-transfer-currency-filter"]=$tld_register_renew_transfer_currency_filter; //session variable for tld data (tld and prices ,currency)
  echo '
  <!--<span><input type="checkbox" onchange="checkAll(this)" class="checkall" />Select all TLDs</span>-->
  <table class="tableClass">
    <tr>
      <th><span><input type="checkbox" onchange="checkAll(this)" class="checkall" /></span></th>
      <th>TLD</th>
      <th>Register</th>
      <th>Renew</th>
      <th>Transfer</th>
      <th>Currency</th>
    </tr>';
  foreach ($tld_register_renew_transfer_currency_filter as $tld => $value){
    echo "<tr>";
    echo "<td><input type='checkbox' class='tocheck'  name='checkbox-tld[]' value='".$tld."'></input></td>";
    echo "<td>".'.'.$tld."</input></td>";
    foreach($value as $key){
      //prints prices in each row
      echo "<td name='Myprices'>".$key."</td>";
    }
    echo "</tr>";
  }
  echo '
  </table>
  <br>
  <input type="submit" name="check-button" value="Next">
   </form>
   ';
}

function ispapidpi_output($vars)
{
    if ($_POST)
    {
    echo "<pre>"; print_r($_POST); echo "</pre>";
    }
  //css
  echo '
  <style>
    .steps{
      margin: 0;
      padding: 0;
      overflow: hidden;
    }
    .steps label{
    	list-style-type: none;
      display: inline-block;

      position: relative;
      margin: 0;
      padding: 0;

      text-align: center;
      line-height: 30px;
      height: 30px;

      background-color: #f0f0f0;
    }
    .steps[data-steps="3"] label{width: 33%;}
    .steps[data-steps="3"] label{width: 25%;}
    .steps[data-steps="3"] label{width: 20%;}
    .steps label > span{
      display: block;
      color: #999;
      font-weight: bold;
      text-transform: uppercase;
    }
    .steps label.labelClass > span{
      color: #666;
      background-color: #ccc;
    }
    .steps label > span:after,
    .steps label > span:before{
      content: "";
      display: block;
      width: 0px;
      height: 0px;

      position: absolute;
      top: 0;
      left: 0;

      border: solid transparent;
      border-left-color: #f0f0f0;
      border-width: 15px;
    }
    .steps label > span:after{
      top: -5px;
      z-index: 1;
      border-left-color: white;
      border-width: 20px;
    }
    .steps label > span:before{
      z-index: 2;
    }
    .steps label.labelClass + label > span:before{
      border-left-color: #ccc;
    }
    .steps label:first-child > span:after, .steps label:first-child > span:before{
      display: none;
    }
    .steps label:first-child i,
    .steps label:last-child i{
      display: block;
      height: 0;
      width: 0;

      position: absolute;
      top: 0;
      left: 0;

      border: solid transparent;
      border-left-color: white;
      border-width: 15px;
    }
    .steps label:last-child i{
      left: auto;
      right: -15px;

      border-left-color: transparent;
      border-right-color: white;
      border-top-color: white;
      border-bottom-color: white;
    }
    table {
  	   border-collapse: collapse;
    }
    th{
    	<!--this part - no lines appear for th elements
      background-color: #ccc;
      text-align: center;-->
    }
    th{
      background: #efefef;
      text-align: center;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px;
    }
    tr:nth-child(even) {
      background: #efefef;
    }
    tr:hover {
      background: #d1d1d1;
    }
  </style>
  ';
  $file = "ispapi";
  require_once(dirname(__FILE__)."/../../../includes/registrarfunctions.php");
	require_once(dirname(__FILE__)."/../../../modules/registrars/".$file."/".$file.".php");
  $registrarconfigoptions = getregistrarconfigoptions($file);
  $ispapi_config = ispapi_config($registrarconfigoptions);
  $command =  $command = array(
          "command" => "queryuserclasslist"
  );
  $queryuserclasslist = ispapi_call($command, $ispapi_config);
  $tlds_with_new_prices =  [];
  if(isset($_POST['checkbox-tld']) || (isset($_SESSION["checkbox-tld"]) && isset($_POST['multiplier'])))
  {
    //Step 3
    if(isset($_POST['checkbox-tld']))
    {
        $_SESSION["checkbox-tld"] = $_POST["checkbox-tld"];
    }
    if(isset($_POST['multiplier']))
    {
        $multiplier = $_POST['multiplier'];
    }
    else
    {
        $multiplier = 1.00;
    }

    echo '
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
      ';
      echo '
      --><label>
        <span>
          <div>
            <form method="POST">';
              echo "<input type='hidden' name='price_class' value='".$_SESSION["price_class"]."'</input>";
              echo '<input style="border:none;" type="submit" name="submit" value="STEP 2"/>
            </form>
          </div>
        </span>
        <i></i>
      </label><!--
      --><label class="labelClass">
        <span>STEP 3</span>
        <i></i>
      </label>
    </div> ';
    echo '<br>';
    echo '
    <form action="addonmodules.php?module=ispapidpi" method="POST">
      <label>Update your prices by using a factor:</label>
        <input type="number" step="0.01" name="multiplier" min="0">
          <input type="submit" name="update" value="Update"/>
      </form>
      <br>
    ';
    //get checked TLD then get register,renew and transfer prices for that TLD
    $get_tld = [];
    echo "session checkbox tld<br>";
    echo "<pre>"; print_r($_SESSION["checkbox-tld"]); echo "</pre>";
    foreach($_SESSION["checkbox-tld"] as $checkbox_tld)
    {
      array_push($get_tld, $checkbox_tld);
    }
    echo "get tld before arry flip<br>";
    echo "<pre>"; print_r($get_tld); echo "</pre>";
    $get_tld = array_flip($get_tld);
    echo "get tld after flip<br>";
    echo "<pre>"; print_r($get_tld); echo "</pre>";
    $get_checked_tld_data = [];
    echo "tld register renew transfer currency filter<br>";
    echo "<pre>"; print_r($_SESSION["tld-register-renew-transfer-currency-filter"]); echo "</pre>";
    foreach($get_tld as $key=>$value)
    {
      if(array_key_exists($key, $_SESSION["tld-register-renew-transfer-currency-filter"]))
      {
        $tld_prices = $_SESSION["tld-register-renew-transfer-currency-filter"][$key];
        $get_checked_tld_data[$key]=$tld_prices;
      }
    }
    echo "get checked tld data<br>";
      echo "<pre>"; print_r($get_checked_tld_data); echo "</pre>";
    $_SESSION["checked_tld_data"] = $get_checked_tld_data;
    $_SESSION["checked_tld_data"] = array_change_key_case($_SESSION["checked_tld_data"], CASE_LOWER);
    // echo "<pre>";
    // print_r($_SESSION["checked_tld_data"]);
    // echo "</pre>";
    // $_SESSION["checked_tld_data"] = array_map('strtolower', $_SESSION["checked_tld_data"]);
    // remove currency element from the array $_SESSION["checked_tld_data"]
    foreach ($_SESSION["checked_tld_data"] as $key => $subArr)
    {
      unset($subArr['currency']);
      $_SESSION["checked_tld_data"][$key] = $subArr;
    }
    echo "get checked tld data something with currency<br>";
      echo "<pre>"; print_r($get_checked_tld_data); echo "</pre>";
    // echo "after removing currency <br>";
    // echo "<pre>";
    // print_r($_SESSION["checked_tld_data"]);
    // echo "</pre>";
    echo '
    <form action="addonmodules.php?module=ispapidpi" method="POST">
    ';
    if(isset($_POST['update']))
    {
      echo '
      <table>
          <tr>
            <th>TLD</th>
            <th colspan="2">Register</th>
            <th colspan="2">Renew</th>
            <th colspan="2">Transfer</th>
            <th colspan="2">Currency</th>
          </tr>
          <tr>
            <th></th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
          </tr>
        ';

      foreach($_SESSION["checked_tld_data"] as $key=>$value)
      {
        echo '<tr id="row">';
        echo '<td>'.'.'.$key.'</td>';
        foreach($value as $key2=>$old_and_new_price)
        {
          echo "<td name='Myprices'>".$old_and_new_price."</td>";
          $update_price1 = $old_and_new_price*$multiplier;
          $update_price=number_format((float)$update_price1, 2, '.', '');
          echo "<td><input type='text' name='PRICE_" . $key . "_" . $key2 . "' value='".$update_price."'></input></td>";
        }
        echo '<td>'."USD".'</td>';
        echo '<td><select name="currency[]">';
        //get currency type from (tblcurrencies)
        $request = mysql_query("SELECT * FROM tblcurrencies");
        while ($currencies = mysql_fetch_array($request)) {
          $currency_id = $currencies["id"];
          $currency = $currencies["code"];
          echo '<option value = "'.$currency_id.'">'.$currency.'</option>';
        }
        echo '</select></td>';
        echo '</tr>';
      }
      echo '
       </table>
     <br>
     ';
     echo'
     <div>
     <input type="checkbox" name="dns_management" value="on">DNS Management</input>
     <input type="checkbox" name="email_forwarding" value="on">Email Forwarding</input>
     <input type="checkbox" name="id_protection" value="on">ID Protection</input>
     <input type="checkbox" name="epp_code" value="on">EPP Code</input>
     <br> <br>
       <input type="submit" name="import" value="Import"/>
     </div>
     </form>
     ';
    }
    else
    {
      echo '
      <form action="addonmodules.php?module=ispapidpi" method="POST">
      ';
      echo '
      <table>
          <tr>
            <th>TLD</th>
            <th colspan="2">Register</th>
            <th colspan="2">Renew</th>
            <th colspan="2">Transfer</th>
            <th colspan="2">Currency</th>
          </tr>
          <tr>
            <th></th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
          </tr>
        ';
      foreach($_SESSION["checked_tld_data"] as $key=>$value)
      {
        echo '<tr>';
        echo '<td>'.'.'.$key.'</td>';

        foreach($value as $key2=>$price)
        {
          echo "<td name='Myprices'>".$price."</td>";
          echo "<td><input type='text' name='PRICE_" . $key . "_" . $key2 . "' value='".$price."'></input></td>";
          // echo "<td name='Myprices'>".$price."</td>";
        }
        echo '<td>'."USD".'</td>';
        echo '<td><select name="currency[]">';
        //get currency type from (tblcurrencies)
        $request = mysql_query("SELECT * FROM tblcurrencies");
        while ($currencies = mysql_fetch_array($request)) {
          $currency_id = $currencies["id"];
          $currency = $currencies["code"];
          echo '<option value = "'.$currency_id.'">'.$currency.'</option>';
        }
        echo '</select></td>';
        echo '</tr>';
      }
      echo '</table>
      <br>';
      echo '<div>
      <input type="checkbox" name="dns_management" value="on">DNS Management</input>
      <input type="checkbox" name="email_forwarding" value="on">Email Forwarding</input>
      <input type="checkbox" name="id_protection" value="on">ID Protection</input>
      <input type="checkbox" name="epp_code" value="on">EPP Code</input>
      <br> <br>
        <input type="submit" name="import" value="Import"/>
      </div>';
      echo '</form>';
    }
    // unset($_SESSION["checkbox-tld"]);
    // session_destroy();
  }
  //
  elseif(isset($_POST['price_class']))
  {
    //step 2
    $_SESSION["price_class"] = $_POST['price_class'];
    echo '
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
    ';

    $command =  $command = array(
            "command" => "StatusUserClass",
            "userclass"=> $_POST['price_class']
    );
    $getdata_of_priceclass = ispapi_call($command, $ispapi_config);
    priceclass_or_defualtcosts($getdata_of_priceclass);
  }
  //step 3 when csv file is selected
  elseif($_POST['checkbox-tld-csv'] || (isset($_SESSION["checkbox-tld-csv"]) && isset($_POST['multiplier'])))
  {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";

    if(isset($_POST['checkbox-tld-csv']))
    {
        $_SESSION["checkbox-tld-csv"] = $_POST["checkbox-tld-csv"];
    }
    if(isset($_POST['multiplier']))
    {
        $multiplier = $_POST['multiplier'];
    }
    else
    {
        $multiplier = 1.00;
    }
    echo '
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
      ';
      echo '
      --><label>
        <span>
          <div>
            <form method="POST">';
              // echo "<input type='hidden' name='price_class' value='".$_SESSION["csv-as-new-array"]."'</input>";
              echo '<input style="border:none;" type="submit" name="submit" value="STEP 2"/>
            </form>
          </div>
        </span>
        <i></i>
      </label><!--
      --><label class="labelClass">
        <span>STEP 3</span>
        <i></i>
      </label>
    </div> ';
    echo '<br>';
    echo '
    <form action="addonmodules.php?module=ispapidpi" method="POST">
      <label>Update your prices by using a factor:</label>
        <input type="number" step="0.01" name="multiplier" min="0">
          <input type="submit" name="update-csv" value="Update"/>
      </form>
      <br>
    ';
    $get_tld = [];
   foreach($_SESSION["checkbox-tld-csv"] as $checkbox_tld)
   {
     array_push($get_tld, $checkbox_tld);
   }

    $get_tld = array_flip($get_tld);

    $get_checked_tld_data = [];
    foreach($get_tld as $key=>$value)
    {
      if(array_key_exists($key, $_SESSION["csv-as-new-array"]))
      {
        $tld_prices = $_SESSION["csv-as-new-array"][$key];
        $get_checked_tld_data[$key]=$tld_prices;
      }
    }
   $_SESSION["checked_tld_csv_data"] = $get_checked_tld_data;
   $_SESSION["checked_tld_csv_data"] = array_change_key_case($_SESSION["checked_tld_csv_data"], CASE_LOWER);

    if(isset($_POST['update-csv']))
    {

      echo '
      <table>
          <tr>
            <th>TLD</th>
            <th colspan="2">Register</th>
            <th colspan="2">Renew</th>
            <th colspan="2">Transfer</th>
            <th colspan="2">Currency</th>
          </tr>
          <tr>
            <th></th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
          </tr>
        ';
        foreach($_SESSION["checked_tld_csv_data"] as $key => $value)
        {
          echo '<tr id="row">';
          echo '<td>'.'.'.$key.'</td>';
          foreach($value as $key2=>$old_and_new_price)
          {
            echo "<td name='Myprices'>".$old_and_new_price."</td>";
            $update_price1 = $old_and_new_price*$multiplier;
            $update_price=number_format((float)$update_price1, 2, '.', '');
            echo "<td><input type='text' name='PRICE_" . $key . "_" . $key2 . "' value='".$update_price."'></input></td>";
          }
          echo '<td>'."USD".'</td>';
          echo '<td><select name="currency[]">';
       //get currency type from (tblcurrencies)
       $request = mysql_query("SELECT * FROM tblcurrencies");
       while ($currencies = mysql_fetch_array($request)) {
         $currency_id = $currencies["id"];
         $currency = $currencies["code"];
         echo '<option value = "'.$currency_id.'">'.$currency.'</option>';
       }
       echo '</select></td>';
       echo '</tr>';
     }
     echo '
      </table>
    <br>
    ';
    echo '
    <form action="addonmodules.php?module=ispapidpi" method="POST">
    ';
    echo'
     <div>
     <input type="checkbox" name="dns_management" value="on">DNS Management</input>
     <input type="checkbox" name="email_forwarding" value="on">Email Forwarding</input>
     <input type="checkbox" name="id_protection" value="on">ID Protection</input>
     <input type="checkbox" name="epp_code" value="on">EPP Code</input>
     <br> <br>
       <input type="submit" name="import" value="Import"/>
     </div>
     </form>
     ';
    }
    else
    {
      echo '
      <form action="addonmodules.php?module=ispapidpi" method="POST">
      ';
      echo '
      <table>
          <tr>
            <th>TLD</th>
            <th colspan="2">Register</th>
            <th colspan="2">Renew</th>
            <th colspan="2">Transfer</th>
            <th colspan="2">Currency</th>
          </tr>
          <tr>
            <th></th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
          </tr>
        ';
        foreach($_SESSION["checked_tld_csv_data"] as $key=>$value)
          {
            echo '<tr>';
            echo '<td>'.$key.'</td>';

            foreach($value as $key2=>$price)
            {
              echo "<td name='Myprices'>".$price."</td>";
              echo "<td><input type='text' name='PRICE_" . $key . "_" . $key2 . "' value='".$price."'></input></td>";
              // echo "<td name='Myprices'>".$price."</td>";
            }
            echo '<td>'."USD".'</td>';
            echo '<td><select name="currency[]">';
            //get currency type from (tblcurrencies)
            $request = mysql_query("SELECT * FROM tblcurrencies");
            while ($currencies = mysql_fetch_array($request)) {
              $currency_id = $currencies["id"];
              $currency = $currencies["code"];
              echo '<option value = "'.$currency_id.'">'.$currency.'</option>';
            }
            echo '</select></td>';
            echo '</tr>';
          }
          echo '</table>
      <br>';
      echo '<div>
      <input type="checkbox" name="dns_management" value="on">DNS Management</input>
      <input type="checkbox" name="email_forwarding" value="on">Email Forwarding</input>
      <input type="checkbox" name="id_protection" value="on">ID Protection</input>
      <input type="checkbox" name="epp_code" value="on">EPP Code</input>
      <br> <br>
        <input type="submit" name="import" value="Import"/>
      </div>';
      echo '</form>';
    }
  }

  //when csv file selected
  //working with csv file
  elseif(isset($_POST['csv-file-selected']))
  {
    $_SESSION["csv-file-selected-session"] = $_POST['csv-file-selected'];
    if ( isset($_FILES["file"])) {
      $_SESSION["csv-file"] = $_FILES["file"];

      //if there was an error uploading the file
        if ($_FILES["file"]["error"] > 0) {
            echo "Return Code: " . $_FILES["file"]["error"] . " error uploading the file"."<br />";
        }
        else {
          //
          $tmpName = $_FILES['file']['tmp_name'];
          $csvAsArray = array_map(function($d) {
              return str_getcsv($d, ";");
          }, file($tmpName));
          // $csvAsArray = array_map('str_getcsv', file($tmpName));
          array_shift($csvAsArray); //remove first element (header part of the csv file)
          // echo "<pre>";
          // print_r($csvAsArray); echo "</pre>";
          $_SESSION["csv-as-array"] = $csvAsArray;
          $csv_as_new_array = [];
          foreach($csvAsArray as $key=>$value)
            {
               $newKey = "";
               foreach($value as $ky=>$val)
               {
                 if($ky == 0)
                 {
                   //first element to take as new key
                   $newKey = $val;
                   $csv_as_new_array[$newKey] = [];
                 }
                 else{
                   $csv_as_new_array[$newKey][] = $val;
                 }
               }
            }
            $_SESSION["csv-as-new-array"] = $csv_as_new_array;
            echo "<pre>";print_r($_SESSION["csv-as-new-array"]);echo "</pre>";
            echo '
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
            ';
            echo '
            <!--<span><input type="checkbox" onchange="checkAll(this)" class="checkall" />Select all TLDs</span>-->
            <table class="tableClass">
              <tr>
                <th><span><input type="checkbox" onchange="checkAll(this)" class="checkall" /></span></th>
                <th>TLD</th>
                <th>Register</th>
                <th>Renew</th>
                <th>Transfer</th>
                <th>Currency</th>
              </tr>';
              foreach ($csv_as_new_array as $tld => $value){
                echo "<tr>";
                echo "<td><input type='checkbox' class='tocheck'  name='checkbox-tld-csv[]' value='".$tld."'></input></td>";
                echo "<td>".$tld."</input></td>";
                foreach($value as $key){
                  //prints prices in each row
                  echo "<td name='Myprices'>".$key."</td>";
                }
                echo "<td>USD</td>";
                echo "</tr>";
              }
            echo '
            </table>
            <br>
            <input type="submit" name="check-button" value="Next">
             </form>
             ';

       }
    } else {
            echo "No file selected <br />";
    }
  }
  //
  //when user selects defualt hexonet costs
  //to use defualt HEXONET costs
  elseif(isset($_POST['default-costs']))
  {
    echo '
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
    ';
    $command =  $command = array(
            "command" => "StatusUser"
    );
    $default_costs = ispapi_call($command, $ispapi_config);

    $_SESSION["defualt-hexonet-costs"] = $default_costs;
    priceclass_or_defualtcosts($default_costs);
    // echo "<br>hexonet defualt costs are <br>";echo "<pre>";print_r($default_costs);echo "</pre>";
  }
  else{
    // step 1
    echo '
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
    ';
    echo '
    <form action="addonmodules.php?module=ispapidpi" method="POST">
    <br>
      <input type="submit" name="default-costs" value="Use my default HEXONET costs"/>
      <br>
      <label>or</label>
      </form>
    ';
    echo '
      <form action="addonmodules.php?module=ispapidpi" method="POST">
        <label>Select one of my HEXONET Price Classes:</label>
          <br>
          <select name="price_class">
    ';
    foreach($queryuserclasslist["PROPERTY"]["USERCLASS"] as $price_class){
      echo "<option value=".$price_class.">".$price_class."</option>";
    }
    echo '</select>

      <input type="submit" name="submit" value="Select"/>
      </form>
      ';
      echo '
      <form action="addonmodules.php?module=ispapidpi" method="POST" enctype="multipart/form-data">
      <label>or</label>
      <br>
      <label for="file">Filename:</label><input type="file" name="file" id="file"/> <br />
        <input type="submit" name="csv-file-selected" value="Next"/>
        <br><br>
        </form>
      ';
  }
  //select all script
  echo '
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
  ';

  //
  if(isset($_POST['import'])){

    $prices_match_pattern = "/PRICE_(.*)_(.*)/";
    $tld_match = []; //has all the tld names which have new prices
    foreach($_POST as $key=>$value){
      if(preg_match($prices_match_pattern,$key,$match)){
        $tld_match[] = $match[1];
      }
    }
    //for prices renew, register, transfer
    $price_name_match = []; //has all new prices (strings)
    foreach($_POST as $key=>$value){
      if(preg_match($prices_match_pattern,$key,$match)){
        $price_name_match[] = $match[2];
      }
    }
    // remove duplicates
    // $tld_match = array_unique($tld_match);
    // echo "tld match array<br>";
    // echo "<pre>"; print_r($tld_match);echo "</pre>";echo"<br>";
    // echo "price name match array <br>";
    // echo "<pre>"; print_r($price_name_match);echo "</pre>";echo"<br>";
    $tld_new_price = [];
    foreach($_POST as $key=>$value){
      if(preg_match($prices_match_pattern, $key)){
        $tld_new_price[] = $value;
      }
    }
    // echo "tld new price<br>";
    // echo "<pre>"; print_r($tld_new_price);echo "</pre>";echo"<br>";
    $new_prices_for_whmcs = array_combine_($tld_match, $tld_new_price);

    //for checked items -DNS Management, email Forwarding, id Protection, epp code
    $domain_addons = [];
    $dns_pattern = "/dns_management/";
    foreach($_POST as $key=>$value){
      if(preg_match($dns_pattern, $key)){
        $domain_addons['dns-management'] = $value;
      }
    }
    $emailforwarding_pattern = "/email_forwarding/";
    foreach($_POST as $key=>$value){
      if(preg_match($emailforwarding_pattern, $key)){
        $domain_addons['email-forwarding'] = $value;
      }
    }
    $idprotection_pattern = "/id_protection/";
    foreach($_POST as $key=>$value){
      if(preg_match($idprotection_pattern, $key)){
        $domain_addons['id-protection'] = $value;
      }
    }
    $eppcode_pattern = "/epp_code/";
    foreach($_POST as $key=>$value){
      if(preg_match($eppcode_pattern, $key)){
        $domain_addons['epp-code'] = $value;
      }
    }
    //for currency
    $currencies = [];
    $currency_pattern = "/currency/";
    foreach($_POST as $key=>$value){
      if(preg_match($currency_pattern, $key)){
        $currencies['currency'] = $value;
      }
    }
    foreach($new_prices_for_whmcs as $key=>$value)
    {
      array_push($new_prices_for_whmcs[$key], $domain_addons);
    }

    //to merge each curreny value from currencies array new_prices_for_whmcs
    $i = -1;
    foreach($new_prices_for_whmcs as $key=>$value)
    {
      $i++;
      $new_prices_for_whmcs[$key]['currency'] = $currencies['currency'][$i];
    }
    // echo "<br> new prices for whcms<br>";
    // echo "<pre>"; print_r($new_prices_for_whmcs);;echo "</pre>";echo"<br>";
    startimport($new_prices_for_whmcs);
  }
}
//
function array_combine_($keys, $values)
{
    $result = array();
    foreach ($keys as $i => $k) {
        $result[$k][] = $values[$i];
    }
    array_walk($result, create_function('&$v', '$v = (count($v) == 1)? array_pop($v): $v;'));
    return    $result;
}
//import button
// function startimport($tld_pricing)
function startimport($prices_for_whmcs)
{
  //get currency type from  (tblcurrencies)
  // $request = mysql_query("SELECT * FROM tblcurrencies");
  // while ($currencies = mysql_fetch_array($request)) {
  //   $currency_id = $currencies["id"];
  //   $currency = $currencies["code"];
  //   // echo "curreny id and currency<br>";
  //   // echo "<pre>"; print_r($currency_id); echo "</pre>";
  //   //   echo "<pre>"; print_r($currency); echo "</pre>";
  // }

  //here comes --> loop through array and insert or update the tld and prices for whmcs to DB
  $prices_for_whmcs = array_change_key_case($prices_for_whmcs, CASE_LOWER);
  foreach($prices_for_whmcs as $key=>$value)
  {
   //with TLD/extension
   $result = mysql_query("SELECT * FROM tbldomainpricing WHERE extension='".'.'.$key."'");
   $tbldomainpricing = mysql_fetch_array($result);
   if(!empty($tbldomainpricing)){
     //
     update_query("tbldomainpricing",array("dnsmanagement"=> $prices_for_whmcs[$key][3]['dns-management'], "emailforwarding"=> $prices_for_whmcs[$key][3]['email-forwarding'], "idprotection"=> $prices_for_whmcs[$key][3]['id-protection'], "eppcode"=> $prices_for_whmcs[$key][3]['epp-code'], "autoreg"=> "ispapi"),array("extension" => '.'.$key));
   }else{
     $tbldomainpricing["id"] = insert_query("tbldomainpricing",array("extension" => '.'.$key, "dnsmanagement"=> $prices_for_whmcs[$key][3]['dns-management'], "emailforwarding"=> $prices_for_whmcs[$key][3]['email-forwarding'], "idprotection"=> $prices_for_whmcs[$key][3]['id-protection'], "eppcode"=>$prices_for_whmcs[$key][3]['epp-code'], "autoreg"=>"ispapi"));
   }
    //replace or add pricing for domainregister
    $result = mysql_query("SELECT * FROM tblpricing WHERE type='domainregister' AND currency=".$prices_for_whmcs[$key]['currency']." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
    $tblpricing = mysql_fetch_array($result);
    if(!empty($tblpricing)){
      update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][0]), array("id" => $tblpricing["id"]));
    }else{
      insert_query("tblpricing",array("type" => "domainregister", "currency" => $prices_for_whmcs[$key]['currency'], relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][0], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
    }

    //replace or add pricing for domaintransfer
		$result = mysql_query("SELECT * FROM tblpricing WHERE type='domaintransfer' AND currency=".$prices_for_whmcs[$key]['currency']." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
		$tblpricing = mysql_fetch_array($result);
		if(!empty($tblpricing)){
			update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][1]), array("id" => $tblpricing["id"]));
		}else{
			insert_query("tblpricing",array("type" => "domaintransfer", "currency" => $prices_for_whmcs[$key]['currency'], relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][1], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
		}

    //replace or add pricing for domainrenew
		$result = mysql_query("SELECT * FROM tblpricing WHERE type='domainrenew' AND currency=".$prices_for_whmcs[$key]['currency']." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
		$tblpricing = mysql_fetch_array($result);
		if(!empty($tblpricing)){
			update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][2]), array("id" => $tblpricing["id"]));
		}else{
			insert_query("tblpricing",array("type" => "domainrenew", "currency" => $prices_for_whmcs[$key]['currency'], relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][2], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
		}

  }
echo "<div class='infobox'><strong><span class='title'>Update successful!</span></strong><br>Your pricing list has been updated successfully.</div>";
}
