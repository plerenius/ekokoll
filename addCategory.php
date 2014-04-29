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

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
  $insertSQL = sprintf("INSERT INTO p_econ_categories (parent_id, name, `description`, sort_order) VALUES (%s, %s, %s, %s)",
                       GetSQLValueString($_POST['parent_id'], "int"),
                       GetSQLValueString($_POST['name'], "text"),
                       GetSQLValueString($_POST['description'], "text"),
                       GetSQLValueString($_POST['sort_order'], "int"));

  mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
  $Result1 = mysql_query($insertSQL, $localhost_lerenius) or die(mysql_error());
}

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_mainCategories = "SELECT id, name FROM p_econ_categories WHERE p_econ_categories.parent_id IS NULL";
$mainCategories = mysql_query($query_mainCategories, $localhost_lerenius) or die(mysql_error());
$row_mainCategories = mysql_fetch_assoc($mainCategories);
$totalRows_mainCategories = mysql_num_rows($mainCategories);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>L&auml;gg till kategori</title>
</head>

<body>
<form action="<?php echo $editFormAction; ?>" method="post" name="form1" id="form1">
  <table align="center">
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Parent_id:</td>
      <td><select name="parent_id">
        <?php 
do {  
?>
        <option value="<?php echo $row_mainCategories['id']?>" ><?php echo $row_mainCategories['name']?></option>
        <?php
} while ($row_mainCategories = mysql_fetch_assoc($mainCategories));
?>
        <option value="" >Main Category</option>
      </select></td>
    </tr>
    <tr> </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Name:</td>
      <td><input type="text" name="name" value="" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right" valign="top">Description:</td>
      <td><textarea name="description" cols="50" rows="5"></textarea></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Sort_order:</td>
      <td><input type="text" name="sort_order" value="" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">&nbsp;</td>
      <td><input type="submit" value="Insert record" /></td>
    </tr>
  </table>
  <input type="hidden" name="MM_insert" value="form1" />
</form>
<p>&nbsp;</p>
</body>
</html>
<?php
mysql_free_result($mainCategories);
?>
