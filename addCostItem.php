<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php require_once('Connections/localhost_lerenius.php'); ?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=latin1" />
<title>L&auml;gg till utl&auml;gg</title>
</head>

<body>

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
	  $theValue = strtr($theValue,',','.');
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
  $insertSQL = sprintf("INSERT INTO p_econ_costs (cost, `date`, `comment`, users_id, categories_id) VALUES (%s, %s, %s, %s, %s)",
                       GetSQLValueString($_POST['cost'], "double"),
                       GetSQLValueString($_POST['date'], "date"),
                       GetSQLValueString($_POST['comment'], "text"),
                       GetSQLValueString($_POST['users_id'], "text"),
                       GetSQLValueString($_POST['categories_id'], "int"));

  mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
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

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_Users = "SELECT * FROM p_econ_users";
$Users = mysql_query($query_Users, $localhost_lerenius) or die(mysql_error());
$row_Users = mysql_fetch_assoc($Users);
$totalRows_Users = mysql_num_rows($Users);

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_Categories = "SELECT p.name AS mainCat, c.name AS subCat, c.id AS subCatId FROM p_econ_categories AS c INNER JOIN p_econ_categories AS p ON c.parent_id = p.id ORDER BY p.sort_order ASC, c.sort_order ASC";
$Categories = mysql_query($query_Categories, $localhost_lerenius) or die(mysql_error());
$row_Categories = mysql_fetch_assoc($Categories);
$totalRows_Categories = mysql_num_rows($Categories);

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_Labels = "SELECT * FROM p_econ_labels";
$Labels = mysql_query($query_Labels, $localhost_lerenius) or die(mysql_error());
$row_Labels = mysql_fetch_assoc($Labels);
$totalRows_Labels = mysql_num_rows($Labels);
?>
<form action="<?php echo $editFormAction; ?>" method="post" name="form1" id="form1">
  <table align="center">
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Cost:</td>
      <td><input type="text" name="cost" value="" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Date:</td>
      <td><input type="text" name="date" value="<?php echo date('Y-m-d'); ?>" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Comment:</td>
      <td><input type="text" name="comment" value="" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Users_id:</td>
      <td><select name="users_id">
        <?php 
do {  
?>
        <option value="<?php echo $row_Users['name']?>" ><?php echo $row_Users['name']?></option>
        <?php
} while ($row_Users = mysql_fetch_assoc($Users));
?>
      </select></td>
    </tr>
    <tr> </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Categories_id:</td>
      <td><select name="categories_id">
        <?php 
do {  
?>
        <option value="<?php echo $row_Categories['subCatId']?>" ><?php echo $row_Categories['mainCat']." - ".$row_Categories['subCat']?></option>
        <?php
} while ($row_Categories = mysql_fetch_assoc($Categories));
?>
      </select></td>
    </tr>
    <tr> </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Labels:</td>
         <td>
           <?php do { ?>
              <input type="checkbox" name="labels_id[]" value="<?php echo $row_Labels['id']?>" /><?php echo $row_Labels['name']?><br />
           <?php } while ($row_Labels = mysql_fetch_assoc($Labels)); ?>
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
<p>&nbsp;</p>
</body>
</html>
<?php
mysql_free_result($Users);
mysql_free_result($Categories);
mysql_free_result($Labels);
?>
