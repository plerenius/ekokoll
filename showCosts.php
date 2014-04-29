<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<?php
require_once('Connections/localhost_lerenius.php');
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

$pageNum_Utlagg = 0;
$maxRows_Utlagg = 30;
if (isset($_GET['pageNum_Utlagg'])) {
  $pageNum_Utlagg = $_GET['pageNum_Utlagg'];
}
$startRow_Utlagg = $pageNum_Utlagg * $maxRows_Utlagg;

$filt_user_Utlagg = "%";
if (isset($_GET['filt_user'])) {
  $filt_user_Utlagg = $_GET['filt_user']."%";
}
$filt_month_Utlagg = "%";
if (isset($_GET['filt_month'])) {
  $filt_month_Utlagg = $_GET['filt_month'];
}
$filt_mainCat_Utlagg = "%";
if (isset($_GET['filt_mainCat'])) {
  $filt_mainCat_Utlagg = $_GET['filt_mainCat']."%";
}
$filt_subCat_Utlagg = "%";
if (isset($_GET['filt_subCat'])) {
  $filt_subCat_Utlagg = $_GET['filt_subCat']."%";
}

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_Utlagg = sprintf("SELECT c.id AS id, c.users_id AS user, mc.name AS mainCat, sc.name AS subCat, c.categories_id, c.cost, DATE_FORMAT(c.date,'%%X-%%m-%%d') AS date, DATE_FORMAT(date,'%%M_%%Y') AS m_name, c.comment, l.name AS label FROM p_econ_costs AS c  INNER JOIN p_econ_categories AS sc ON c.categories_id = sc.id INNER JOIN p_econ_categories AS mc ON sc.parent_id = mc.id LEFT JOIN p_econ_labelcosts AS lc ON c.id = lc.costs_id LEFT JOIN p_econ_labels AS l ON lc.labels_id = l.id WHERE c.users_id LIKE %s AND mc.name LIKE %s AND sc.name LIKE %s AND DATE_FORMAT(c.date,'%%Y%%m') LIKE %s", GetSQLValueString($filt_user_Utlagg, "text"),GetSQLValueString($filt_mainCat_Utlagg, "text"),GetSQLValueString($filt_subCat_Utlagg, "text"),GetSQLValueString($filt_month_Utlagg, "text"));

if (isset($_GET['filt_label'])) {
  $query_Utlagg .= " AND lc.labels_id LIKE " . GetSQLValueString($_GET['filt_label'], "text");
}
$query_Utlagg = $query_Utlagg . " ORDER BY c.date, c.id";
//echo $query_Utlagg;
$Utlagg = mysql_query($query_Utlagg, $localhost_lerenius) or die(mysql_error());
$row_Utlagg = mysql_fetch_assoc($Utlagg);
$totalRows_Utlagg = mysql_num_rows($Utlagg);
// AND lc.labels_id LIKE %s ,GetSQLValueString($filt_label_Utlagg, "text")
$maxRows_users = 10;
$pageNum_users = 0;
if (isset($_GET['pageNum_users'])) {
  $pageNum_users = $_GET['pageNum_users'];
}
$startRow_users = $pageNum_users * $maxRows_users;

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_users = "SELECT name FROM p_econ_users ORDER BY name ASC";
$query_limit_users = sprintf("%s LIMIT %d, %d", $query_users, $startRow_users, $maxRows_users);
$users = mysql_query($query_limit_users, $localhost_lerenius) or die(mysql_error());
$row_users = mysql_fetch_assoc($users);

if (isset($_GET['totalRows_users'])) {
  $totalRows_users = $_GET['totalRows_users'];
} else {
  $all_users = mysql_query($query_users);
  $totalRows_users = mysql_num_rows($all_users);
}
$totalPages_users = ceil($totalRows_users/$maxRows_users)-1;

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_mainCats = "SELECT name FROM p_econ_categories WHERE parent_id IS NULL ORDER BY sort_order";
$mainCats = mysql_query($query_mainCats, $localhost_lerenius) or die(mysql_error());
$row_mainCats = mysql_fetch_assoc($mainCats);
$totalRows_mainCats = mysql_num_rows($mainCats);

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_months = "SELECT DISTINCT DATE_FORMAT(date,'%M_%Y') AS m_name, ";
$query_months .= "DATE_FORMAT(date,'%M') AS m_txt, ";
$query_months .= "DATE_FORMAT(date,'%m') AS m_num, ";
$query_months .= "DATE_FORMAT(date,'%Y') AS y_num ";
$query_months .= "FROM p_econ_costs ORDER BY y_num,m_num";
$months = mysql_query($query_months, $localhost_lerenius) or die(mysql_error());
$row_months = mysql_fetch_assoc($months);
$totalRows_months = mysql_num_rows($months);

