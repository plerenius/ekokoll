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

$currentPage = $_SERVER["PHP_SELF"];

$maxRows_UnhadledCosts = 10;
$pageNum_UnhadledCosts = 0;
if (isset($_GET['pageNum_UnhadledCosts'])) {
  $pageNum_UnhadledCosts = $_GET['pageNum_UnhadledCosts'];
}
$startRow_UnhadledCosts = $pageNum_UnhadledCosts * $maxRows_UnhadledCosts;

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_UnhadledCosts = "SELECT * FROM p_econ_costs WHERE categories_id = -2";
$query_limit_UnhadledCosts = sprintf("%s LIMIT %d, %d", $query_UnhadledCosts, $startRow_UnhadledCosts, $maxRows_UnhadledCosts);
$UnhadledCosts = mysql_query($query_limit_UnhadledCosts, $localhost_lerenius) or die(mysql_error());
$row_UnhadledCosts = mysql_fetch_assoc($UnhadledCosts);

if (isset($_GET['totalRows_UnhadledCosts'])) {
  $totalRows_UnhadledCosts = $_GET['totalRows_UnhadledCosts'];
} else {
  $all_UnhadledCosts = mysql_query($query_UnhadledCosts);
  $totalRows_UnhadledCosts = mysql_num_rows($all_UnhadledCosts);
}
$totalPages_UnhadledCosts = ceil($totalRows_UnhadledCosts/$maxRows_UnhadledCosts)-1;

$queryString_UnhadledCosts = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pageNum_UnhadledCosts") == false && 
        stristr($param, "totalRows_UnhadledCosts") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_UnhadledCosts = "&" . htmlentities(implode("&", $newParams));
  }
}
$queryString_UnhadledCosts = sprintf("&totalRows_UnhadledCosts=%d%s", $totalRows_UnhadledCosts, $queryString_UnhadledCosts);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-18" />
<title>Unhandled cost entries</title>
</head>
<?php


?>
<body>
<table border="1" align="center">
  <tr>
    <td>users_id</td>
    <td>date</td>
    <td>cost</td>
    <td>comment</td>
    <td>categories_id</td>
  </tr>
  <?php do { ?>
    <tr>
      <td><?php echo $row_UnhadledCosts['users_id']; ?>&nbsp; </td>
      <td><?php echo $row_UnhadledCosts['date']; ?>&nbsp; </td>
      <td><a href="updateCost.php?recordID=<?php echo $row_UnhadledCosts['id']; ?>"> <?php echo $row_UnhadledCosts['cost']; ?>&nbsp; </a></td>
      <td><?php echo $row_UnhadledCosts['comment']; ?>&nbsp; </td>
      <td><?php echo $row_UnhadledCosts['categories_id']; ?>&nbsp; </td>
    </tr>
    <?php } while ($row_UnhadledCosts = mysql_fetch_assoc($UnhadledCosts)); ?>
</table>
<br />
<table border="0">
  <tr>
    <td><?php if ($pageNum_UnhadledCosts > 0) { // Show if not first page ?>
        <a href="<?php printf("%s?pageNum_UnhadledCosts=%d%s", $currentPage, 0, $queryString_UnhadledCosts); ?>">First</a>
        <?php } // Show if not first page ?></td>
    <td><?php if ($pageNum_UnhadledCosts > 0) { // Show if not first page ?>
        <a href="<?php printf("%s?pageNum_UnhadledCosts=%d%s", $currentPage, max(0, $pageNum_UnhadledCosts - 1), $queryString_UnhadledCosts); ?>">Previous</a>
        <?php } // Show if not first page ?></td>
    <td><?php if ($pageNum_UnhadledCosts < $totalPages_UnhadledCosts) { // Show if not last page ?>
        <a href="<?php printf("%s?pageNum_UnhadledCosts=%d%s", $currentPage, min($totalPages_UnhadledCosts, $pageNum_UnhadledCosts + 1), $queryString_UnhadledCosts); ?>">Next</a>
        <?php } // Show if not last page ?></td>
    <td><?php if ($pageNum_UnhadledCosts < $totalPages_UnhadledCosts) { // Show if not last page ?>
        <a href="<?php printf("%s?pageNum_UnhadledCosts=%d%s", $currentPage, $totalPages_UnhadledCosts, $queryString_UnhadledCosts); ?>">Last</a>
        <?php } // Show if not last page ?></td>
  </tr>
</table>
Records <?php echo ($startRow_UnhadledCosts + 1) ?> to <?php echo min($startRow_UnhadledCosts + $maxRows_UnhadledCosts, $totalRows_UnhadledCosts) ?> of <?php echo $totalRows_UnhadledCosts ?>
</body>
</html>
<?php
mysql_free_result($UnhadledCosts);
?>
