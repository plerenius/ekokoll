<?php
require_once("Connections/localhost_lerenius.php");
require_once("Connections/pdo_connect.php");
require_once("../jpgraph/src/jpgraph.php");

// Utgiftsöversikt
$query_Period = "SELECT DISTINCT DATE_FORMAT(date,'%Y%m') AS Period ";
$query_Period .= "FROM p_econ_costs ";
$query_Period .= "WHERE DATE_FORMAT(date,'%Y%m')>=DATE_FORMAT(NOW()-INTERVAL 1 YEAR,'%Y%m') ";
$query_Period .= "ORDER BY Period ASC";
$Period = $db->query($query_Period);
if(!$Period)
{
  $err_str = "<body><p><h2>Execute query_Period query error, because: ". $db->errorInfo()."</h2></p>\n";
  $err_str .= "<p>SQL:<br />".$query_Period."</p></body>";
  die($err_str);
}

$query_cost="SELECT ";
while($row_Period = $Period->fetchObject()) {
  $query_cost.="SUM(if(DATE_FORMAT(date,'%Y%m')=".$row_Period->Period.",cost,0)) AS `".$row_Period->Period."`, ";
  $costHeadings[]=$row_Period->Period;
}
$query_cost.="MainCat.name AS Category ";
$query_cost.="FROM p_econ_costs ";
$query_cost.="INNER JOIN p_econ_categories AS cat ON cat.id = categories_id ";
$query_cost.=" AND categories_id > 0 AND $user AND $cat_id ";
$query_cost.="INNER JOIN  p_econ_categories AS MainCat ON cat.parent_id = MainCat.id ";
$query_cost.="GROUP BY Category, users_id ";
$query_cost.="ORDER BY MainCat.sort_order";
echo "<p>$query_cost</p>";

?>