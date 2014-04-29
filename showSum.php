<?php require_once('Connections/localhost_lerenius.php'); ?>
<?php
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

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_months = "SELECT DISTINCT CONCAT(MONTHNAME(date),YEAR(date)) AS m_name, DATE_FORMAT(date,'%b-%y') AS m_header, MONTH(date)+12*(YEAR(date)-2009) AS m_num FROM p_econ_costs ORDER BY m_num";
$months = mysql_query($query_months, $localhost_lerenius) or die(mysql_error());
$row_months = mysql_fetch_assoc($months);
$totalRows_months = mysql_num_rows($months);

$query_summering = "SELECT c.users_id AS name, MONTHNAME(c.date) AS m_name, mc.id AS mainCatId, mc.name AS mainCat, sc.name AS subCat, ";
$row_nr=$totalRows_months-1;
do {
  $query_summering .= 'SUM(IF((MONTH(c.date)+12*(YEAR(c.date)-2009))='.$row_months['m_num'].',c.cost,0)) AS summa_'.$row_months['m_name'].', ';
  $col_headings="<td class='sum_name' align='right'>".$row_months['m_header']."</td>\n".$col_headings;
  $month_col[$row_nr]="summa_".$row_months['m_name'];
  $row_nr-=1;
} while ($row_months = mysql_fetch_assoc($months));

$query_summering .= "SUM(c.cost) AS summa_tot, COUNT(*) AS antal FROM p_econ_costs AS c JOIN p_econ_categories AS sc ON c.categories_id = sc.id INNER JOIN p_econ_categories AS mc ON sc.parent_id = mc.id GROUP BY c.users_id, mc.id, c.categories_id";
$col_headings.="<td class='sum_name' align='right'>Tot</td>\n".
               "<td class='sum_name'>&nbsp;</td>\n";

$summering = mysql_query($query_summering, $localhost_lerenius) or die(mysql_error());
$row_summering = mysql_fetch_assoc($summering);
$totalRows_summering = mysql_num_rows($summering);

$old_name = '';
$old_mainCat = '';
$n_first = false;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=latin1" />
<title>Summering</title>
<link href="ekon_style.css" rel="stylesheet" type="text/css" />
</head>

<body>
<table border="0" align="center" cellpadding="0" cellspacing="0">
  <?php do { ?>
    <tr>
		<?php
    	if ($old_name != $row_summering['name']) {
          if ($n_first) {
            printf("<tr><td colspan='3'>&nbsp;</td>\n");
            for ($k=0;$k < $totalRows_months;$k++) { 
              printf("<td align='right'>%.2f kr</td>\n",$sumCosts[$k]);
            }
            printf("<td align='right'>%.2f kr</td>\n<td bgcolor='#FFFFCC'>&nbsp;</td></tr>\n",$sumCosts['tot']);
          }
          echo "<td colspan=\"3\" class=\"sum_name\">".$row_summering['name']."</td>\n$col_headings\n</tr>\n<tr>\n<td>&nbsp;</td>\n";
          $n_first = true;
          $old_mainCat = '';
          for ($k=0;$k < $totalRows_months;$k++) { 
            $sumCosts[$k] = $row_summering[$month_col[$k]];
          }
          $sumCosts['tot'] = $row_summering['summa_tot'];
        } else {
          for ($k=0;$k < $totalRows_months;$k++) { 
            $sumCosts[$k] += $row_summering[$month_col[$k]];
          }
          $sumCosts['tot'] += $row_summering['summa_tot'];
          echo "<td width=\"30\">&nbsp;</td>\n";
        }
        $old_name = $row_summering['name'];	
        if ($old_mainCat != $row_summering['mainCat']) {
          printf("<td width=\"100\" class=\"sum_mainCat\">\n<a href='showCosts.php?filt_user=%s&filt_mainCat=%s'>\n%s\n</a>\n</td>\n",
                 $row_summering['name'],
                 $row_summering['mainCat'],
                 $row_summering['mainCat']);
        } else {
          echo "<td>&nbsp;</td>\n";
        }
        $old_mainCat = $row_summering['mainCat'];
        ?>
      <td width="80" align="left" bgcolor="#FFFFCC"><?php echo $row_summering['subCat']; ?></td>
<?php for ($k=0;$k < $totalRows_months;$k++) { ?>
      <td width="100" align="right">
         <?php printf("<a href='showCosts.php?filt_user=%s&filt_mainCat=%s&filt_subCat=%s&filt_month=12'>%0.2f kr</a>",
                 $row_summering['name'],$row_summering['mainCat'],$row_summering['subCat'],$row_summering[$month_col[$k]]); ?>
      </td>
<?php } ?>
      <td width="100" align="right">
         <?php printf("<a href='showCosts.php?filt_user=%s&filt_mainCat=%s&filt_subCat=%s'>%0.2f kr</a>",
                 $row_summering['name'],$row_summering['mainCat'],$row_summering['subCat'],$row_summering['summa_tot']); ?></td>
      <td width="80" align="right" bgcolor="#FFFFCC"><?php echo $row_summering['antal']; ?></td>
    </tr>
    <?php } while ($row_summering = mysql_fetch_assoc($summering));
          printf("<tr><td colspan='3'>&nbsp;</td>\n");
          for ($k=0;$k < $totalRows_months;$k++) { 
            printf("<td align='right'>%.2f kr</td>\n",$sumCosts[$k]);
          }
            printf("<td align='right'>%.2f kr</td>\n<td bgcolor='#FFFFCC'>&nbsp;</td></tr>\n",$sumCosts['tot']);
    ?>
</table>
<p>&nbsp;</p>
</body>
</html>
<?php
mysql_free_result($months);
mysql_free_result($summering);
?>