mysql_select_db($database_localhost_lerenius, $localhost_lerenius);
$query_labels = "SELECT id AS l_id, name AS l_name FROM p_econ_labels ORDER BY l_name";
$labels = mysql_query($query_labels, $localhost_lerenius) or die(mysql_error());
$row_labels = mysql_fetch_assoc($labels);

$totalCost = 0;
$old_id = -1;
?>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=latin1" />
<title>Visa utl&auml;gg</title>
<link href="ekon_style.css" rel="stylesheet" type="text/css" />

<script language="javascript"> 

function setBgColor(bg_color) {
  document.body.style.backgroundColor = bg_color;
}

  //collapses a row
  function collapseRow(rowElement) {
    rowElement.style.display = 'none';
  }

  //the color black
  var BLACK ="#000000"; 
  //restores the color to black
  //expands the span section if it has been collapsed
  function restore(rowElement) {
  	rowElement.style.display = '';
  	rowElement.style.color = BLACK;
  }

  
  //checkes if a span element is part of an activated category
  //returns the first category (as an INPUT object) that a span belongs to  
  function isPartOfActiveCategory(rowElement) {
    var rowCategories = (rowElement.title).split("+");
    for (var i=0; i < rowCategories.length; i++) {
      if(document.getElementById(rowCategories[i])!=null) {
        if (document.getElementById(rowCategories[i]).checked==false) {
          return false;
        }
      }
    }
    return true;
  }
  
  //resets the entire page in normal form
  function resetPage() {
   var rows=document.getElementsByTagName("TR");
    for (var i=0; i < rows.length; i++) {
      restore(rows[i]); 
    }
  }
  var COLOR_CODE_BG_COLOR="#999999";
  var NONE = "none";
  var COLOR_CODE="color code";
  var GRAY_OUT="gray out";
  var COLLAPSE="collapse";

 //updates the page according to the settings
 function myUpdate() {
  if (mech != NONE) {
    // Get all spans
    var rows=document.getElementsByTagName("TR");
    var sum=0;
    // Loop through all spans
    for (var i=0; i < rows.length; i++) {
      // Check if span belongs to active category
      if (isPartOfActiveCategory(rows[i])) {
        restore(rows[i]);
        if(rows[i].cells.length > 4) {
          if(!isNaN(rows[i].cells[4].innerHTML)) {
            sum = sum + parseFloat(rows[i].cells[4].innerHTML);
          }
        }
      } else {
        collapseRow(rows[i]);
      }
    }
    document.getElementById('totalSum').innerHTML = (sum.toFixed(2)).toString();
  }
 }

  
  var mech=COLLAPSE; //default
  //sets the filtering mechanism used to filter the web page 
  function setFilteringMechanism(option) {
    if (option ==NONE) {
     resetPage();
     setBgColor('#ddddff');
    } else {
     resetPage();
     setBgColor('#ddffdd');
     myUpdate();
    }
  }
  
</script>

</head>

<body onLoad="setFilteringMechanism('collapse');">
<p>&nbsp;</p>
<table border="0" align="center">
<colgroup>
    <col width=80 />
    <col width=80 />
    <col width=100 />
    <col width=100 />
    <col width=100 />
    <col width=200 />
    <col width=100 />
