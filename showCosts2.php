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

$maxRows_Utlagg = 30;
$pageNum_Utlagg = 0;
if (isset($_GET['pageNum_Utlagg'])) {
  $pageNum_Utlagg = $_GET['pageNum_Utlagg'];
}
$startRow_Utlagg = $pageNum_Utlagg * $maxRows_Utlagg;

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_Utlagg = "SELECT u.name AS user, mc.name AS mainCat, sc.name AS subCat, c.categories_id, c.cost, c.date, c.comment FROM p_econ_costs AS c LEFT JOIN p_econ_users AS u ON c.users_id = u.name INNER JOIN p_econ_categories AS sc ON c.categories_id = sc.id INNER JOIN p_econ_categories AS mc ON sc.parent_id = mc.id";
$query_limit_Utlagg = sprintf("%s LIMIT %d, %d", $query_Utlagg, $startRow_Utlagg, $maxRows_Utlagg);
$Utlagg = mysql_query($query_limit_Utlagg, $localhost_lerenius) or die(mysql_error());
$row_Utlagg = mysql_fetch_assoc($Utlagg);

if (isset($_GET['totalRows_Utlagg'])) {
  $totalRows_Utlagg = $_GET['totalRows_Utlagg'];
} else {
  $all_Utlagg = mysql_query($query_Utlagg);
  $totalRows_Utlagg = mysql_num_rows($all_Utlagg);
}
$totalPages_Utlagg = ceil($totalRows_Utlagg/$maxRows_Utlagg)-1;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=latin1" />
<title>Visa utl&auml;gg</title>
</head>

<body>
<table border="0">
  <tr>
    <td>user</td>
    <td>mainCat</td>
    <td>subCat</td>
    <td>categories_id</td>
    <td>cost</td>
    <td>date</td>
    <td>comment</td>
  </tr>
  <?php do { ?>
    <tr>
      <td><?php echo $row_Utlagg['user']; ?></td>
      <td><?php echo $row_Utlagg['mainCat']; ?></td>
      <td><?php echo $row_Utlagg['subCat']; ?></td>
      <td><?php echo $row_Utlagg['categories_id']; ?></td>
      <td><?php echo $row_Utlagg['cost']; ?></td>
      <td><?php echo $row_Utlagg['date']; ?></td>
      <td><?php echo $row_Utlagg['comment']; ?></td>
    </tr>
    <?php } while ($row_Utlagg = mysql_fetch_assoc($Utlagg)); ?>
</table>
</body>
</html>
<?php
mysql_free_result($Utlagg);
?>
