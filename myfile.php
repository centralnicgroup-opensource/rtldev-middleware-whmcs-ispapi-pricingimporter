//pricing importer code from april 18th - 9.38

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

function ispapidpi_output($vars)
{
  echo "<pre>";
  print_r($_POST);
  echo "</pre>";
  // if( isset($_GET["tab"]) )
  // {
  //     $tab = $_GET["tab"];
  // }
  // else
  // {
  //     $tab = 1;
  // }
	//echo 'HELLO';
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

  echo '<form method="POST">';
  // echo '<input type="hidden" name="tab" value="1">';
  echo "<select name ='Priceclasses'>";
  echo "<option selected='disabled selected'>Choose a price class</option>";

  foreach($checkAuthentication["PROPERTY"]["USERCLASS"] as $price_class)
  {
    echo "<option value=".$price_class.">".$price_class."</option>";
  }
  echo "</select>";
  echo "<input type='submit' name='submit' value='Get price class'/>";
  echo " ";
  echo "</form>";
  if($tab == "1")
  {
    echo '<form action="addonmodules.php?module=ispapidpi&tab=2" method="POST">';
    echo '<input type="hidden" name="tab" value="2">';
    if(isset($_POST['submit']))
    {
      $selected_price_class = $_POST['Priceclasses'];
      $command =  $command = array(
              "command" => "StatusUserClass",
              "userclass"=> $selected_price_class
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
      //avoid duplicates
      $tlds = array_unique($tlds);
      //collect tld, register, renew and transfer prices in an array
      $tld_data = array();
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
            $tld_data[$tld][]= $register_price;
            $tld_data[$tld][]= $register_price;
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
            $tld_data[$tld][]= $transfer_price;
          }
        }
      }
      $_SESSION["tlddata"]=$tld_data; //session variable for tld data (tld and prices)
      //create table
      echo "<br>";
      // echo '<form method="POST">';
      echo'<table border="1" cellpadding="4" width="100">';
      echo "<tr>";
      echo "<th>TLD</th>";
      echo "<th>Register</th>";
      echo "<th>Renew</th>";
      echo "<th>Transfer</th>";
      echo "</tr>";
      foreach ($tld_data as $tld => $value)
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
      echo "</table>";
      echo "<br>";
      echo "<br>";
      echo '<input type="submit" name="check-button" value="Get checked TLDs">';
  }
      echo "</form>";
}
  $gettld = [];
  if($tab == "2")
  {
    echo '<form action="addonmodules.php?module=ispapidpi&tab=3" method="POST">';
    echo '<input type="hidden" name="tab" value="3">';

  foreach($_POST['checkbox-tld'] as $checkbox_tld)
  {
    array_push($gettld, $checkbox_tld);
  }
  $gettld = array_flip($gettld);
  //get only prices for checked tlds ---> $gettld, $_SESSION["tlddata"]
  $collect_checked_tld_data = [];
  foreach($gettld as $key=>$value)
  {
    if(array_key_exists($key, $_SESSION["tlddata"]))
    {
      $tldprices = $_SESSION["tlddata"][$key];
      $collect_checked_tld_data[$key] =$tldprices;
    }
  }
  echo "<br>";
  //new table with checked tlds

  if(isset($_POST['check-button']))
  {
    // echo '<form method="POST">';
    if(isset($_POST['checkbox-tld']))
    {

      //create new table with checked tlds
      echo'<table border="1" cellpadding="4" width="100">';
      echo "<tr>";
      echo "<th>TLD</th>";
      echo "<th>Register</th>";
      echo "<th>Renew</th>";
      echo "<th>Transfer</th>";
      echo "</tr>";
      echo "<tr>";
      foreach($collect_checked_tld_data as $key=>$value)
      {
        echo "<tr>";
        echo "<td>".$key."</td>";
        // echo "<td><input type='checkbox' name='checkbox-tld[]' value='".$key."'>".$key."</input></td>";
        foreach ($value as $price)
        {
          echo "<td name='Myprices'>".$price."</td>";
        }
      }
      echo "</tr>";
      }
      else
      {
        echo "please select a tld<br>";
      }
      echo "</table>";
      echo "<br>";
      echo "<input type='integer' name='tomultiply'>";
      echo "<input type='submit' name='get-newprices' value='Get new prices'>";
      $_SESSION["checkedtlddata"] = $collect_checked_tld_data;

    }

  }
    echo "</form>";
    echo "<br>";
    $tlds_with_new_prices = [];
    if($tab == "3")
    {
      echo '<form action="addonmodules.php?module=ispapidpi&tab=4" method="POST">';
      echo '<input type="hidden" name="tab" value="4">';

    if(isset($_POST['get-newprices']))
    {
      echo'<table border="1" cellpadding="4" width="100">';
      echo "<tr>";
      echo "<th>TLD</th>";
      echo "<th>Register</th>";
      echo "<th>Renew</th>";
      echo "<th>Transfer</th>";
      echo "</tr>";
      echo "<tr>";
      foreach($_SESSION["checkedtlddata"] as $key=>$value)
      {
        echo "<tr>";
        echo "<td>".$key."</td>";
        // echo "<td><input type='checkbox' name='checkbox-tld[]' value='".$key."'>".$key."</input></td>";
        foreach ($value as $price)
        {
          echo "<td name='Myprices'>".$price."</td>";
        }
      }
      echo "</tr>";
      echo "</table>";
      echo "<br>";

      if(isset($_POST['tomultiply']))
      {
        $to_multiply = $_POST['tomultiply'];
        //this loop gives required data after multiplication
        foreach ($_SESSION["checkedtlddata"] as $key => $value)
        {
          foreach($value as $ky => $val)
          {
            $tlds_with_new_prices[$key][] = $val * $to_multiply;
          }
        }
        // echo '<form method="POST">';
        echo'<table border="1" cellpadding="4" width="100">';
        echo "<tr>";
        echo "<th>TLD</th>";
        echo "<th>Register</th>";
        echo "<th>Renew</th>";
        echo "<th>Transfer</th>";
        echo "</tr>";
        echo "<tr>";
        foreach($tlds_with_new_prices as $key => $value)
        {
          echo "<tr>";
          echo "<td>".$key."</td>";
          foreach($value as $newprice)
          {
            echo "<td name='Myprices' contenteditable='true'>".$newprice."</td>";
          }
        }
        echo "</tr>";
      }

    }
      echo "</table>";
      echo "<br>";
      // echo '<from action="addonmodules.php?module=ispapidpi&tab=3" method="POST">';
      // echo "<input type='integer' name='tomultiply'>";
      // echo "<input type='submit' name='get-newprices' value='Get new prices'>";
      // echo "</form>";
      echo "</form>";
    }  // echo '<pre>' . print_r($_SESSION, TRUE) . '</pre>';
}


############################################################################

























<script>
alert("ok?");
</script>;

//example code from anthony
<?php

if( isset($_GET["step"]) ){
    $step = $_GET["step"];
}else{
    $step = 1;
}

echo "Current Step: ".$step;
echo "<br>";

if($step == "1"){
    echo '<form action="index.php?step=2" method="post">';
    echo '<input type="hidden" name="step" value="2">';
    echo '<input type="submit" name="go_to_2" value="go_to_2 (This is my simulated backend call)">';
    echo '</form>';
}
elseif( $step == "2" ){
        $myusers = array("andreas","uwe","daniel","sven","kai","david","roman","jorg","abbas","tulsi","anthony");
        echo '<form action="index.php?step=3" method="post">';
        foreach($myusers as $user){
            echo "<input type='checkbox' name='user[]' value='".$user."'>".$user."</input><br>";
        }
        echo '<input type="submit" name="go_to_3" value="go_to_3">';
        echo '</form>';
}
elseif( $step == "3" ){
        echo '<form action="index.php?step=3" method="post">';

        echo "<pre>";
        print_r( $_POST['user'] );
        echo "</pre>";

        echo '</form>';
}
