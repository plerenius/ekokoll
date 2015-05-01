<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
require_once('Connections/localhost_lerenius.php');

if (isset($_GET['user'])) {
  $user = "a.users_id=".$_GET['user'];
} else {
  $user = "1";
}

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
  $insertSQL = sprintf("INSERT INTO p_econ_accountvalues (`accounts_id`, `date`, `value`) VALUES (%s, %s, %s)",
                       GetSQLValueString($_POST['accounts_id'], "int"),
                       GetSQLValueString($_POST['date'], "date"),
                       GetSQLValueString($_POST['value'], "double"));

  mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
  $Result1 = mysql_query($insertSQL, $localhost_lerenius) or die(mysql_error());
}

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_Accounts = "SELECT a.* FROM p_econ_accounts AS a ";
$query_Accounts .= "LEFT JOIN p_econ_accountvalues AS av ON a.id=av.accounts_id AND ";
$query_Accounts .= "DATE_FORMAT(av.date,'%y-%m')=DATE_FORMAT(NOW()-INTERVAL 1 MONTH,'%y-%m') ";
$query_Accounts .= "WHERE $user AND av.value IS NULL AND a.active=1 ";
$query_Accounts .= "ORDER BY a.users_id, a.name";
$Accounts = mysql_query($query_Accounts, $localhost_lerenius) or die(mysql_error());
$row_Accounts = mysql_fetch_assoc($Accounts);
$totalRows_Accounts = mysql_num_rows($Accounts);
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
  <title>L&auml;gg till kontov&auml;rde</title>
</head>

<body>
<p align="center">
<?php if ((isset($_POST["MM_insert"])) && ($_POST["MM_insert"] == "form1")) {
	printf("Lagt till: %s kr, %s, konto #%s",
               GetSQLValueString($_POST['value'], "double"),
               GetSQLValueString($_POST['date'], "date"),
               GetSQLValueString($_POST['accounts_id'], "int"));
	}?>
</p>
<form action="<?php echo $editFormAction; ?>" method="post" name="form1" id="form1">
  <table align="center">
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Värde:</td>
      <td><input type="text" name="value" value="" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Datum:</td>
      <td><input type="text" name="date" value="<?php echo date('Y-m-d',mktime(0,0,0,date('m'),0,date('Y'))); ?>" size="32" /></td>
    </tr>
    <tr valign="baseline">
      <td nowrap="nowrap" align="right">Konto:</td>
      <td><select name="accounts_id">
        <?php 
do {  
?>
        <option value="<?php echo $row_Accounts['id']?>" ><?php echo $row_Accounts['users_id']."-".$row_Accounts['name']?></option>
        <?php
} while ($row_Accounts = mysql_fetch_assoc($Accounts));
?>
      </select></td>
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
mysql_free_result($Accounts);
?>
