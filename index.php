<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php 
require_once("Connections/pdo_connect.php");
require_once("Connections/localhost_lerenius.php");
mysql_select_db($database_localhost_lerenius, $localhost_lerenius);

if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) {
    $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
  }

  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
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
}
$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
  $insertSQL = "INSERT INTO p_econ_costs (cost, `date`, `comment`, users_id, categories_id) ";
  $insertSQL .= sprintf("VALUES (%s, %s, %s, %s, %s)",
                       GetSQLValueString($_POST['cost'], "double"),
                       GetSQLValueString($_POST['date'], "date"),
                       GetSQLValueString($_POST['comment'], "text"),
                       GetSQLValueString($_POST['users_id'], "text"),
                       GetSQLValueString($_POST['categories_id'], "int"));

  $Result1 = mysql_query($insertSQL, $localhost_lerenius) or die(mysql_error());
  $cost_id = mysql_insert_id();

  printf("<p align='center'>\nLagt till: %s kr, %s , %s, %s, kategori #%s<br />\n",
         GetSQLValueString($_POST['cost'], "double"),
         GetSQLValueString($_POST['date'], "date"),
         GetSQLValueString($_POST['comment'], "text"),
         GetSQLValueString($_POST['users_id'], "text"),
         GetSQLValueString($_POST['categories_id'], "int"));

  if(!empty($_POST['labels_id']))
    {
      foreach ($_POST['labels_id'] AS $l)
        {
          $insertSQL = sprintf("INSERT INTO p_econ_labelcosts (labels_id, costs_id) VALUES (%s,%s)",
                               GetSQLValueString($l, "int"),
                               GetSQLValueString($cost_id, "int"));
          mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
          $Result2 = mysql_query($insertSQL, $localhost_lerenius) or die(mysql_error());
          printf("Lagt till labels # %s<br />\n", GetSQLValueString($l, "int"));
        }
    }
  printf("\n</p>\n");
}

$query_Users = "SELECT * FROM p_econ_users";
$Users = $db->query($query_Users);
$row_Users = $Users->fetchObject();
if(!$Users)
{
  $err_str = "<body><p><h2>Execute sum_sql query error, because: ". $db->errorInfo()."</h2></p>\n";
  $err_str .= "<p>SQL:<br />".$query_Users."</p></body>";
  die($err_str);
}

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_Categories = "SELECT m.name AS mainCat, c.name AS subCat, ";
$query_Categories .= "c.id AS subCatId FROM p_econ_categories AS c ";
$query_Categories .= "INNER JOIN p_econ_categories AS m ON c.parent_id = m.id ";
$query_Categories .= "ORDER BY m.sort_order ASC, c.sort_order ASC";
$Categories = $db->query($query_Categories);
$row_Categories = $Categories->fetchObject();
if(!$Categories)
{
  $err_str = "<body><p><h2>Execute sum_sql query error, because: ". $db->errorInfo()."</h2></p>\n";
  $err_str .= "<p>SQL:<br />".$query_Categories."</p></body>";
  die($err_str);
}

$query_Labels = "SELECT * FROM p_econ_labels";
$Labels = $db->query($query_Labels);
$row_Labels = $Labels->fetchObject();
if(!$Labels)
{
  $err_str = "<body><p><h2>Execute sum_sql query error, because: ". $db->errorInfo()."</h2></p>\n";
  $err_str .= "<p>SQL:<br />".$query_Labels."</p></body>";
  die($err_str);
}

