<?php
require_once("Connections/localhost_lerenius.php");
require_once("Connections/pdo_connect.php");
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
  if ($_GET['acc_cat'] < 0) {
    $acc_cat = "Acc.accCat_id<4";
  } else {
    $acc_cat = "Acc.accCat_id=".$_GET['acc_cat'];
  }
} else {
  $acc_cat = "1";
}

// Utgiftsöversikt
$query_acc = "SELECT DISTINCT accCat_id,users_id AS Owner FROM p_econ_accounts WHERE $user AND $acc_id ORDER BY users_id";
$Acc = $db->query($query_acc);
if(!$Acc)
{
  $err_str = "<body><p><h2>Execute query_acc query error, because: ". $db->errorInfo()."</h2></p>\n";
  $err_str .= "<p>SQL:<br />".$query_acc."</p></body>";
  die($err_str);
}
$old_owner = "";

// Skapa månadsfråga
//Skapa Aktiedata
$stock_sql="SELECT DATE_FORMAT(sv.time,'%Y-%m-%d') AS date, ";
$stock_sql.="SUM(sh.nofstocks * sv.value) AS `acc_Petter_Stocks` ";
$stock_sql.="FROM ";
$stock_sql.="( ";
$stock_sql.="  SELECT * FROM p_ekon_stockvalues AS sv1 ";
$stock_sql.="  JOIN ( ";
$stock_sql.="    SELECT MAX(time) AS time2 ";
$stock_sql.="    FROM `p_ekon_stockvalues` AS x ";
$stock_sql.="    GROUP BY YEAR(time), MONTH(time) ";
$stock_sql.="  ) AS sv2 ON DATE_FORMAT( sv1.time,  '%Y-%m-%d' ) = DATE_FORMAT( sv2.time2,  '%Y-%m-%d' ) ";
$stock_sql.=") AS sv ";
$stock_sql.="LEFT JOIN p_ekon_stockholdings AS sh ON sv.stock_id = sh.stock_id ";
$stock_sql.="GROUP BY DATE(sv.time) ";
$stock_sql.="ORDER BY DATE(sv.time) ";
$accs[] = "acc_Petter_Stocks";

$query_accSum = "SELECT * FROM (";
$query_accSum .= "SELECT ";
while($row_accounts = $Acc->fetchObject())
{
  if($old_owner!=$row_accounts->Owner)
  {
    $old_owner=$row_accounts->Owner;
    $query_accSum .= "SUM(if(Acc.users_id='$old_owner',AV.Value,0))+IF(Acc.users_id='Petter',acc_Petter_Stocks,0) AS Total_$old_owner, ";
	$accs[] = "Total_$old_owner";
  }
  $query_accSum .= "SUM(if(Acc.accCat_id=$row_accounts->accCat_id AND Acc.users_id='$old_owner',AV.Value,0)) AS `acc_${old_owner}_$row_accounts->accCat_id`, ";
  $accs[] = "acc_${old_owner}_$row_accounts->accCat_id";
}
$query_accSum .= " DATE_FORMAT(AV.date,'%y-%m') AS Datum, ";
$query_accSum .= " Acc.accCat_id AS accCat ";
$query_accSum .= "FROM `p_econ_accountvalues` AS AV ";
$query_accSum .= "LEFT JOIN p_econ_accounts AS Acc ON AV.accounts_id = Acc.id ";
$query_accSum .= "LEFT JOIN ($stock_sql) AS Stocks ON DATE_FORMAT(AV.date,'%Y%m')=DATE_FORMAT(Stocks.date,'%Y%m') ";
$query_accSum .= "WHERE $user AND $acc_cat AND $acc_id ";
//$query_accSum .= "AND DATE_FORMAT(AV.date,'%Y%m')>=DATE_FORMAT(NOW()-INTERVAL 2 YEAR,'%Y%m') ";
$query_accSum .= "GROUP BY AV.date ";
$query_accSum .= "ORDER BY AV.date) AS A ";
$query_accSum .= "LEFT JOIN ($stock_sql) AS Stocks ON A.DATUM=DATE_FORMAT(Stocks.date,'%y-%m') ";
try{
  mysql_query("SET SQL_BIG_SELECTS=1", $localhost_lerenius) or die(mysql_error());
  $qt=mysql_query($query_accSum, $localhost_lerenius) or die(mysql_error());
} catch (PDOException $err) {
  echo "<p>".$err->getMessage()."</p>";
}
if(!$qt)
{
  $err=$db->errorInfo();
  $err_str = "<body><p><h2>Execute query_accSum query error, because:</h2><br />";
  $err_str .= $err[2]."</p>\n";
  $err_str .= "<p>SQL:<br />".$query_acc."</p></body>";
  die($err_str);
}

$old_month=5;
while($nt=mysql_fetch_array($qt))
{
  foreach($accs as $acc)
  {
    $data[$acc][]=$nt[$acc];
    $accCat[$acc]=$acc;
  }
  if($old_month++ == 5) {
    $datax[]=$nt['Datum'];
    $old_month=0;
  } else {
    $datax[]="";
  }
}

require_once('../jpgraph/src/jpgraph_line.php');

$jpgcache = "Test/";

$graph_name = 'chart.png';
//$graph = new graph(500, 200, $graph_name, 0, 0);
$graph = new graph(1024, 800);
$graph->img->SetMargin(100, 50, 50, 50);    
$graph->SetScale('textlin');
foreach($accs as $acc)
{
	//echo "$acc=$data[$acc][0]<br>\n";
	$line[$acc] = new LinePlot($data[$acc]);
        $line[$acc]->SetLegend($accCat[$acc]);
	$line[$acc]->SetColor('darkolivegreen');
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