</colgroup>
<tr>
<td colspan="7">
 <form>
 <table border="0" align="left">
  <tr>
    <td width="100">Filtrera på användare:</td>
     <?php do { ?>
       <td><?php printf("<input type='checkbox' id='user_%s' onChange='myUpdate();'>%s<br />",$row_users['name'],$row_users['name']); ?></td>
      <?php } while ($row_users = mysql_fetch_assoc($users)); ?>
  </tr>   
 </table>
 </td>
 </tr>
 <tr>
 <td colspan="7">
 <table border="0" align="left">
  <tr>
    <td width="100">Filtrera på kategori:</td>
    <?php do { ?>
       <td><?php printf("<input type='checkbox'  checked='true' id='cat_%s' onChange='myUpdate();'>%s<br />",$row_mainCats['name'],$row_mainCats['name']); ?></td>
    <?php } while ($row_mainCats = mysql_fetch_assoc($mainCats)); ?>
  </tr>
 </table>
 </td>
 </tr><tr>
 <td colspan="7">
 <table border="0" align="left">
  <tr>
    <td rowspan="3" valign="top" width="100">Filtrera på månad:</td>
    <?php
       $old_y=$row_months['y_num'];
       printf("<td>%s:</td>\n",$row_months['y_num']);
       do
       {
         if($old_y != $row_months['y_num'])
         {
           $old_y=$row_months['y_num'];
           printf("</tr><tr><td>%s:</td>\n",$row_months['y_num']);
         }
         printf("<td><input type='checkbox' id='month_%s' onChange='myUpdate();'>%s</td>",$row_months['m_name'],$row_months['m_txt']);
       } while ($row_months = mysql_fetch_assoc($months)); ?>
  </tr>
 </table>
 </td>
 </tr><tr>
 <td colspan="7">
 <table border="0" align="left">
  <tr>
    <td>Filtrera på label:</td>
    <?php do { ?>
      <td><?php printf("<input type='checkbox' id='label_%s' checked='true' onChange='myUpdate();'>%s<br />",$row_labels['l_name'],$row_labels['l_name']); ?></td>
    <?php } while ($row_labels = mysql_fetch_assoc($labels)); ?>
      <td><input type='checkbox' id='label_' checked='true' onChange='myUpdate();'>No Label<br /></td>
  </tr>
 </table>
 </td>
 </tr>
 </form>
 <tr>
         <td colspan="7">&nbsp;</td>
 </tr>
  <tr>
    <td>Datum</td>
    <td>Anv&auml;ndare</td>
    <td>Huvudkategori</td>
    <td>Underkategori</td>
    <td>Kostnad</td>
    <td>Kommentar</td>
    <td>Label</td>
  </tr>
       <?php do {
    if ($old_id != $row_Utlagg['id']) {
      ?>
    <tr title=<?php echo "\"user_".$row_Utlagg['user']."+month_".$row_Utlagg['m_name']."+cat_".$row_Utlagg['mainCat']."+label_".$row_Utlagg['label']."\"";?> \
        class=<?php echo "\"utlagg_".$row_Utlagg['user']."\""; ?>>
      <td><?php echo $row_Utlagg['date']; ?></td>
      <td><?php echo $row_Utlagg['user']; ?></td>
      <td><?php echo $row_Utlagg['mainCat']; ?></td>
      <td><?php echo $row_Utlagg['subCat']; ?></td>
      <td align="right"><?php printf("%.2f",$row_Utlagg['cost']); $totalCost += $row_Utlagg['cost'];?></td>
      <td><?php echo $row_Utlagg['comment']; ?></td>
      <td><?php echo $row_Utlagg['label']; ?></td>
    </tr>
     <?php } else { ?>
    <tr title=<?php echo "\"user_".$row_Utlagg['user']."+month_".$row_Utlagg['m_name']."+cat_".$row_Utlagg['mainCat']."+label_".$row_Utlagg['label']."\"";?> \ 
       class=<?php echo "\"utlagg_".$row_Utlagg['user']."\""; ?>>
      <td colspan="6">&nbsp;</td>
      <td><?php echo $row_Utlagg['label']; ?></td>
    </tr>
     <?php }
      $old_id = $row_Utlagg['id']; } while ($row_Utlagg = mysql_fetch_assoc($Utlagg));
     ?>
  <tr>
  <td colspan="4"><b>Totalt</b></td>
      <td align="right"><span id='totalSum' style="font-weight:bold">-</span></td>
      <td><b>kr</b></td>
    </tr>
    
</table>
</body>
</html>
<?php
mysql_free_result($Utlagg);

mysql_free_result($users);

mysql_free_result($mainCats);

mysql_free_result($months);

mysql_free_result($labels);
?>