// Översikts SQL-fråga
$sum_sql="SELECT K.Owner, Accounts, Loans, Funds, Pensions, if(Stocks is NULL,0,Stocks) AS Stock, ";
$sum_sql.="if(Costs is NULL,0,-Costs) AS Cost, ";
$sum_sql.="(Accounts+Loans+if(Costs is NULL,0,-Costs)+if(Stocks is NULL,0,Stocks)+Funds) AS Total ";
$sum_sql.="FROM ";
$sum_sql.="(SELECT Acc.users_id AS Owner, ";
$sum_sql.="SUM(if(Acc.accCat_id=0,if(Av.value is NULL,0,Av.value),0)) AS Accounts, ";
$sum_sql.="SUM(if(Acc.accCat_id=1,if(Av.value is NULL,0,Av.value),0)) AS Loans, ";
$sum_sql.="SUM(if(Acc.accCat_id=3,if(Av.value is NULL,0,Av.value),0)) AS Funds, ";
$sum_sql.="SUM(if(Acc.accCat_id=4,if(Av.value is NULL,0,Av.value),0)) AS Pensions ";
$sum_sql.="FROM p_econ_accounts AS Acc ";
$sum_sql.="LEFT JOIN ";
$sum_sql.="(SELECT * FROM p_econ_accountvalues WHERE ";
$sum_sql.="DATE_FORMAT(date,'%Y-%m')=DATE_FORMAT(NOW()-INTERVAL 1 MONTH,'%Y-%m')) ";
$sum_sql.="AS Av ON Acc.id = Av.accounts_id ";
$sum_sql.="GROUP BY Acc.users_id ORDER BY Acc.users_id) AS K ";
$sum_sql.="LEFT JOIN ";
$sum_sql.="(SELECT users_id AS Owner, SUM(cost) AS Costs ";
$sum_sql.="FROM p_econ_costs ";
$sum_sql.="WHERE DATE_FORMAT(date,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m') ";
$sum_sql.="AND categories_id > 0 ";
$sum_sql.="GROUP BY users_id) AS C ";
$sum_sql.="ON C.Owner = K.Owner ";
$sum_sql.="LEFT JOIN ";
$sum_sql.="(SELECT sh.users_id AS Owner, DATE(sv.time), ";
$sum_sql.="SUM(if(sh.nofstocks is NULL,0,sh.nofstocks * sv.value)) AS Stocks ";
$sum_sql.="FROM p_ekon_stockholdings AS sh ";
$sum_sql.="LEFT JOIN p_econ_users AS u ON u.name=sh.users_id ";
$sum_sql.="INNER JOIN (SELECT * FROM p_ekon_stockvalues ";
$sum_sql.="WHERE DATE(time)=(SELECT DATE(MAX(time)) FROM p_ekon_stockvalues)) ";
$sum_sql.="AS sv ON sh.stock_id = sv.stock_id ";
$sum_sql.="GROUP BY DATE(time)) ";
$sum_sql.="AS S ON S.Owner=K.Owner";
$summary = $db->query($sum_sql);
if(!$summary)
{
  die("<body><p><h2>Execute sum_sql query error, because: ". $db->errorInfo()."</h2></p><p>SQL:<br />$sum_sql</p></body>");
}

// Kontoöversikt


// Utgiftsöversikt
$query_Cats = "SELECT DISTINCT name AS mainCat, id AS mainCatId ";
$query_Cats .= "FROM p_econ_categories ";
$query_Cats .= "WHERE parent_id is NULL AND id > 0 ";
$query_Cats .= "ORDER BY sort_order ASC";
$Cats = $db->query($query_Cats);
if(!$Cats)
{
  $err_str = "<body><p><h2>Execute query_Cats query error, because: ". $db->errorInfo()."</h2></p>\n";
  $err_str .= "<p>SQL:<br />".$query_Cats."</p></body>";
  die($err_str);
}

