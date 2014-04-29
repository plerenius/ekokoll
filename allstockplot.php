<?php
include "Connections/localhost_lerenius.php";
require_once("../jpgraph/src/jpgraph.php");

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$list_sql="SELECT SUM(sh.nofstocks * sv.value) AS sum, ";
$list_sql.="SUM(sh.nofstocks * sv.high) AS sum_high, ";
$list_sql.="SUM(sh.nofstocks * sv.low) AS sum_low ";
$list_sql.="FROM p_ekon_stocks AS s ";
$list_sql.="LEFT JOIN p_ekon_stockholdings AS sh ON s.id = sh.stock_id ";
$list_sql.="LEFT JOIN p_ekon_stockvalues AS sv ON s.id = sv.stock_id ";
$list_sql.="GROUP BY DATE(time) ";
$list_sql.="ORDER BY DATE(sv.time), s.id";
$qt=mysql_query($list_sql, $localhost_lerenius) or die(mysql_error());
//header ("Content-type: image/jpg");


while($nt=mysql_fetch_array($qt)){
//echo "$nt[time], $nt[value]<br>\n";
$data['sum'][]=$nt['sum'];
$data['sum_high'][]=$nt['sum_high'];
$data['sum_low'][]=$nt['sum_low'];
}

// only include these if you are using each particular kind of graph
//require_once('jpgraph/jpgraph_bar.php');
require_once('../jpgraph/src/jpgraph_line.php');
//require_once('jpgraph/jpgraph_pie.php');
//require_once('jpgraph/jpgraph_spider.php');

$jpgcache = "Test/";

$graph_name = 'chart.png';
//$graph = new graph(500, 200, $graph_name, 0, 0);
$graph = new graph(1024, 600);
$graph->img->SetMargin(100, 50, 50, 50);    
$graph->SetScale('textlin');
$line1 = new LinePlot($data['sum']);
$line1->SetColor('darkolivegreen');
$line2 = new LinePlot($data['sum_high']);
$line2->SetColor('grren');
$line3 = new LinePlot($data['sum_low']);
$line3->SetColor('red');
$graph->Add($line1);
$graph->Add($line2);
$graph->Add($line3);
$graph->Stroke();
//print "<p><img src='{$jpgcache}{$graph_name}'>\n";
?>