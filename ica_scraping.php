<?php
require_once("Connections/pdo_connect.php");

$stmt = $db->prepare("SELECT category_id, comment FROM p_econ_shopcategories WHERE shop=?");
$reg_query = $db->prepare("SELECT id FROM p_econ_costs WHERE date=? AND cost=?");
$accOwnerQuery = $db->prepare("SELECT users_id FROM p_econ_accounts WHERE accNr=?");
$addCostQuery = $db->prepare("INSERT INTO `p_econ_costs` (`cost`, `date`, `comment`, `users_id`, `categories_id`) VALUES (?,?,?,?,?);");

function isAlreadyRegistered($reg_query,$date,$cost)
{
  try
  {
	echo "Check if exist: $date -> $cost<br />\n";
    $reg_query->bindValue(1, $date . " 00:00:00", PDO::PARAM_STR);
    $reg_query->bindValue(2, $cost, PDO::PARAM_STR);
    $reg_query->execute();
	$rows = $reg_query->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) { echo "Found: " . $row['id'] . "<br />\n";}
  }
  catch (PDOException $ex)
  {
    //Something went wrong rollback!
    echo $ex->getMessage();
  }
  return ($rows != null);
}

function findCatId($stmt,$store)
{
	try
    {
		$stmt->bindValue(1, $store, PDO::PARAM_STR);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if ($rows != null)
		{
			$cat['id'] = $rows[0]['category_id'];
			$cat['comment'] = $rows[0]['comment'];
			return $cat;
		}
	}
	catch (PDOException $ex)
	{
		echo $ex->getMessage();
	}
	return null;
}

function findOwner($accOwnerQuery,$accId)
{
	try
    {
		$accOwnerQuery->bindValue(1, intval($accId), PDO::PARAM_STR);
		$accOwnerQuery->execute();
		$rows = $accOwnerQuery->fetchAll(PDO::FETCH_ASSOC);
		if ($rows != null)
		{
			return $rows[0]['users_id'];
		}
	}
	catch (PDOException $ex)
	{
		echo $ex->getMessage();
	}
	return null;
}

function addCost($stmt,$owner,$date,$cost,$cat)
{
	try
    {
		$stmt->bindValue(1, $cost);
		$stmt->bindValue(2, $date . " 00:00:00");
		$stmt->bindValue(3, $cat['comment']);
		$stmt->bindValue(4, $owner);
		$stmt->bindValue(5, $cat['id']);
		$stmt->execute();
		echo "ADD: $owner, ". $date . " 00:00:00, $cost, ". $cat['id'] .", ".$cat['comment'] ." <br />\n";
	}
	catch (PDOException $ex)
	{
		echo $ex->getMessage();
	}
}
// -----------------  Fetch costs  -----------------------------------------------
$urlStart = "https://mobil2.icabanken.se/login/login.aspx";
$urlService = "https://mobil2.icabanken.se/services/services.aspx";
$urlOverview = "https://mobil2.icabanken.se/account/overview.aspx";
$urlAccount = "https://mobil2.icabanken.se/account/account.aspx";
$urlLogOut = "https://mobil2.icabanken.se/logout/logout.aspx";
$username="7907030295";
$password="5105";
$ckfile = "cookies.txt";
$defaultOpt = array(
	CURLOPT_URL => $urlStart,
	CURLOPT_HEADER => TRUE,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_COOKIESESSION => true,
	CURLOPT_COOKIEFILE => $ckfile,
	CURLOPT_COOKIEJAR => $ckfile);
	
// Is set in php.ini for compliance with one.com
//CURLOPT_CAINFO => "c:\wamp\cacert.crt",

function parseFloat($balance)
{
	$balance = str_replace("[^0-9,.-]*", "",$balance);
    $balance = str_replace(".", "",$balance);
    $balance = str_replace(",", ".",$balance);
	return $balance;
}

// Get seeds
$ch = curl_init( );
curl_setopt_array($ch, $defaultOpt); 
curl_setopt($ch, CURLOPT_URL, $urlStart); 
$data1 = curl_exec($ch);
if(curl_errno($ch)){
    echo 'Curl get seeds error: ' . curl_error($ch);
}

// Viewstate
preg_match("/__VIEWSTATE\"\\s+value=\"([^\"]+)\"/",$data1,$viewstate);
// Event validation
preg_match("/__EVENTVALIDATION\"\\s+value=\"([^\"]+)\"/",$data1,$eventValidation);