$query_cost="SELECT ";
$query_cost.="users_id, DATE_FORMAT(NOW(),'%Y-%m-%d') AS Date, ";
while($row_Cats = $Cats->fetchObject()) {
  $query_cost.="SUM(if(cat.parent_id=".$row_Cats->mainCatId.",cost,0)) AS `".$row_Cats->mainCat."`, ";
  $costHeadings[]=$row_Cats->mainCat;
}
$query_cost.="SUM(cost) AS Total ";
$query_cost.="FROM p_econ_costs ";
$query_cost.="INNER JOIN p_econ_categories AS cat ON cat.id = categories_id ";
$query_cost.="WHERE categories_id > 0 AND ";
$query_cost.="DATE_FORMAT(date,'%Y-%m-%d') >= DATE_FORMAT(NOW(),'%Y-%m-01') ";
//$query_cost.="OR (DATE_FORMAT(date,'%Y-%m-%d') >= DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y-%m-01') ";
//$query_cost.=" AND DATE_FORMAT(date,'%Y-%m-%d') <= DATE_FORMAT(NOW() - INTERVAL 1 MONTH,'%Y-%m-%d')) ";
$query_cost.="GROUP BY users_id";
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
$query_acc= "SELECT Acc.users_id AS Owner,DATE_FORMAT(AV.date,'%M-%Y') AS Datum, ";
$query_acc.= "Acc.accCat_id, Acc.name, if(AV.Value is NULL,0,AV.Value) AS Value, ";
$query_acc.= "if(AV1.Value is NULL,0,AV1.Value) AS Value1, if(AV3.Value is NULL,0,AV3.Value) AS Value3 ";
$query_acc.= "FROM p_econ_accounts AS Acc ";
$query_acc.= "LEFT JOIN p_econ_accountvalues AS AV ";
$query_acc.= "ON AV.accounts_id = Acc.id ";
$query_acc.= "AND DATE_FORMAT(AV.date,'%Y-%m') = DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m') ";
$query_acc.= "LEFT JOIN p_econ_accountvalues AS AV1 ";
$query_acc.= "ON AV1.accounts_id = Acc.id ";
$query_acc.= "AND DATE_FORMAT(AV1.date,'%Y-%m') = DATE_FORMAT(NOW() - INTERVAL 2 MONTH, '%Y-%m') ";
$query_acc.= "LEFT JOIN p_econ_accountvalues AS AV3 ";
$query_acc.= "ON AV3.accounts_id = Acc.id ";
$query_acc.= "AND DATE_FORMAT(AV3.date,'%Y-%m') = DATE_FORMAT(NOW() - INTERVAL 5 MONTH, '%Y-%m') ";
$query_acc.= "LEFT JOIN p_econ_acccategories AS AC ";
$query_acc.= "ON AC.id = Acc.accCat_id ";
$query_acc.= "WHERE AV.Value <> 0 OR AV1.Value <> 0 OR AV3.Value <> 0 ";
$query_acc.= "ORDER BY Owner, AC.sortorder, Acc.name";
try{
  $db->query("SET SQL_BIG_SELECTS=1");
  $accounts = $db->query($query_acc);
} catch (PDOException $err) {
  echo "<p>".$err->getMessage()."</p>";
}
if(!$accounts)
{
  $err=$db->errorInfo();
  $err_str = "<body><p><h2>Execute query_acc query error, because:</h2><br />";
  $err_str .= $err[2]."</p>\n";
  $err_str .= "<p>SQL:<br />".$query_acc."</p></body>";
  die($err_str);
}

// Aktier
$stocks_sql ="SELECT DATE_FORMAT(sv.time,'%e/%c') AS date, sh.users_id AS Owner, s.name, ";
$stocks_sql.="sv.diff, sv.value, sv.high, sv.low, SUM(sh.nofstocks) AS ant, ";
$stocks_sql.="SUM(sh.nofstocks * sv.value) AS tot, SUM(sh.nofstocks * sh.cost) AS cost, ";
$stocks_sql.="SUM((sv.value-sh.cost)*sh.nofstocks) AS tot_diff ";
$stocks_sql.="FROM p_ekon_stocks AS s ";
$stocks_sql.="INNER JOIN p_ekon_stockvalues AS sv ON sv.stock_id = s.id ";
$stocks_sql.="AND DATE_FORMAT(sv.time,'%Y%m%d') = (";
$stocks_sql.="SELECT MAX(DATE_FORMAT(time,'%Y%m%d')) FROM p_ekon_stockvalues)  ";
$stocks_sql.="LEFT JOIN p_ekon_stockholdings AS sh ON s.id = sh.stock_id ";
$stocks_sql.="GROUP BY s.id ";
$stocks_sql.="ORDER BY DATE_FORMAT(sv.time,'%Y%m%d') DESC, s.name ";
//$stocks_sql.="WHERE ant>0";
try{
  $stocks = $db->query($stocks_sql);
} catch (PDOException $err) {
  echo "<p>".$err->getMessage()."</p>";
}
if(!$stocks)
{
  $err=$db->errorInfo();
  $err_str = "<body><p><h2>Execute stocks_sql query error, because:</h2><br />";
  $err_str .= $err[2]."</p>\n";
  $err_str .= "<p>SQL:<br />".$stocks_sql."</p></body>";
  die($err_str);
}

