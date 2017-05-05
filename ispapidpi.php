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
    return $configarray;
}


function ispapidpi_activate() {
	return array('status'=>'success','description'=>'Installed');
}

function ispapidpi_deactivate() {
	return array('status'=>'success','description'=>'Uninstalled');
}
//this function is called based on the user selection --> selection of price class or to use defualt hexonet costs and creates an array
function collect_tld_register_transfer_renew_currency($priceclass_or_defaultcost){
  //smarty template for table in step 2
  $smarty = new Smarty;
  $smarty->compile_dir = $GLOBALS['templates_compiledir'];
  $smarty->caching = false;

  $pattern_for_tld = "/PRICE_CLASS_DOMAIN_([^_]+)_/";
  $tlds = [];
  foreach($priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"] as $key => $value){
    if(preg_match($pattern_for_tld,$value,$match)){
      $tlds[] = $match[1];
    }
  }
  //remove duplicates of tlds
  $tlds = array_unique($tlds);
  //collect register, renew and transfer prices and currency for each tld in an array
  $tld_register_renew_transfer_currency = array();
  foreach ($tlds as $key => $tld){
    $pattern_for_registerprice ="/PRICE_CLASS_DOMAIN_".$tld."_ANNUAL$/";
    $register_match = preg_grep($pattern_for_registerprice, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
    $register_match_keys = array_keys($register_match);
    // $tld_data[] = $tld;
    foreach ($register_match_keys as $key){
      if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"])){
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
    foreach ($transfer_match_keys as $key){
      if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"])){
        //values of the keys
        $transfer_price =  $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
        $tld_register_renew_transfer_currency[$tld]['transfer']= $transfer_price;
      }
    }
    //get tld currency
    $pattern_for_currency = "/PRICE_CLASS_DOMAIN_".$tld."_CURRENCY$/";
    $currency_match = preg_grep($pattern_for_currency, $priceclass_or_defaultcost["PROPERTY"]["RELATIONTYPE"]);
    $currency_match_keys= array_keys($currency_match);
    foreach($currency_match_keys as $key){
      if(array_key_exists($key, $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"])){
        $tld_currency = $priceclass_or_defaultcost["PROPERTY"]["RELATIONVALUE"][$key];
        $tld_register_renew_transfer_currency[$tld]['currency'] = $tld_currency;
      }
    }
  }
  //filter tlds that are with currency USD
  $tld_register_renew_transfer_currency_filter = filter_array($tld_register_renew_transfer_currency,'USD');

  $tld_register_renew_transfer_currency_filter =  array_change_key_case($tld_register_renew_transfer_currency_filter, CASE_LOWER);
  $_SESSION["tld-register-renew-transfer-currency-filter"]=$tld_register_renew_transfer_currency_filter; //session variable for tld data (tld and prices ,currency)

  $smarty->assign('tld_register_renew_transfer_currency_filter', $tld_register_renew_transfer_currency_filter);
  $smarty->display(dirname(__FILE__).'/templates/talbeInStep2.tpl');
 }

function ispapidpi_output($vars){
  // echo "sessions";
  // echo "<pre>";print_r($_SESSION);echo "</pre>";

  //smarty template
  $smarty = new Smarty;
  $smarty->compile_dir = $GLOBALS['templates_compiledir'];
  $smarty->caching = false;

  // echo"<pre>";print_r($_POST);echo"</pre>";
  //for css
  echo '<style>';
  include 'css/styles.css';
  echo '</style>';

  $file = "ispapi";
  require_once(dirname(__FILE__)."/../../../includes/registrarfunctions.php");
    require_once(dirname(__FILE__)."/../../../modules/registrars/".$file."/".$file.".php");
  $registrarconfigoptions = getregistrarconfigoptions($file);
  $ispapi_config = ispapi_config($registrarconfigoptions);
  $command =  $command = array(
          "command" => "queryuserclasslist"
  );
  $queryuserclasslist = ispapi_call($command, $ispapi_config);
  $smarty->assign('queryuserclasslist', $queryuserclasslist);

  if(isset($_POST['checkbox-tld']) || (isset($_SESSION["checkbox-tld"]) && isset($_POST['multiplier']))){
    //Step 3
    if(isset($_POST['checkbox-tld'])){
        $_SESSION["checkbox-tld"] = $_POST["checkbox-tld"];
    }
    if(isset($_POST['multiplier'])){
        $multiplier = $_POST['multiplier'];
    }
    else{
        $multiplier = 1.00;
    }

    $smarty->assign('session-price-class', $_SESSION["price_class"]);

    //get checked TLD then get register,renew and transfer prices for that TLD
    $get_tld = [];
    foreach($_SESSION["checkbox-tld"] as $checkbox_tld){
      array_push($get_tld, $checkbox_tld);
    }
    $get_tld = array_flip($get_tld);

    $get_checked_tld_data = [];
    foreach($get_tld as $key=>$value){
      if(array_key_exists($key, $_SESSION["tld-register-renew-transfer-currency-filter"])){
        $tld_prices = $_SESSION["tld-register-renew-transfer-currency-filter"][$key];
        $get_checked_tld_data[$key]=$tld_prices;
      }
    }
    //csv file array
    foreach($get_tld as $key=>$value){
      if(array_key_exists($key, $_SESSION["csv-as-new-array"])){
        $tld_prices = $_SESSION["csv-as-new-array"][$key];
        $get_checked_tld_data[$key]=$tld_prices;
      }
    }
    $_SESSION["checked_tld_data"] = $get_checked_tld_data;
    $_SESSION["checked_tld_data"] = array_change_key_case($_SESSION["checked_tld_data"], CASE_LOWER);

    foreach ($_SESSION["checked_tld_data"] as $key => $subArr){
      unset($subArr['currency']);
      $_SESSION["checked_tld_data"][$key] = $subArr;
    }

    $smarty->display(dirname(__FILE__).'/templates/tableInStep3.tpl');

    if(isset($_POST['multiplier'])){
      foreach($_SESSION["checked_tld_data"] as $key=>$value){
        echo '<tr id="row">';
        echo '<td>'.'.'.$key.'</td>';
        foreach($value as $key2=>$old_and_new_price){
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
    }
    else{
      foreach($_SESSION["checked_tld_data"] as $key=>$value){
        echo '<tr>';
        echo '<td>'.'.'.$key.'</td>';
        foreach($value as $key2=>$price){
          echo "<td name='Myprices'>".$price."</td>";
          echo "<td><input type='text' name='PRICE_" . $key . "_" . $key2 . "' value='".$price."'></input></td>";
          // echo "<td name='Myprices'>".$price."</td>";
        }
       //can be a function
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
  elseif(isset($_POST['price_class'])){
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
    if($_POST['price_class'] == "DEFAULT_PRICE_CLASS"){
      $command =  $command = array(
          "command" => "StatusUser"
      );
      $default_costs = ispapi_call($command, $ispapi_config);

      collect_tld_register_transfer_renew_currency($default_costs);
    }
    //when csv file is slected
  elseif($_POST['price_class'] == "CSV-FILE"){
    // $_POST['price_class'] = $_SESSION["csv-as-new-array"];
    if ( isset($_FILES["file"])) {
      //if there was an error uploading the file
      if ($_FILES["file"]["error"] > 0) {
          echo "Return Code: " . $_FILES["file"]["error"] . " error uploading the file"."<br />";
      }
      else {
        $tmpName = $_FILES['file']['tmp_name'];
        $csvAsArray = array_map(function($d) {
            return str_getcsv($d, ";");
        }, file($tmpName));
        array_shift($csvAsArray); //remove first element (header part of the csv file)

        $csv_as_new_array = [];
        foreach($csvAsArray as $key=>$value){
          $newKey = "";
          foreach($value as $ky=>$val){
            if($ky == 0){
              //first element to take as new key
              $newKey = $val;
              $csv_as_new_array[$newKey] = [];
            }
            else{
              $csv_as_new_array[$newKey][] = $val;
            }
          }
        }
        //to change keys of above array to strings
        $keynames = array('register', 'renew', 'transfer');
        foreach($csv_as_new_array as $key=>$value){
          $csv_as_new_array[$key] = array_combine($keynames, array_values($csv_as_new_array[$key]));
        }
        $add_currency_to_array = array('currency'=>'USD');
        foreach($csv_as_new_array as $key=>$value){
          $csv_as_new_array[$key] = $csv_as_new_array[$key]+$add_currency_to_array;
        }
        $_SESSION["csv-as-new-array"] = $csv_as_new_array;
       }
      }
    elseif(isset($_SESSION["csv-as-new-array"]))	//!!!
    {
      //else for isset($_FILES["file"]), i.e. there is no file, but a session
      $csv_as_new_array = $_SESSION["csv-as-new-array"];
    }
    // $smarty->assign('csv_as_new_array', $csv_as_new_array);
    // $smarty->display(dirname(__FILE__).'/templates/csvtableInStep2.tpl');
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
    else{
      $command =  $command = array(
              "command" => "StatusUserClass",
              "userclass"=> $_POST['price_class']
      );
      $getdata_of_priceclass = ispapi_call($command, $ispapi_config);
      collect_tld_register_transfer_renew_currency($getdata_of_priceclass);
    }
  }
  else
  {
    // step 1
    $smarty->assign('queryuserclasslist_PROPERTY_USERCLASS', $queryuserclasslist["PROPERTY"]["USERCLASS"]);

    $smarty->display(dirname(__FILE__).'/templates/step1.tpl');
  }
  //select all checkboxes script
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
    $tld_new_price = [];
    foreach($_POST as $key=>$value){
      if(preg_match($prices_match_pattern, $key)){
        $tld_new_price[] = $value;
      }
    }
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
    foreach($new_prices_for_whmcs as $key=>$value){
      array_push($new_prices_for_whmcs[$key], $domain_addons);
    }
    //to merge each curreny value from currencies array new_prices_for_whmcs
    $i = -1;
    foreach($new_prices_for_whmcs as $key=>$value){
      $i++;
      $new_prices_for_whmcs[$key]['currency'] = $currencies['currency'][$i];
    }
    //import the data
    startimport($new_prices_for_whmcs);
  }
}
//helper functions
function array_combine_($keys, $values){
    $result = array();
    foreach ($keys as $i => $k) {
        $result[$k][] = $values[$i];
    }
    array_walk($result, create_function('&$v', '$v = (count($v) == 1)? array_pop($v): $v;'));
    return    $result;
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
//import button
// function startimport($tld_pricing)
function startimport($prices_for_whmcs){
  //loop through array and insert or update the tld and prices for whmcs to DB
  $prices_for_whmcs = array_change_key_case($prices_for_whmcs, CASE_LOWER);
  foreach($prices_for_whmcs as $key=>$value){
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
