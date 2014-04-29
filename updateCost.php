<?php require_once('Connections/localhost_lerenius.php'); ?><?php
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

if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {
  $updateSQL = sprintf("UPDATE p_econ_costs SET cost=%s, `date`=%s, `comment`=%s, users_id=%s, categories_id=%s WHERE id=%s",
                       GetSQLValueString($_POST['cost'], "double"),
                       GetSQLValueString($_POST['date'], "date"),
                       GetSQLValueString($_POST['comment'], "text"),
                       GetSQLValueString($_POST['users_id'], "text"),
                       GetSQLValueString($_POST['categories_id'], "int"),
                       GetSQLValueString($_POST['id'], "int"));

  mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
  $Result1 = mysql_query($updateSQL, $localhost_lerenius) or die(mysql_error());
  
  printf("<p align='center'>\nUppdaterat: %s kr, %s , %s, %s, kategori #%s<br />\n",
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

  if(isset($_POST["remember"]) && $_POST["remember"] == 'Yes') 
  {
    $insertSQL = sprintf("INSERT INTO p_econ_shopcategories (`comment`, shop, category_id) VALUES (%s, %s, %s)",   
      GetSQLValueString($_POST['comment'], "text"),
      GetSQLValueString($_POST['oldComment'], "text"),
      GetSQLValueString($_POST['categories_id'], "int"));
    mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
    $Result3 = mysql_query($insertSQL, $localhost_lerenius) or die(mysql_error());
	printf("Lagt till shop kategori<br />%s -> #%s - %s<br />\n",
	  GetSQLValueString($_POST['oldComment'], "text"),
      GetSQLValueString($_POST['categories_id'], "int"),
	  GetSQLValueString($_POST['comment'], "text"));
  }
  printf("\n</p>\n");
  
  $updateGoTo = "unhandledCosts.php";
  if (isset($_SERVER['QUERY_STRING'])) {
    $updateGoTo .= (strpos($updateGoTo, '?')) ? "&" : "?";
    $updateGoTo .= $_SERVER['QUERY_STRING'];
  }
  printf("<a href=\"$updateGoTo\">Gå tillbaka</a><br />\n");
  //header(sprintf("Location: %s", $updateGoTo));
}

$maxRows_DetailRS1 = 10;
$pageNum_DetailRS1 = 0;
if (isset($_GET['pageNum_DetailRS1'])) {
  $pageNum_DetailRS1 = $_GET['pageNum_DetailRS1'];
}
$startRow_DetailRS1 = $pageNum_DetailRS1 * $maxRows_DetailRS1;

$colname_DetailRS1 = "-1";
if (isset($_GET['recordID'])) {
  $colname_DetailRS1 = $_GET['recordID'];
}
mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_DetailRS1 = sprintf("SELECT * FROM p_econ_costs  WHERE id = %s", GetSQLValueString($colname_DetailRS1, "int"));
$query_limit_DetailRS1 = sprintf("%s LIMIT %d, %d", $query_DetailRS1, $startRow_DetailRS1, $maxRows_DetailRS1);
$DetailRS1 = mysql_query($query_limit_DetailRS1, $localhost_lerenius) or die(mysql_error());
$row_DetailRS1 = mysql_fetch_assoc($DetailRS1);

if (isset($_GET['totalRows_DetailRS1'])) {
  $totalRows_DetailRS1 = $_GET['totalRows_DetailRS1'];
} else {
  $all_DetailRS1 = mysql_query($query_DetailRS1);
  $totalRows_DetailRS1 = mysql_num_rows($all_DetailRS1);
}
$totalPages_DetailRS1 = ceil($totalRows_DetailRS1/$maxRows_DetailRS1)-1;

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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<title>Update Cost</title>
</head>

<body>
<form action="<?php echo $editFormAction; ?>" method="post" name="form1" id="form1">
  <table align="center">
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Id:</td>
      <td><?php echo $row_DetailRS1['id']; ?></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Cost:</td>
      <td><input type="text" name="cost" value="<?php echo htmlentities($row_DetailRS1['cost'], ENT_COMPAT, 'ISO-8859-1'); ?>" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Date:</td>
      <td><input type="text" name="date" value="<?php echo htmlentities($row_DetailRS1['date'], ENT_COMPAT, 'ISO-8859-1'); ?>" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Comment:</td>
      <td><input type="text" name="comment" value="<?php echo htmlentities($row_DetailRS1['comment'], ENT_COMPAT, 'ISO-8859-1'); ?>" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Users_id:</td>
      <td><input type="text" name="users_id" value="<?php echo htmlentities($row_DetailRS1['users_id'], ENT_COMPAT, 'ISO-8859-1'); ?>" size="32" /></td>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Categories_id:</td>
      <td><select name="categories_id">
        <?php 
do {  
?>
        <option value="<?php echo $row_Categories['subCatId'] ?>" <?php if ($row_DetailRS1['categories_id']==$row_Categories['subCatId']) {echo "selected";} ?> ><?php echo $row_Categories['mainCat']." - ".$row_Categories['subCat']?></option>
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
      <td nowrap="nowrap" align="right">Labels:</td>
         <td>
              <input type="checkbox" name="remember" value="Yes" />Remember category selection<br />
         </td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">&nbsp;</td>
      <td><input type="submit" value="Update" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">&nbsp;</td>
      <td><input type="submit" value="Ignore" /></td>
    </tr>
  </table>
  <input type="hidden" name="MM_update" value="form1" />
  <input type="hidden" name="id" value="<?php echo $row_DetailRS1['id']; ?>" />
  <input type="hidden" name="oldComment" value="<?php echo $row_DetailRS1['comment']; ?>" />
</form>
<p>&nbsp;</p>
</body>
</html>
<?php
mysql_free_result($DetailRS1);
mysql_free_result($Users);
mysql_free_result($Categories);
mysql_free_result($Labels);
?>