function printSum($accCatId, $sum, $sum1, $sum3)
{
  switch ($accCatId)
  {
    case 0:
      echo "<tr bgcolor='DDFFDD'>\n";
      echo "<th width='130' align='left' class='sum'>Tillgångar</th>\n";
      break;
    case 1:
      echo "<tr bgcolor='FFDDDD'>\n";
      echo "<th width='130' align='left' class='sum'>Låneskuld</th>\n";
      break;
    case 2:
      echo "<tr bgcolor='FFDDDD'>\n";
      echo "<th width='130' align='left' class='sum'>Kreditskuld</th>\n";
      break;
    case 3:
      echo "<tr bgcolor='DDFFDD'>\n";
      echo "<th width='130' align='left' class='sum'>Fonder</th>\n";
      break;
    default:
      echo "<tr bgcolor='FFFFDD'>\n";
      echo "<th width='130' align='left' class='sum'>Pension</th>\n";
    break;
  }
  echo "<th width='130' align='right' class='sum'>".number_format($sum,2,',',' ')."</th>\n";
  // 1 month
  echo "<th width='130' align='right' class='sum'>".number_format($sum-$sum1,2,',',' ')."</th>\n";
  if ($sum1 == 0)
  {
    echo "<th width='70' align='right' class='sum'>(N/A)</th>\n";
  } else {
    echo "<th width='70' align='right' class='sum'> (".number_format(($sum-$sum1)/abs($sum1)*100,2,',',' ').")</th>\n";
  }
  // 3 months
  echo "<th width='130' align='right' class='sum'>".number_format($sum-$sum3,2,',',' ')."</th>\n";
  if ($sum3 == 0)
  {
    echo "<th width='70' align='right' class='sum'>(N/A)</th>\n</tr>\n";
  } else {
    echo "<th width='70' align='right' class='sum'> (".number_format(($sum-$sum3)/abs($sum3)*100,2,',',' ').")</th\n</tr>\n";
  }
  echo "<tr><td colspan='6'>&nbsp;</td></tr>\n";
}

?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Ekonomi</title>
<script languague="javascript">
function myUpdate(option) {
  var spans=document.getElementsByTagName("span");
  // Loop through all spans
  for (var i=0; i < spans.length; i++) {
    if (spans[i].id != option) {
      collapseRow(spans[i]);
    } else { 
      restore(spans[i]);
    }
  }
}

//collapses a row
function collapseRow(rowElement) {
  rowElement.style.display = 'none';
}

function resetPage() {
  var rows=document.getElementsByTagName("SPAN");
  for (var i=0; i < rows.length; i++) {
    restore(rows[i]);
  }
}
//the color black
var BLACK ="#000000"; 
//restores the color to black
function restore(rowElement) {
  rowElement.style.display = '';
  rowElement.style.color = BLACK;
}

</script>
<style>
th.sum 
{
  border-bottom: 2px solid black;
  border-top: 3px solid black;
}
</style>
</head>

<body onLoad="myUpdate('newCost');">
<table cellpadding="5">
  <tr>
    <th align="right">L&auml;gg till:</th>
    <td><a href="addCostItem.php">Utgift</a></td>
    <td><a href="addAccValue.php">Kontouppgift</a></td>
    <td><a href="addCategory.php">Kategori</a></td>
  </tr>
  <tr>
    <th align="right">Visa:</th>
    <td><a href="showCosts.php">Utl&auml;gg</a></td>
    <td><a href="showAcc.php">Konton</a></td>
    <td><a href="showSum.php">Summering</a></td>
  </tr>
</table>
<p>
<?php 
echo "<h2>&Ouml;versikt</h2>\n";
$result = $summary->fetchAll(PDO::FETCH_ASSOC);
echo "<table cellspacing='0'>\n<tr>\n";
echo "<th align=left>Anv&auml;ndare</th>\n";
echo "<th align=right>Konto</th>\n";
echo "<th align=right>L&aring;n</th>\n";
echo "<th align=right>Fond</th>\n";
echo "<th align=right>Pension</th>\n";
echo "<th align=right>Aktier</th>\n";
echo "<th align=right>Kostnader</th>\n";
echo "<th align=right>Totalt</th>\n";
echo "</tr>\n";
$odd=0;
foreach ($result as $row) {
  if ($odd==1) {
    echo "<tr>\n";
    $odd=0;
  } else {
    echo "<tr bgcolor='DDDDFF'>\n";
    $odd=1;
  }
  foreach ($row as $value) {
    if(is_numeric($value)) {
        echo "<td width=100 align=right>".number_format($value,2,',',' ')."</td>\n";
      } else {
        echo "<td width=100>$value</td>\n";
      }
  }
  echo "</tr>\n";
}
echo "</table>";
?>
</p>
<hr />

<table>
<tr>
<td width="150" align="center" bgcolor="99dd99"><h3 onClick="myUpdate('newCost');">Nytt Utl&auml;gg</h3></td>
<td width="150" align="center" bgcolor="99dd99"><h3 onClick="myUpdate('costs');">Utl&auml;gg</h3></td>
<td width="150" align="center" bgcolor="99dd99"><h3 onClick="myUpdate('accounts');">Konton</h3></td>
<td width="150" align="center" bgcolor="99dd99"><h3 onClick="myUpdate('stocks');">Aktier</h3></td>
</tr>
</table>