// Login
$fields = array('pnr_phone'=>$username,'pwd_phone' => $password,'btnLogin' => "Logga in",'__VIEWSTATE' => $viewstate[1],'__EVENTVALIDATION' => $eventValidation[1]);
$encFields = http_build_query($fields);
curl_setopt($ch, CURLOPT_URL, $urlStart); 
curl_setopt($ch, CURLOPT_POST, count($fields));
curl_setopt($ch, CURLOPT_POSTFIELDS, $encFields);
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
$data_loc = curl_exec($ch);
if(curl_errno($ch)){
    echo 'Curl login error: ' . curl_error($ch);
}
preg_match("/Location:\\s(.*)\r\n/i",$data_loc,$header_loc);
$redirUrl = "https://mobil2.icabanken.se".$header_loc[1];

curl_setopt($ch, CURLOPT_URL, $redirUrl);
curl_setopt($ch, CURLOPT_HTTPGET, true);
$data2 = curl_exec($ch);
if(curl_errno($ch)){
    echo 'Curl follow location error: ' . curl_error($ch);
}

// Get account values and ids
curl_setopt($ch, CURLOPT_URL, $urlOverview); 
$data3 = curl_exec($ch);
if(curl_errno($ch)){
    echo 'Curl get accounts error: ' . curl_error($ch);
}
//$fp = fopen("overview.txt","w");
//fwrite($fp, $data3);
//fclose($fp);
//$data3=file_get_contents("file:///C:/wamp/www/ekonomi/overview.txt");
preg_match_all("/account\\.aspx\\?id=([^\"]+).+?>([^<]+).+?Saldo([0-9 .,-]+)/i",$data3,$accountsSum);
$accountIds    = $accountsSum[1];
$accountNames  = $accountsSum[2];
$accountValues = $accountsSum[3];

// Fetch all data from accounts pages
foreach($accountIds as $value => $id)
{
	curl_setopt($ch, CURLOPT_URL, $urlAccount."?id=".$id);
	$data4[$value] = curl_exec($ch);
	if(curl_errno($ch)){
	    echo 'Curl account $id error: ' . curl_error($ch);
	}
	//echo "<hr /><h1>$accountNames[$value] ($id): ". $accountValues[$value] . "</h1>\n";
	//echo "<h4>$urlAccount?id=$id</h4>";	
	//echo "<p>$data4[value]</p>\n";
	//$fp = fopen("acc_".$id.".txt","w");
    //fwrite($fp, $data4[$value]);
	//fclose($fp);
}

// Log out
curl_setopt($ch, CURLOPT_URL, $urlLogOut);
$dataLogOut = curl_exec($ch);
if(curl_errno($ch)){
    echo 'Curl logout error: ' . curl_error($ch);
}

// Close handler
curl_close($ch);


// Find spendings
/*
$data4[0]=file_get_contents("file:///C:/wamp/www/ekonomi/acc_0000100578.txt");
$accountNames[0]="file:///C:/wamp/www/ekonomi/ac_c0000100578.txt";
$data4[1]=file_get_contents("file:///C:/wamp/www/ekonomi/acc_0000200574.txt");
$accountNames[1]="file:///C:/wamp/www/ekonomi/acc_0000200574.txt";
$data4[2]=file_get_contents("file:///C:/wamp/www/ekonomi/acc_0000300577.txt");
$accountNames[2]="file:///C:/wamp/www/ekonomi/acc_0000300577.txt";
$data4[3]=file_get_contents("file:///C:/wamp/www/ekonomi/acc_0000400568.txt");
$accountNames[3]="file:///C:/wamp/www/ekonomi/acc_0000400568.txt";
$data4[4]=file_get_contents("file:///C:/wamp/www/ekonomi/acc_0000500578.txt");
$accountNames[4]="file:///C:/wamp/www/ekonomi/acc_0000500578.txt";
$accountValues=array(0,1,2,3,4,5);
$accountIds=array("0000100578","0000200574","0000300577","0000400568","0000500578");
*/

foreach($data4 as $index => $accData)
{
	preg_match_all("/<label>(.+?)<\/label>\\s*<[^>]+>-\\s*Datum\\s*(.+?)<\/div>\\s*<[^>]+>-\\s*Belopp(.+?)</is",$accData,$spendings);
	$dates = $spendings[2];
	$costs = $spendings[3];
	if ($spendings[1] != null)
	{
		$owner=findOwner($accOwnerQuery,$accountIds[$index]);
		if($owner != null)
		{
			echo "<h3>$owner - $accountNames[$index] (".parseFloat($accountValues[$index]).")</h3>\n";
			foreach($spendings[1] as $i => $store)
			{
				$cost = -parseFloat($costs[$i]);
				if ($cost > 0 && !isAlreadyRegistered($reg_query,$dates[$i],$cost))
				{
					$category = findCatId($stmt, $store);
					if ($category['id'] == null)
					{
						$category['id']=-2;
						$category['comment'] = $store;
					}
					echo $dates[$i] . " " . $store . " $cost  - " . $category['comment'] . "<br />\n";
					addCost($addCostQuery,$owner,$dates[$i],$cost,$category);
				}
			}
		}
	}
}
?> 