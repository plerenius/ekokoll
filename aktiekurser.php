<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
require_once("Connections/pdo_connect.php");

$stocks_sql ="SELECT *,(CONCAT( DAY( sv.time ) , '/', MONTH( sv.time ))) AS date from p_ekon_stocks AS s ";
$stocks_sql.="LEFT JOIN p_ekon_stockvalues AS sv ON sv.stock_id = s.id ";
$stocks_sql.="ORDER BY sv.time DESC LIMIT 5";

$statement_1 = $db->query($stocks_sql);
if(!$statement_1)
{
  die("Execute query error, because: ". $db->errorInfo());
}

$list_sql="SELECT DATE_FORMAT(sv.time,'%d/%m') AS date, s.name, (sh.nofstocks * sv.value) AS worth ";
$list_sql.="FROM p_ekon_stocks AS s ";
$list_sql.="LEFT JOIN p_ekon_stockholdings AS sh ON s.id = sh.stock_id ";
$list_sql.="LEFT JOIN (SELECT * FROM p_ekon_stockvalues WHERE DATE_FORMAT(time,'%Y-%m-%d')>DATE_FORMAT(NOW()-INTERVAL 1 MONTH,'%Y-%m-%d'))  ";
$list_sql.="AS sv ON s.id = sv.stock_id ";
$list_sql.="ORDER BY DATE(sv.time), s.id";
$statement_2 = $db->query($list_sql);
if(!$statement_2)
{
  die("Execute query error, because: ". $db->errorInfo());
}


$companies="SELECT name FROM p_ekon_stocks ORDER BY id";
$statement_3 = $db->query($companies);
if(!$statement_3)
{
  die("Execute query error, because: ". $db->errorInfo());
}
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Aktiekurser</title>
<link href="ekon_style.css" rel="stylesheet" type="text/css" />
</head>
<body>

<h1>Aktiekurs</h1>

<?php
echo "<table>";


while($nt=$statement_1->fetchObject()){
    echo "<tr>\n";
    echo "<th align=left>$nt->date</th>\n";
    echo "<th align=left>$nt->name</th>\n";
    echo "<td align=right width=100>".number_format($nt->value,2,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->high,2,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->low,2,'.',' ')."</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<table cellspacing=10>\n";
echo "<tr>\n";
echo "<th>&nbsp;</th>\n";
while($nt=$statement_3->fetchObject()){
  echo "<th>$nt->name</th>\n";
}
echo "<th>Totalt</th>\n";

$oldDate="";
$max = 0;
$ant = 0;
$min = 1000000;
$tot_summa = 0;

while($nt=$statement_2->fetchObject()){
  if ($nt->date != $oldDate) // If new date
  {
    if($oldDate != "") // Not first lap
    {
      echo "<th align='right'>".number_format($total,2,'.',' ')."</th>\n";
      if ($max < $total)
      {
        $max = $total;
        $max_date = $oldDate;
      }
      if ($min > $total)
      {
        $min = $total;
        $min_date = $oldDate;
      }
      $tot_summa += $total;
      $ant += 1;
    }
    echo "</tr>\n";
    echo "<tr>\n";
    $total=0;
    echo "<th align='right'>$nt->date</th>\n";
  }
  echo "<td align='right'>".number_format($nt->worth,2,'.',' ')."</td>\n";
  $total += $nt->worth;
  $oldDate=$nt->date;
}
echo "<th align='right'>".number_format($total,2,'.',' ')."</th>\n";
echo "</tr>\n";
echo "</table>\n";
if ($max < $total)
{
  $max = $total;
  $max_date = $oldDate;
}
if ($min > $total)
{
  $min = $total;
  $min_date = $oldDate;
}
$tot_summa += $total;
$ant += 1;
echo "<p>H&ouml;gsta summan ".number_format($max,2,'.',' ')." uppm&auml;ttes den $max_date</p>\n";
echo "<p>L&auml;gsta summan ".number_format($min,2,'.',' ')." uppm&auml;ttes den $min_date</p>\n";
echo "<p>Medelsumma: ".number_format(($tot_summa/$ant),2,'.',' ')."</p>\n";
?>
</body>
</html>