<span id="newCost">
<p>
<form action="<?php echo $editFormAction; ?>" method="post" name="form1" id="form1">
  <table align="left">
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Kostnad:</td>
      <td><input type="text" name="cost" value="" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Datum:</td>
      <td><input type="text" name="date" value="<?php echo date('Y-m-d'); ?>" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right" valign="top">Kommentar:</td>
      <td><textarea type="textarea" name="comment" value="" rows="3" size="32" /></textarea></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Anv&auml;ndare:</td>
      <td><select name="users_id">
        <?php 
do {  
?>
        <option value="<?php echo $row_Users->name?>" ><?php echo $row_Users->name?></option>
        <?php
} while ($row_Users = $Users->fetchObject());
?>
      </select></td>
    </tr>
    <tr> </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Kategori:</td>
      <td><select name="categories_id">
        <?php 
do {  
?>
        <option value="<?php echo $row_Categories->subCatId?>" ><?php echo $row_Categories->mainCat." - ".$row_Categories->subCat ?></option>
        <?php
} while ($row_Categories = $Categories->fetchObject());
?>
      </select></td>
    </tr>
    <tr> </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Tagg:</td>
         <td>
           <?php do { ?>
              <input type="checkbox" name="labels_id[]" value="<?php echo $row_Labels->id?>" /><?php echo $row_Labels->name?><br />
           <?php } while ($row_Labels = $Labels->fetchObject()); ?>
         </td>
    </tr>
    <tr> </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">&nbsp;</td>
      <td><input type="submit" value="Insert record" /></td>
    </tr>
  </table>
  <input type="hidden" name="MM_insert" value="form1" />
</form>
</p>
</span>

<span id="costs">
<p>
<?php
$result = $costs->fetchAll(PDO::FETCH_ASSOC);
echo "<table cellspacing='0'>\n<tr>\n";
echo "<th align=left>Användare</th>\n";
echo "<th align=left>Datum</th>\n";
foreach($costHeadings as $heading) {
  echo "<th align=right>$heading</th>\n";
}
echo "<th align=right>Totalt</th>\n";
echo "</tr>\n";
$odd=0;
foreach ($result as $row) {
  if ($odd==1) {
    echo "<tr>\n";
    $odd=0;
  } else {
    echo "<tr bgcolor='DDDDFF'>\n";
    $odd=1;
  }
  foreach ($row as $value) {
    if(is_numeric($value)) {
        echo "<td width=100 align=right>".number_format($value,2,',',' ')."</td>\n";
      } else {
        echo "<td width=100>$value</td>\n";
      }
  }
  echo "</tr>\n";
}
echo "</table>\n";
echo "<p><img src='costplot.php?user=\"Gemensamt\"' width='800'></p>\n";
?>
</p>
</span>
<span id="accounts">
<p>
<?php
$result = $accounts->fetchAll(PDO::FETCH_ASSOC);
echo "<table>\n<tr>\n";
echo "<th colspan='4' align='left'>Konton ".$result[0]['Datum']."</th>\n</tr>\n<tr>\n";
echo "<td width='500' valign='top' align='left'>\n";
echo "<b>".$result[0]['Owner']."</b>\n";
echo "<table cellspacing='0'>\n";
$odd=0;
$value0=0;
$sum=0;
$sum1=0;
$sum3=0;
$old_loan = 0;
$old_owner = $result[0]['Owner'];
foreach ($result as $row) {
  foreach ($row as $key => $value) {
    if($key == 'accCat_id' && $old_loan != $value) {
      printSum($old_loan,$sum,$sum1,$sum3);
      $old_loan = $value;
      $odd=0;
      $sum=0;
      $sum1=0;
      $sum3=0;
    } elseif($key == 'Owner' && $old_owner != $value) {
	  printSum($old_loan,$sum,$sum1,$sum3);
      $old_owner = $value;
      $old_loan = 0;
      $odd=0;
      $sum=0;
      $sum1=0;
      $sum3=0;
      // Nästa tabell
      echo "</table>\n</td>\n";
      echo "<td width='500' valign='top' align='left'>\n";
      echo "<b>$value</b>\n";
      echo "<table cellspacing='0'>\n";
    } elseif($key == 'name') {
      if ($odd==1) {
        echo "<tr>\n";
        $odd=0;
      } else {
        echo "<tr bgcolor='DDDDFF'>\n";
        $odd=1;
      }
      echo "<td width='130'>$value</td>\n";
    } elseif($key == 'Value') {
      $sum += $value;
      $value0=$value;
      echo "<td width='130' align='right'>".number_format($value,2,',',' ')."</td>\n";
    } elseif($key == 'Value1') {
      $sum1 += $value;
      echo "<td width='130' align='right'>".number_format($value0-$value,2,',',' ')."</td>\n";
      if ($value == 0)
      {
        echo "<td width='70' align='right'>(N/A)</td>\n";
      } else {
        echo "<td width='70' align='right'> (".number_format(($value0-$value)/abs($value)*100,2,',',' ')."%)</td>\n";
      }
    } elseif($key == 'Value3') {
      $sum3 += $value;
      echo "<td width='130' align='right'>".number_format($value0-$value,2,',',' ')."</td>\n";
      if ($value == 0)
      {
        echo "<td width='70' align='right'>(N/A)</td>\n</tr>\n";
      } else {
        echo "<td width='70' align='right'> (".number_format(($value0-$value)/abs($value)*100,2,',',' ')."%)</td>\n</tr>\n";
      }
    }
  }
}
// Skulder
printSum($old_loan,$sum,$sum1,$sum3);
echo "</table>\n</td>\n";
echo "</td>\n</tr>\n</table>\n";

