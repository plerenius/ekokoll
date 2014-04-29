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

$stocks_sql ="SELECT s.id, s.name, sv.value FROM p_ekon_stocks AS s LEFT JOIN (SELECT * FROM p_ekon_stockvalues WHERE DATE(`time`)=DATE(NOW())) AS sv ON sv.stock_id = s.id WHERE ISNULL(sv.value) ORDER BY name";
$statement_1 = $db->query($stocks_sql);
if(!$statement_1)
{
  die("Execute query error, because: ". $db->errorInfo());
}

?>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Aktiekurser</title>
<link href="biscaya.css" rel="stylesheet" type="text/css" />
</head>
<body>

<h1>Aktiekurs</h1>

<?php
if (date("H") < 18) {
 echo "<p><strong>\n";
 echo "Klockan är " . date("H:i") . "\n<br />\n";
 echo "Det &auml;r f&ouml;r tidigt f&ouml;r att titta p&aring; kursen idag. V&auml;nta till efter klockan 18.\n";
 echo "</strong></p>\n";
}
else
{
//$aktie_ids[]=21401; //AZN
//$aktie_ids[]=23223; //ABB
//$aktie_ids[]=  219; //ATC
//$aktie_ids[]= 2599; //SHB
//$aktie_ids[]=  703; //HMB
while($stock=$statement_1->fetchObject()){
	$aktie_ids[]=$stock->id;
}

$first=true;
$head=true;
foreach($aktie_ids as $a_id) {
$head=true;

echo "<table>";

$result=file_get_contents("http://bors.affarsvarlden.se/afvbors.sv/site/mobile/stock/stock_detail.page?magic=%28cc%20%28tsid%20$a_id%29%29");
if ($result === false)
{
    echo "FEL!";
} else {
  $start_pos = strpos($result,'<h3');
  $end_pos = strpos($result,'</table')-$start_pos;
  $result = substr($result,$start_pos,$end_pos);
$dom = new DOMDocument;
$dom->loadHTML( $result );
$rowsh = array();
$rows = array();
$value = array();

foreach( $dom->getElementsByTagName( 'tr' ) as $tr ) {
    $type = array();
    $res = array();

if($head){
echo "</tr>\n<tr>\n";
$head=false;
echo '<th colspan=10 align=left>';
$c_list = $dom->getElementsByTagName( 'h3' );
$comp = $c_list->item( 0 )->nodeValue;
echo($comp);
echo "</th></tr>\n<tr>";
}
    foreach( $tr->getElementsByTagName( 'td' ) as $td ) {
        $res[] = $td->nodeValue;
echo "<td width=100>" . $res[0] . "</td>\n";
$value[]=$res[0];
    }
}

$insertSQL = sprintf("INSERT INTO p_ekon_stockvalues (`time`,stock_id,value,high,low,`diff`,volume) VALUES (NOW(), %s, %s, %s, %s, %s, %s)",
                       GetSQLValueString($a_id, "int"),
                       GetSQLValueString($value[4], "double"),
                       GetSQLValueString($value[5], "double"),
                       GetSQLValueString($value[6], "double"),
                       GetSQLValueString($value[0], "double"),
                       GetSQLValueString($value[7], "double"));
echo "<tr><td colspan=10>$insertSQL</td></tr>";

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$Result1 = mysql_query($insertSQL, $localhost_lerenius) or die(mysql_error());

}

}
}
?>

 </body>
</html>
