<?php
include "Connections/localhost_lerenius.php";
require_once("../jpgraph/src/jpgraph.php");

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);

if (isset($_GET['c_id'])) {
  $acc_id = "=".$_GET['c_id'];
} else {
  $acc_id = "1";
}
if (isset($_GET['user'])) {
  $user = "users_id=".$_GET['user'];
} else {
  $user = "1";
}
if (isset($_GET['acc_cat'])) {
  $acc_cat = "Acc.accCat_id=".$_GET['acc_cat'];
} else {
  $acc_cat = "1";
}

$query_accounts = "SELECT id,name,users_id AS Owner FROM p_econ_accounts WHERE $user AND $acc_id ORDER BY users_id";
$accounts = mysql_query($query_accounts, $localhost_lerenius) or die(mysql_error());
$old_owner = "Petter";
$accs = array();
$accsName[] = array();

$query_accSum = "SELECT Acc.users_id AS Owner,AV.date AS Datum, ";
while($row_accounts = mysql_fetch_assoc($accounts))
{
  if($old_owner!=$row_accounts['Owner'])
  {
    $old_owner=$row_accounts['Owner'];
    $query_accSum .= "SUM(if(Acc.users_id='$old_owner',AV.Value,0)) AS Total_$old_owner, ";
	$accs[] = "Total_$old_owner";
	$accsName[end(array_values($accs))] = "Total " . $row_accounts['name'];
  }
  $query_accSum .= "SUM(if(Acc.id=$row_accounts[id],AV.Value,0)) AS `acc_$row_accounts[id]`, ";
  $accs[] = "acc_$row_accounts[id]";
  $accsName[end(array_values($accs))] = $row_accounts['name'];
}
$query_accSum .= "SUM(AV.value) AS Summa ";
$accs[] = "Summa";
$accsName[end(array_values($accs))] = "Totalt";
$query_accSum .= "FROM `p_econ_accountvalues` AS AV ";
$query_accSum .= "LEFT JOIN p_econ_accounts AS Acc ON AV.accounts_id = Acc.id ";
$query_accSum .= " WHERE $user AND $acc_cat AND $acc_id GROUP BY AV.date";
$qt=mysql_query($query_accSum, $localhost_lerenius) or die(mysql_error());
//header ("Content-type: image/jpg");

//echo "$query_accSum <br>\n";
   $old_month=10;
while($nt=mysql_fetch_array($qt))
{
	foreach($accs as $acc)
	{
		$data[$acc][]=$nt[$acc];
		//echo "$acc=".$nt[$acc]."<br>\n";
	}
	if($old_month++ == 10)
	{
        $datax[]=$nt['Datum'];
        $old_month=0;
    } else {
        $datax[]="";
    }
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
foreach($accs as $acc)
{
	//echo "$acc=$data[$acc][0]<br>\n";
	$line[$acc] = new LinePlot($data[$acc]);
	$line[$acc]->SetColor('darkolivegreen');
	//echo $acc . ": " . $accsName[$acc];
	$line[$acc]->SetLegend($accsName[$acc]);
	$graph->Add($line[$acc]);
}
// Setup the titles
$graph->title->Set("Konton");
$graph->xaxis->title->Set("Month");
$graph->yaxis->title->Set("SEK");

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->SetTickLabels($datax);

//Draw!
$graph->Stroke();
//print "<p><img src='{$jpgcache}{$graph_name}'>\n";
?>