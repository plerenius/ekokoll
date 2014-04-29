<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php require_once('Connections/localhost_lerenius.php');

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
mysql_select_db($database_localhost_lerenius, $localhost_lerenius);

$query_accSum = "SELECT Acc.users_id AS Owner,AV.date AS Datum, ";

$query_accounts = "SELECT id,name,users_id AS Owner FROM p_econ_accounts ORDER BY users_id";
$accounts = mysql_query($query_accounts, $localhost_lerenius) or die(mysql_error());
$old_owner = "Gemensamt";
while($row_accounts = mysql_fetch_assoc($accounts))
{
  if($old_owner!=$row_accounts['Owner'])
  {
    $query_accSum .= "SUM(if(Acc.users_id='$old_owner',AV.Value,0)) AS Total_$old_owner, ";
    $old_owner=$row_accounts['Owner'];
  }
  $query_accSum .= "SUM(if(Acc.id=$row_accounts[id],AV.Value,0)) AS `$row_accounts[name]`, ";
}
$query_accSum .= "SUM(AV.value) AS Summa ";
$query_accSum .= "FROM `p_econ_accountvalues` AS AV ";
$query_accSum .= "LEFT JOIN p_econ_accounts AS Acc ON AV.accounts_id = Acc.id ";
$query_accSum .= "GROUP BY Acc.users_id, B.date";

echo $query_accSum;

$query_accountSum = "SELECT Acc.users_id AS Owner,B.date AS Datum, SUM(B.value) AS Summa, ";
$query_accountSum .= "SUM(CASE WHEN (B.value is NULL) THEN 0 ELSE B.value END)-SUM(CASE WHEN (A.value is NULL) THEN 0 ELSE A.value END) AS Diff, ";
$query_accountSum .= "-cost_sum AS Reg_Costs ";
$query_accountSum .= "FROM `p_econ_accountvalues` AS A ";
$query_accountSum .= "RIGHT JOIN p_econ_accountvalues AS B ON A.accounts_id=B.accounts_id ";
$query_accountSum .= "AND DATEDIFF(B.date,A.date) >= 1 AND DATEDIFF(B.date,A.date) <= 35 ";
$query_accountSum .= "JOIN p_econ_accounts AS Acc ON B.accounts_id = Acc.id ";
$query_accountSum .= "LEFT JOIN (SELECT SUM(cost) AS cost_sum, YEAR(date) AS year_nr, MONTH(date) AS month_nr, users_id FROM p_econ_costs GROUP BY year_nr, month_nr, users_id) AS costs ";
$query_accountSum .= "ON YEAR(B.date)=costs.year_nr AND MONTH(B.date)=costs.month_nr AND costs.users_id=Acc.users_id ";
$query_accountSum .= "WHERE Acc.Loan=0 ";
$query_accountSum .= "GROUP BY Acc.users_id, B.date";
$accountSum = mysql_query($query_accountSum, $localhost_lerenius) or die(mysql_error());
$row_accountSum = mysql_fetch_assoc($accountSum);
$totalRows_accountSum = mysql_num_rows($accountSum);

$query_accounts = "SELECT Acc.users_id AS Owner, Acc.name AS Account,B.date, B.value, ";
$query_accounts .= "(CASE WHEN (B.value is NULL) THEN 0 ELSE B.value END)-(CASE WHEN (A.value is NULL) THEN 0 ELSE A.value END) AS Diff ";
$query_accounts .= "FROM `p_econ_accountvalues` AS A RIGHT JOIN p_econ_accountvalues AS B ON A.accounts_id=B.accounts_id ";
$query_accounts .= "AND DATEDIFF(B.date,A.date) >= 1 AND DATEDIFF(B.date,A.date) <= 32 ";
$query_accounts .= "RIGHT JOIN p_econ_accounts AS Acc ON B.accounts_id = Acc.id ";
$query_accounts .= "ORDER BY Acc.users_id, B.accounts_id, B.date";
$accounts = mysql_query($query_accounts, $localhost_lerenius) or die(mysql_error());
$row_accounts = mysql_fetch_assoc($accounts);
$totalRows_accounts = mysql_num_rows($accounts);
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Konton</title>
<link href="ekon_style.css" rel="stylesheet" type="text/css" />
</head>

<body>
<table border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
 <?php
$old_Owner = "";
  do {
	echo '<tr>';
	if ($old_Owner != $row_accountSum['Owner']) {
        printf("<td class=\"sum_name\" colspan='2'>\n%s\n</td>\n<td class=\"sum_name\" align=\"right\">Summa</td>\n<td class=\"sum_name\" align=\"right\">Kontodiff</td>\n<td class=\"sum_name\" align=\"right\">Kostnader</td>\n<td class=\"sum_name\" align=\"right\">Diff</td>\n</tr>\n<tr>\n<td>&nbsp;</td>\n",$row_accountSum['Owner']);
	    $old_Acc = '';
	} else {
        echo "<td>&nbsp;</td>\n";
    }
    $old_Owner = $row_accountSum['Owner'];
?>
      <td width=100><?php echo $row_accountSum['Datum']; ?></td>
      <td width=120 align="right"><?php printf("%.2f",$row_accountSum['Summa']); ?></td>
      <td width=120 align="right"><?php printf("%.2f",$row_accountSum['Diff']); ?></td>
      <td width=120 align="right"><?php printf("%.2f",$row_accountSum['Reg_Costs']); ?></td>
      <td width=120 align="right"><?php printf("%.2f",$row_accountSum['Diff']-$row_accountSum['Reg_Costs']); ?></td>
    </tr>
    <?php } while ($row_accountSum = mysql_fetch_assoc($accountSum)); ?>
</table>
<p>&nbsp;</p>
<table border="0" align="center">
 <?php do {
	echo '<tr>';
	if ($old_Owner != $row_accounts['Owner']) {
        printf("<td class=\"sum_name\" colspan='5'>\n%s\n</td>\n</tr>\n<tr>\n<td>&nbsp;</td>\n",$row_accounts['Owner']);
	    $old_Acc = '';
	} else {
        echo "<td>&nbsp;</td>\n";
    }
    $old_Owner = $row_accounts['Owner'];
	
	if ($old_Acc != $row_accounts['Account']) {
        printf("<td class=\"sum_mainCat\">\n%s\n</td>\n",$row_accounts['Account']);
	} else {
        echo "<td>&nbsp;</td>\n";
    }
    $old_Acc = $row_accounts['Account'];
    echo "<td>".$row_accounts['date']."</td>";
    echo "<td align='right'>".number_format($row_accounts['value'],2,'.',' ')."</td>";
    echo "<td align='right'>".number_format($row_accounts['Diff'],2,'.',' ')."</td>";
    echo "</tr>\n";
} while ($row_accounts = mysql_fetch_assoc($accounts)); ?>
</table>
</body>
</html>
<?php
mysql_free_result($accountSum);
mysql_free_result($accounts);
?>