?>
</p>
</span>
<span id="stocks">
<p>
<?php
echo "<table cellspacing='0'>";
$old_owner="";
$sum_d    = 0;
$sum_tot  = 0;
$sum_cost = 0;
$sum_diff = 0;

while($nt=$stocks->fetchObject()){
  if ($nt->ant > 0){
    if($old_owner != $nt->Owner) {
      $old_owner = $nt->Owner;
      $odd=0;
      echo "<tr>\n";
      echo "<th colspan='4' align=left>$nt->Owner</th>\n";
      echo "</tr>\n";
      echo "<tr>\n";
      echo "<th align=left>Datum</th>\n";
      echo "<th align=leftt>Aktie</th>\n";
      echo "<th align=right>Diff</td>\n";
      echo "<th align=right>Slutkurs</td>\n";
      echo "<th align=right>H&ouml;gsta</td>\n";
      echo "<th align=right>L&auml;gsta</td>\n";
      echo "<th align=right>Antal</td>\n";
      echo "<th align=right>Totalt</td>\n";
      echo "<th align=right>Inköpskostnad</td>\n";
      echo "<th align=right>Diff</td>\n";
      echo "</tr>\n";
    }
    if ($odd==1) {
      echo "<tr>\n";
      $odd=0;
    } else {
      echo "<tr bgcolor='DDDDFF'>\n";
      $odd=1;
    }
    echo "<th align=left>$nt->date</th>\n";
    echo "<th align=left>$nt->name</th>\n";
    echo "<td align=right width=100>".number_format($nt->diff,2,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->value,2,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->high,2,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->low,2,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->ant,0,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->tot,2,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->cost,2,'.',' ')."</td>\n";
    echo "<td align=right width=100>".number_format($nt->tot_diff,2,'.',' ')."</td>\n";
    echo "</tr>\n";
	$sum_d    += $nt->diff*$nt->ant;
	$sum_tot  += $nt->tot;
	$sum_cost += $nt->cost;
	$sum_diff += $nt->tot_diff;
  }
}
echo "<tr bgcolor='FFDDFF'>\n";
echo "<th align=left>Summa:</th>\n";
echo "<th align=left>&nbsp;</th>\n";
echo "<td align=right width=100>".number_format($sum_d,2,'.',' ')."</td>\n";
echo "<td align=right width=100>&nbsp;</td>\n";
echo "<td align=right width=100>&nbsp;</td>\n";
echo "<td align=right width=100>&nbsp;</td>\n";
echo "<td align=right width=100>&nbsp;</td>\n";
echo "<td align=right width=100>".number_format($sum_tot,2,'.',' ')."</td>\n";
echo "<td align=right width=100>".number_format($sum_cost,2,'.',' ')."</td>\n";
echo "<td align=right width=100>".number_format($sum_diff,2,'.',' ')."</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "<p><img src='stockplot.php' width='400'></p>\n";
?>
</p>
</span>

<p>&nbsp;</p>
</body>
</html>
