<?php
//header ("Content-type: image/jpg");

require_once("Connections/pdo_connect.php");
// include "Connections/localhost_lerenius.php";
require_once("../jpgraph/src/jpgraph.php");

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

$a_sql="SELECT id FROM p_ekon_stocks WHERE active>0";
$a_list=$db->query($a_sql);
if (!$a_list) {
  die("Execute query error, because: ". $db->errorInfo());
}

$list_sql="SELECT DATE_FORMAT(time,'%Y-%m-%d') AS time, SUM(sh.nofstocks * sv.value) AS sum, ";
$list_sql.="s.name AS name ";
$list_sql.="FROM p_ekon_stocks AS s ";
$list_sql.="LEFT JOIN p_ekon_stockholdings AS sh ON s.id = sh.stock_id ";
$list_sql.="LEFT JOIN (SELECT * FROM p_ekon_stockvalues WHERE DATE_FORMAT(time,'%Y-%m-%d')>DATE_FORMAT(NOW()-INTERVAL 3 MONTH,'%Y-%m-%d')) ";
$list_sql.="AS sv ON s.id = sv.stock_id ";
$list_sql.="WHERE s.id=? ";
$list_sql.="GROUP BY DATE(sv.time), s.id ";
$list_sql.="ORDER BY DATE(sv.time)";
$a_q=$db->prepare($list_sql);

if (isset($_GET['a_id'])) {
  $aktie_id = $_GET['a_id'];
} else {
  while($al=$a_list->fetchObject()) {
    $aktie_id[] = $al->id;
  }
}

foreach ($aktie_id as $id) {
   $a_q->execute(array($id));
   //$a_q->debugDumpParams();

   if (!$a_q) {
     die("Execute query error, because: ". $db->errorInfo());
   }
   $data=array();
   $datax=array();
   $old_month=10;
   $first=1;
   while($sv=$a_q->fetchObject())
   {
	   if ($first==1) {
		   $base=$sv->sum;
		   $first=0;
	   }
      $data[]=$sv->sum/$base;
      $stockName=$sv->name;
      if($old_month++ == 10) {
        $datax[]=$sv->time;
        $old_month=0;
      } else {
        $datax[]="";
      }
      //echo $sv->time.": ".$sv->sum."<br>\n";
   }
   //print_r($sv);

   $line[$id] = new LinePlot($data);
   $graph->Add($line[$id]);
   $line[$id]->SetLegend($stockName);

}
//$line->SetColor('darkolivegreen');
//$line2 = new LinePlot($data['sum_high']);
//$line2->SetColor('grren');
//$line3 = new LinePlot($data['sum_low']);
//$line3->SetColor('red');
//$graph->Add($line2);
//$graph->Add($line3);

// Setup the titles
$graph->title->Set("Aktier");
$graph->xaxis->title->Set("Month");
$graph->yaxis->title->Set("SEK");

$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->xaxis->SetTickLabels($datax);

$graph->Stroke();
//print "<p><img src='{$jpgcache}{$graph_name}'>\n";

?>