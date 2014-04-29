<?php
include "Connections/localhost_lerenius.php";
require_once("Connections/pdo_connect.php");
require_once("../jpgraph/src/jpgraph.php");

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);

if (isset($_GET['cat_id'])) {
  $cat_id = "=".$_GET['c_id'];
} else {
  $cat_id = "1";
}
if (isset($_GET['user'])) {
  $user = "users_id=".$_GET['user'];
} else {
  $user = "1";
}
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
//echo "<p>$query_cost</p>";
try{
  $costs = $db->query($query_cost);
} catch (PDOException $err) {
  echo "<p>".$err->getMessage()."</p>";
}
if(!$costs)
{
  $err=$db->errorInfo();
  $err_str = "<body><p><h2>Execute query_cost query error, because:</h2><br />";
  $err_str .= $err[2]."</p>\n";
  $err_str .= "<p>SQL:<br />".$query_cost."</p></body>";
  die($err_str);
}

$result = $costs->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
  //print_r($row);
  $index=0;
  $datax[]=$row['Category'];
  foreach ($row as $value) {
    if(is_numeric($value)) {
		//echo "$index - ".number_format($value,0,'.',' ')." <br>";
        $data[$index++][]=$value;
      }
  }
}

require_once ('../jpgraph/src/jpgraph_bar.php');

// Create the graph. These two calls are always required
$graph = new Graph(1024,800,'auto');
$graph->SetScale("textlin");

$theme_class=new UniversalTheme;
$graph->SetTheme($theme_class);

//$graph->yaxis->SetTickPositions(array(0,1000,2000,3000,4000,5000), array(500,1500,2500,3500,4500,5500));
$graph->SetBox(false);

$graph->ygrid->SetFill(false);
$graph->xaxis->SetTickLabels($datax);
$graph->yaxis->HideLine(false);
$graph->yaxis->HideTicks(false,false);

// Create the bar plots
$index=0;
foreach ($data as $d) {
	$bplot[$index] = new BarPlot($data[$index]);
	$bplot[$index]->SetLegend($costHeadings[$index]);
	$index++;
}

// Create the grouped bar plot
$gbplot = new GroupBarPlot($bplot);
// ...and add it to the graPH
$graph->Add($gbplot);

// Adjust the legend position
$graph->legend->SetLayout(LEGEND_HOR);
//$graph->legend->Pos(0.4,0.95,"center","bottom");

// Title
$graph->title->Set("Kostnader ".$_GET['user']);
$graph->xaxis->title->Set("Kategori");
$graph->yaxis->title->Set("SEK");
 
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

// Legend

// Display the graph
$graph->Stroke();
?>