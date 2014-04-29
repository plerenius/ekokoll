<?php require_once("Connections/pdo_connect.php");

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

function parseFloat($balance)
{
	echo $balance;
	$balance = str_replace("[^0-9,.-]*", "",$balance);
    $balance = str_replace(" ", "",$balance);
    $balance = str_replace(".", "",$balance);
    $balance = str_replace(",", ".",$balance);
	echo " = ".$balance."<br />";
	return $balance;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=latin1" />
<title>Utgifter</title>
</head>
<body>
<h1>Utgifter</h1>

<p>
  <?php
$head=true;
echo "<table>";
$postfix="2012";//"201402";
$owner="Petter";    $filename="file:///home/albicilla/Documents/Petter/ica/Faktura${postfix}.html";
$owner="Petter";    $filename="file:///home/albicilla/Documents/Petter/ica/Vardags${postfix}.html";
$owner="Gemensamt"; $filename="file:///home/albicilla/Documents/Petter/ica/Gemensamt${postfix}.html";
$owner="Gemensamt"; $filename="file:///home/albicilla/Documents/Petter/ica/Lgh${postfix}.html";


$result=file_get_contents($filename);
if ($result === false)
{
    echo "FEL!";
}

// If it's not already Latin1, convert to it
if (mb_detect_encoding($result, 'utf-8', true) === "UTF-8") {
  $result = mb_convert_encoding($result, 'latin1', 'utf-8');
  echo "Change encoding...";
  echo "<h3>Coding: ".mb_detect_encoding($result, 'latin1', true)."</h3>";
}
else
{
  echo "<h3>Coding: ".mb_detect_encoding($result, 'utf-8', true)."</h3>";
}

$preg_str="/<td class=\"first[^>]+>.*?(201.+?)<\/td>";//date
$preg_str.="\s*<td[^>]+>(.+?)<\/td>"; //text
$preg_str.="\s*<td[^>]+>[^<]+<\/td>"; //övrigt
$preg_str.="\s*<td[^>]+>[^<]+<\/td>"; //övrigt
$preg_str.="\s*<td[^>]+>(.+?)\skr<\/td>/is"; // cost
preg_match_all($preg_str,$result,$spendings);
$dates = $spendings[1];
$costs = $spendings[3];
//print_r($spendings);
if ($spendings[2] != null)
{
	if($owner != null)
	{
		echo "<h3>$filename</h3>\n";
		foreach($spendings[2] as $i => $store)
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
?>
<p>&nbsp;</p>
</body>
</html>

