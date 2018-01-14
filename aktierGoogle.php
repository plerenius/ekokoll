<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
require_once('Connections/localhost_lerenius.php');
require_once("Connections/pdo_connect.php");

function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;
  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = strtr($theValue,',','.');
      $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

$stocks_sql ="SELECT s.id, s.name, s.shortname, sv.value FROM p_ekon_stocks AS s";
$stocks_sql.=" LEFT JOIN (SELECT * FROM p_ekon_stockvalues WHERE DATE(`time`)=DATE(NOW())) AS sv ON sv.stock_id = s.id";
$stocks_sql.=" WHERE ISNULL(sv.value) AND s.market='NYSE' ORDER BY name";
$statement_1 = $db->query($stocks_sql);
if(!$statement_1)
{
  die("Execute query error, because: ". $db->errorInfo());
}
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Aktiekurser Google</title>
<link href="biscaya.css" rel="stylesheet" type="text/css" />
</head>
<body>
<h1>Aktiekurs Google</h1>
<?php
if (date("H") < 1) {
 echo "<p><strong>\n";
 echo "Klockan &auml;r " . date("H:i") . "\n<br />\n";
 echo "Det &auml;r f&ouml;r tidigt f&ouml;r att titta p&aring; kursen idag. V&auml;nta till efter klockan 22.\n";
 echo "</strong></p>\n";
}
else
{
  while($stock=$statement_1->fetchObject()) {
	$link="https://finance.google.com/finance?q=$stock->shortname";
    $result=file_get_contents($link);
	
    if ($result === false) {
      echo "FEL vid h&auml;mtning!";
	  continue;
    }

    echo "<p>Parsing: $stock->name <br />";
	echo "<a href='$link'>$link</a> <br />";
    preg_match('@<span class="pr">\s*<span[^>]*>([0-9.]*)@',$result,$matches);
    $value=$matches[1];
	preg_match('@<span class="ch bld">\s*<span[^>]*>([0-9.+-]*)@',$result,$matches);
	$diff = $matches[1];
	preg_match('@Range\s*</td>\s*<td[^>]*>([0-9.]*) - ([0-9.]*)@i',$result,$matches);
	$low=$matches[1];
	$high=$matches[2];
	preg_match('@Vol\s*/\s*Avg.\s*</td>\s*<td[^>]*>([0-9.]*)@i',$result,$matches);
    $volume=$matches[1];
	
    echo "Name:    $stock->shortname<br />";
    echo "Volume:  $volume<br />";
    echo "High:    $high<br />";
    echo "Low:     $low<br />";
    echo "Close:   $value<br />";
    echo "Net Chg: $diff<br />";
    $insertSQL = sprintf("INSERT INTO p_ekon_stockvalues (`time`,stock_id,value,high,low,`diff`,volume) VALUES (NOW(), %s, %s, %s, %s, %s, %s)",
                         GetSQLValueString($stock->id, "text"),
                         GetSQLValueString($value, "double"),
                         GetSQLValueString($high, "double"),
                         GetSQLValueString($low, "double"),
                         GetSQLValueString($diff, "double"),
                         GetSQLValueString($volume, "double"));
    echo "SQL: $insertSQL</p>";
    mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
    $Result1 = mysql_query($insertSQL, $localhost_lerenius) or die(mysql_error());
  }
}
?>
</body>
</html>