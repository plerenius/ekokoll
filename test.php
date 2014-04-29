<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body>
<?php
$result="<tr class=\"toggler overrideLinks\" data-toggletargets=\"#details00000002051032458001\">
					        <td class=\"first overflowEllipsis icon-caret-right\">
                                <div class=\"icon-caret-down\"></div>2013-05-29
                            </td>
					        <td class=\"overflowEllipsis\">Hyra Gbg Maj</td>
					        <td class=\"overflowEllipsis\">Övrigt</td>
					        <td class=\"overflowEllipsis\">Övrigt</td>
					        <td class=\"value overflowEllipsis negative\">-2 500,00 kr</td>
					        <td class=\"value last overflowEllipsis \">9 569,16 kr</td>
				        </tr>";
$preg_str="/<td class=\"first.*>.*<div.*><\/div>(.+?)<\/td>";//date
$preg_str.="\s*<td[^>]+>(.+?)<\/td>"; //text
$preg_str.="\s*<td[^>]+>[^<]+<\/td>"; //övrigt
$preg_str.="\s*<td[^>]+>[^<]+<\/td>"; //övrigt
$preg_str.="\s*<td[^>]+>(.+?)<\/td>/is"; // cost
//echo "<pre>".$preg_str."</pre>";
preg_match_all($preg_str,$result,$spendings);
//.*<td.*>(.+?)<\/td>.*<td.*>.*<\/td>.*<td.*>.*<\/td>.*<td.*>(.+?)<\/td>

echo "<table>$result</table>";
print_r($spendings);
$dates = $spendings[1];
$shops = $spendings[2];
$costs = $spendings[3];
foreach ($spendings[1] as $index=>$spending)
{
  echo "<p>".$dates[$index].": ".$costs[$index]." ".$shops[$index]."</p>";
}
?>
</body>
</html>