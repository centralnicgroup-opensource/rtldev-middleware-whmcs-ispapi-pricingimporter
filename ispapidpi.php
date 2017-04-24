<?php
session_start();
$module_version = "1.4";
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
    //echo "<pre>"; echo print_r($configarray);echo "</pre>";
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

function ispapidpi_output($vars)
{
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

  .steps label > span {
  display: block;

  color: #999;
  font-weight: bold;
  text-transform: uppercase;
  }
  .steps label.labelClass > span {
  color: #666;
  background-color: #ccc;
  }
  .steps label > span:after,
  .steps label > span:before {
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
  .steps label > span:after {
  top: -5px;
  z-index: 1;
  border-left-color: white;
  border-width: 20px;
  }
  .steps label > span:before {
  z-index: 2;
  }
  .steps label.labelClass + label > span:before{
    border-left-color: #ccc;
  }
  .steps label:first-child > span:after, .steps label:first-child > span:before{
    display: none;
  }
  .steps label:first-child i,
  .steps label:last-child i {
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

  .steps label:last-child i {
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
//   if ($_POST)
// {
//     echo "<pre>" . print_r($_POST, true) . "</pre>";
// }
	// echo 'HELLO';
  $file = "ispapi";
  require_once(dirname(__FILE__)."/../../../includes/registrarfunctions.php");
	require_once(dirname(__FILE__)."/../../../modules/registrars/".$file."/".$file.".php");
  //TO-DO : add tests ti check for these files- if doesnt exits throw an error - look in domainChecker code on gitLab
  //https://gitlab.hexonet.net/anthonys/ispapi_whmcs-domaincheckaddon_v7/blob/master/modules/addons/ispapidomaincheck/ispapidomaincheck.php
  $registrarconfigoptions = getregistrarconfigoptions($file);
  $ispapi_config = ispapi_config($registrarconfigoptions);
  $command =  $command = array(
          "command" => "queryuserclasslist"
  );
  $checkAuthentication = ispapi_call($command, $ispapi_config);
  // echo "<pre>";print_r($checkAuthentication);echo "</pre>";
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
        $multiplier = 1;
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
        <input type="number" step="0.1" name="multiplier" min="0">
          <input type="submit" name="update" value="Update"/>
      </form>
      <br>
    ';
    //get checked TLD then get register,renew and transfer prices for that TLD
    $get_tld = [];
    foreach($_SESSION["checkbox-tld"] as $checkbox_tld)
    {
      array_push($get_tld, $checkbox_tld);
    }
    $get_tld = array_flip($get_tld);

    $get_checked_tld_data = [];
    foreach($get_tld as $key=>$value)
    {
      if(array_key_exists($key, $_SESSION["tld-register-renew-transfer-currency-filter"]))
      {
        $tld_prices = $_SESSION["tld-register-renew-transfer-currency-filter"][$key];
        $get_checked_tld_data[$key]=$tld_prices;
      }
    }
    $_SESSION["checked_tld_data"] = $get_checked_tld_data;
    // echo "before with currency <br>";
    // echo "<pre>";
    // print_r($_SESSION["checked_tld_data"]);
    // echo "</pre>";

    // remove currency element from the array $_SESSION["checked_tld_data"]
    foreach ($_SESSION["checked_tld_data"] as $key => $subArr)
    {
      unset($subArr['currency']);
      $_SESSION["checked_tld_data"][$key] = $subArr;
    }
    // echo "after removing currency <br>";
    // echo "<pre>";
    // print_r($_SESSION["checked_tld_data"]);
    // echo "</pre>";

    //new prices //later must be deleted this part of code
    foreach ($_SESSION["checked_tld_data"] as $key => $value) {
      {
         foreach($value as $ky => $val)
         {
           $tlds_with_new_prices[$key][] = $val * $multiplier;
         }
       }

    }
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
            <th>Currency</th>
          </tr>
          <tr>
            <th></th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th></th>
          </tr>
        ';

      foreach($_SESSION["checked_tld_data"] as $key=>$value)
      {
        echo '<tr id="row">';
        echo '<td>'.$key.'</td>';
        foreach($value as $key2=>$old_and_new_price)
        {
          echo "<td name='Myprices'>".$old_and_new_price."</td>";
          echo "<td><input type='text' name='PRICE_" . $key . "_" . $key2 . "' value='".$old_and_new_price*$multiplier."'></input></td>";
        }
        echo '<td>'."USD".'</td>';
        echo '</tr>';
      }
      echo '
       </table>
     <br>';
     echo'
     <div>
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
            <th>Currency</th>
          </tr>
          <tr>
            <th></th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th style="width:16%">Cost</th>
            <th style="width:16%">Sale</th>
            <th></th>
          </tr>
        ';
      foreach($_SESSION["checked_tld_data"] as $key=>$value)
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
        echo '</tr>';
      }
      echo '</table>
      <br>';
      echo '<div>
        <input type="submit" name="import" value="Import"/>
      </div>';
      echo '</form>';
    }
  }
  elseif(isset($_POST['price_class']))
  {
    //step 2
    $_SESSION["price_class"] = $_POST['price_class'];

    //old
  // <div style="float: left">
  //             <form method="POST">
  //                 <input style="border:none;" type="submit" name="submit" value="Step 1"/>
  //             </form>
  //         </div>
  //         <label style="background-color: yellow;">Step 2</label>
  //         <label>Step 3</label>
  //     </div>
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
    ';
    $command =  $command = array(
            "command" => "StatusUserClass",
            "userclass"=> $_POST['price_class']
    );
    $getdata_of_priceclass = ispapi_call($command, $ispapi_config);
    $tld_pattern = "/PRICE_CLASS_DOMAIN_([^_]+)_/";
    $tlds = [];
    foreach($getdata_of_priceclass["PROPERTY"]["RELATIONTYPE"] as $key => $value)
    {
      if(preg_match($tld_pattern,$value,$match))
      {
        $tlds[] = $match[1];
      }
    }
    //remove duplicates
    $tlds = array_unique($tlds);
    //collect tlds whose currency is USD
    // $tlds_usd_currency = [];
    // foreach($tlds as $key => $tld)
    // {
    //   $pattern_for_currency = "/PRICE_CLASS_DOMAIN_".$tld."_CURRENCY$/";
    //   $currency_match = preg_grep($pattern_for_currency, $getdata_of_priceclass["PROPERTY"]["RELATIONVALUE"]);
    //   echo "tlds with usd currency are <br>";
    //   echo "<pre>";
    //   print_r($currency_match);
    //   echo "</pre>";
    //   echo "currency match tlds <br>";
    //  $currency_match_tlds = array_keys($currency_match);
    //  echo "<pre>";
    //  print_r($currency_match_tlds);
    //  echo "</pre>";
    //   /* foreach($currency_match_tlds as $key)
    //   {
    //     if(array_key_exists($key, $getdata_of_priceclass["PROPERTY"]["RELATIONVALUE"]))
    //     {
    //       $tlds_usd_currency[] = $key;
    //     }
    //   }*/
    // }
    // echo "<br>";
    // echo "tlds with usd currency are <br>";
    // echo "<pre>";
    // print_r($tlds_usd_currency);
    // echo "</pre>";

    //collect tld, register, renew and transfer prices in an array
    $tld_register_renew_transfer_currency = array();
    foreach ($tlds as $key => $tld)
    {
      $pattern_for_registerprice ="/PRICE_CLASS_DOMAIN_".$tld."_ANNUAL$/";
      $register_match = preg_grep($pattern_for_registerprice, $getdata_of_priceclass["PROPERTY"]["RELATIONTYPE"]);
      $register_match_keys = array_keys($register_match);
      // $tld_data[] = $tld;
      foreach ($register_match_keys as $key)
      {
        if(array_key_exists($key, $getdata_of_priceclass["PROPERTY"]["RELATIONVALUE"]))
        {
          //values of the keys
    //register and renew
          $register_price =  $getdata_of_priceclass["PROPERTY"]["RELATIONVALUE"][$key];
          $tld_register_renew_transfer_currency[$tld]['register']= $register_price;
          $tld_register_renew_transfer_currency[$tld]['renew']= $register_price;
        }
      }
  //Transfer
      $pattern_for_transferprice = "/PRICE_CLASS_DOMAIN_".$tld."_TRANSFER$/";
      $transfer_match = preg_grep($pattern_for_transferprice, $getdata_of_priceclass["PROPERTY"]["RELATIONTYPE"]);
      $transfer_match_keys = array_keys($transfer_match);
      // echo "<br>";
      foreach ($transfer_match_keys as $key)
      {
        if(array_key_exists($key, $getdata_of_priceclass["PROPERTY"]["RELATIONVALUE"]))
        {
          //values of the keys
          $transfer_price =  $getdata_of_priceclass["PROPERTY"]["RELATIONVALUE"][$key];
          $tld_register_renew_transfer_currency[$tld]['transfer']= $transfer_price;
        }
      }
      //get tld currency
      $pattern_for_currency = "/PRICE_CLASS_DOMAIN_".$tld."_CURRENCY$/";
      $currency_match = preg_grep($pattern_for_currency, $getdata_of_priceclass["PROPERTY"]["RELATIONTYPE"]);
      $currency_match_keys= array_keys($currency_match);
      foreach($currency_match_keys as $key)
      {
        if(array_key_exists($key, $getdata_of_priceclass["PROPERTY"]["RELATIONVALUE"]))
        {
          $tld_currency = $getdata_of_priceclass["PROPERTY"]["RELATIONVALUE"][$key];
          $tld_register_renew_transfer_currency[$tld]['currency'] = $tld_currency;
        }
      }
    }

    $tld_register_renew_transfer_currency_filter = filter_array($tld_register_renew_transfer_currency,'USD');
    // echo "tld data is with USD <br>";
    // echo "<pre>";
    // print_r($tld_register_renew_transfer_currency_filter);
    // echo "</pre>";

    $_SESSION["tld-register-renew-transfer-currency-filter"]=$tld_register_renew_transfer_currency_filter; //session variable for tld data (tld and prices ,currency)
    // echo "<pre>";print_r($_SESSION["tlddata"]);echo "</pre>";
    echo '
    <table class="tableClass">
      <tr>
        <th>TLD</th>
        <th>Register</th>
        <th>Renew</th>
        <th>Transfer</th>
        <th>Currency</th>
      </tr>';
    foreach ($tld_register_renew_transfer_currency_filter as $tld => $value)
    {
      echo "<tr>";
      echo "<td><input type='checkbox' name='checkbox-tld[]' value='".$tld."'>".$tld."</input></td>";
      foreach($value as $key)
      {
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
  else
  {
    // <div class="steps">
    //    <label style="background-color: yellow;">Step 1</label>
    //    <label>Step 2</label>
    //    <label>Step 3</label>
    // </div>
    //step1
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
        <label>Select your price class:
          <br>
          <select name="price_class">
            <option selected="disabled selected">Price class</option>"
    ';

    foreach($checkAuthentication["PROPERTY"]["USERCLASS"] as $price_class)
    {
      echo "<option value=".$price_class.">".$price_class."</option>";
    }
    echo '</select>
      </label>
      <input type="submit" name="submit" value="Select"/>
      ';
  }
  if(isset($_POST['import']))
  {
    // $prices_match_pattern = "/PRICE_(.*)_register/";
    $prices_match_pattern = "/PRICE_(.*)_(.*)/";
    $tld_match = []; //has all the tld names which have new prices
    foreach($_POST as $key=>$value)
    {
      if(preg_match($prices_match_pattern,$key,$match))
      {
        $tld_match[] = $match[1];
      }
    }
    //for price names renew, register, transfer
    $price_name_match = []; //has all the tld names which have new prices
    foreach($_POST as $key=>$value)
    {
      if(preg_match($prices_match_pattern,$key,$match))
      {
        $price_name_match[] = $match[2];
      }
    }
    //avoid duplicates
    // $tld_match = array_unique($tld_match);
    // echo "<pre>"; print_r($tld_match);echo "</pre>";echo"<br>";
    // echo "<pre>"; print_r($price_name_match);echo "</pre>";echo"<br>";
    $tld_new_price = [];
    foreach($_POST as $key=>$value)
    {
      // echo "<br>";
      if(preg_match($prices_match_pattern, $key))
      {
        $tld_new_price[] = $value;
      }
    }
    // echo "prices are <br>";
    // echo "<pre>"; print_r($tld_new_price);echo "</pre>";echo"<br>";

    // echo "merge arrays  <br>";
    $new_prices_for_whmcs = array_combine_($tld_match, $tld_new_price);
    // print_r($new_variable);echo "</pre>";
    startimport($new_prices_for_whmcs);
  }
}
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
  //get currency type from DB (tblcurrencies)
  $request = mysql_query("SELECT * FROM tblcurrencies");
  while ($currencies = mysql_fetch_array($request)) {
    $currency_id = $currencies["id"];
    $currency = $currencies["code"];
      // echo "<option value='".$currencies["id"]."'>".$currencies["code"]."</option>";
  }
  // echo "start import function<br>";
  // echo $currency_id;
  // echo "<br>";
  // echo $currency;

  //here comes --> loop through array and insert or update the tld and prices for whmcs to DB
  foreach($prices_for_whmcs as $key=>$value)
  {
    // echo "<br> foreach loop <br>";

   //with TLD/extension
   $result = mysql_query("SELECT * FROM tbldomainpricing WHERE extension='".$key."'");
   $tbldomainpricing = mysql_fetch_array($result);
   if(!empty($tbldomainpricing)){
     //
   }else{

     $tbldomainpricing["id"] = insert_query("tbldomainpricing",array("extension" => $key));
   }
    //replace or add pricing for domainregister
    $result = mysql_query("SELECT * FROM tblpricing WHERE type='domainregister' AND currency=".$currency_id." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
    $tblpricing = mysql_fetch_array($result);
    if(!empty($tblpricing)){
      update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][0]), array("id" => $tblpricing["id"]));
    }else{
      insert_query("tblpricing",array("type" => "domainregister", "currency" => $currency_id, relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][0], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
    }

    //replace or add pricing for domaintransfer
		$result = mysql_query("SELECT * FROM tblpricing WHERE type='domaintransfer' AND currency=".$currency_id." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
		$tblpricing = mysql_fetch_array($result);
		if(!empty($tblpricing)){
			update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][1]), array("id" => $tblpricing["id"]));
		}else{
			insert_query("tblpricing",array("type" => "domaintransfer", "currency" => $currency_id, relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][1], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
		}

    //replace or add pricing for domainrenew
		$result = mysql_query("SELECT * FROM tblpricing WHERE type='domainrenew' AND currency=".$currency_id." AND relid=".$tbldomainpricing["id"]." ORDER BY id DESC LIMIT 1");
		$tblpricing = mysql_fetch_array($result);
		if(!empty($tblpricing)){
			update_query("tblpricing",array("msetupfee"=> $prices_for_whmcs[$key][2]), array("id" => $tblpricing["id"]));
		}else{
			insert_query("tblpricing",array("type" => "domainrenew", "currency" => $currency_id, relid => $tbldomainpricing["id"], "msetupfee"=> $prices_for_whmcs[$key][2], "qsetupfee"=> "-1", "ssetupfee"=> "-1", "asetupfee"=> "-1", "bsetupfee"=> "-1", "monthly"=> "-1", "quarterly"=> "-1", "semiannually"=> "-1", "annually"=> "-1", "biennially"=> "-1"));
		}

  }
echo "<div class='infobox'><strong><span class='title'>Update successful!</span></strong><br>Your pricing list has been updated successfully.</div>";
}
