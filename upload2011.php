<?php
require_once 'library/db.php';
require_once 'library/database.php';

$uploadedStatus = 0;

if ( isset($_POST["submit"]) ) {


if ($_FILES["file"]["error"] > 0) {
echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
} // end of if

else {	
if (file_exists($_FILES["file"]["name"])) {
unlink($_FILES["file"]["name"]);
} //end of if

$storagename = $_FILES["file"]["name"];
move_uploaded_file($_FILES["file"]["tmp_name"],  "temporary/".$storagename);
$uploadedStatus = 1;
} // end of else


} // end of 1st if

?>





<table width="300" style="margin:115px auto; background:#f8f8f8; border:1px solid #eee; padding:10px;">

<form action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" enctype="multipart/form-data">

<tr><td style="font:bold 15px arial; text-align:center; padding:0 0 5px 0;">Upload Balance</td></tr>
<tr>
<td align="center" ><input type="file" name="file" id="file"  style="width:295px;"/></td>

</tr>

<tr>
<td  align="center"><input type="submit" name="submit" style="width:120px;" /></td>
</tr>

</table>

<?php if($uploadedStatus==1){


/************************ YOUR DATABASE CONNECTION START HERE   ****************************/

/*define ("DB_HOST", $dbHost); // set database host
define ("DB_USER", $dbUser); // set database user
define ("DB_PASS",""); // set database password
define ("DB_NAME", $dbName); // set database name

$link = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die("Couldn't make connection.");
$db = mysql_select_db(DB_NAME, $link) or die("Couldn't select database");*/

//$databasetable = "security_users";

/************************ YOUR DATABASE CONNECTION END HERE  ****************************/


set_include_path(get_include_path() . PATH_SEPARATOR . 'Classes/');
include 'PHPExcel/IOFactory.php';

// This is the file path to be uploaded.
$inputFileName = "temporary/".$storagename; 

try {
	$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
} catch(Exception $e) {
	die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
}


$allDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
$arrayCount = count($allDataInSheet);  // Here get total count of row in that Excel sheet
$tsav = 0;
$arfile = '';
$b = 0;

$tc_java = 0;
$tc_javascript = 0;
$tc_r = 0;
$tc_php = 0;
$tc_python = 0;
$tc_swift = 0;
$tc_chash = 0;
$tc_cplus = 0;
$tc_obj = 0;


$tc_win = 0;
$tc_lin = 0;
$tc_mac = 0;

for($i=2;$i<=$arrayCount;$i++) {
$c_obj = trim($allDataInSheet[$i]["AB"]);
$c_swift = trim($allDataInSheet[$i]["AC"]);
$c_java = trim($allDataInSheet[$i]["AE"]);
$c_javascript = trim($allDataInSheet[$i]["AF"]);
#$c_css = trim($allDataInSheet[$i]["AG"]);
$c_php = trim($allDataInSheet[$i]["AH"]);
$c_python = trim($allDataInSheet[$i]["AI"]);
#$c_ruby = trim($allDataInSheet[$i]["AJ"]);
#$c_sql = trim($allDataInSheet[$i]["AK"]);
$c_chash = trim($allDataInSheet[$i]["AL"]);
$c_cplus = trim($allDataInSheet[$i]["AM"]);
$c_r = trim($allDataInSheet[$i]["AP"]);
#$c_ser = strtolower(trim($allDataInSheet[$i]["AQ"]));
$c_os = strtolower(trim($allDataInSheet[$i]["AR"]));


if ($c_java != ''){
	$c_java = 1;	
} else {
	$c_java = 0;
}


if ($c_javascript != ''){
	$c_javascript = 1;
} else {
	$c_javascript = 0;
}


if ($c_php != ''){
	$c_php = 1;
} else {
	$c_php = 0;
}

if ($c_python != ''){
	$c_python = 1;
} else {
	$c_python = 0;
}


if ($c_chash != ''){
	$c_chash = 1;
} else {
	$c_chash = 0;
}

if ($c_cplus != ''){
	$c_cplus = 1;
} else {
	$c_cplus = 0;
}

if ($c_obj != ''){
	$c_obj = 1;
} else {
	$c_obj = 0;
}

if ($c_swift != ''){
	$c_swift = 1;
} else {
	$c_swift = 0;
}

if ($c_r != ''){
	$c_r = 1;
} else {
	$c_r = 0;
}

/*
//if ((strpos($c_ser, 'r ')) || ($c_ser == 'r')) {
if ((strpos($c_ser, 'r,') !== false) || (strpos($c_ser, ',r,') !== false) || ($c_ser == 'r')) {
	 $c_r = 1;
} else{
	 $c_r = 0;
}

if ((strpos($c_ser, 'objective' )) || ($c_ser == 'objective c'))  {
	 $c_obj = 1;
} else{
	 $c_obj = 0;
}

if ((strpos($c_ser, 'swift')) || ($c_ser == 'swift'))  {
	 $c_swift = 1;
} else{
	 $c_swift = 0;
}

*/



if (strpos($c_os, 'window') !== false) {
    $rc_os = 'W';
    $tc_win += 1;
} else if (strpos($c_os, 'linux') !== false) {
	$rc_os = 'L';
	$tc_lin += 1;
} else if (strpos($c_os, 'ubuntu') !== false) {
	$rc_os = 'L';
	$tc_lin += 1;
} else if (strpos($c_os, 'fedora') !== false) {
	$rc_os = 'L';
	$tc_lin += 1;
} else if (strpos($c_os, 'mint') !== false) {
	$rc_os = 'L';
	$tc_lin += 1;
} else if (strpos($c_os, 'debian') !== false) {
	$rc_os = 'L';
	$tc_lin += 1;
} else if (strpos($c_os, 'mac') !== false) {
	$rc_os = 'M';
	$tc_mac += 1;
}  else {
	$rc_os = '';
}





$tc_java += $c_java;
$tc_javascript += $c_javascript;
$tc_r += $c_r;
$tc_php += $c_php;
$tc_python += $c_python;
$tc_swift += $c_swift;
$tc_chash += $c_chash;
$tc_cplus += $c_cplus;
$tc_obj += $c_obj;





$c_year = 2015;




$insertTable3 = "INSERT INTO exctracttable(`c_java`, `c_javascript`, `c_r`, `c_php`, `c_python`, `c_swift`, `c_chash`, `c_cplus`, `c_obj`, `c_os`, `c_year`) VALUES ('$c_java','$c_javascript','$c_r','$c_php','$c_python','$c_swift','$c_chash','$c_cplus','$c_obj','$rc_os','$c_year')";

if(mysqli_query($db, $insertTable3)){
    //echo "Records inserted successfully.";
} else{
    //echo "ERROR: Could not able to execute $insertTable3. " . mysqli_error($db);
}

	

$msg = 'Record has been updated. <div style="Padding:20px 0 0 0;"><a href="">Go Back to tutorial</a></div>';
//unlink($inputFileName);
}

$tl = $tc_java + $tc_javascript + $tc_r + $tc_php + $tc_python + $tc_swift + $tc_chash + $tc_cplus + $tc_obj;
$to = $tc_mac + $tc_lin + $tc_win;


$tcp_java = ($tc_java/$tl) * 100;
$tcp_javascript = ($tc_javascript/$tl) * 100;
$tcp_r = ($tc_r/$tl) * 100;
$tcp_php = ($tc_php/$tl) * 100;
$tcp_python = ($tc_python/$tl) * 100;
$tcp_swift = ($tc_swift/$tl) * 100;
$tcp_chash = ($tc_chash/$tl) * 100;
$tcp_cplus = ($tc_cplus/$tl) * 100;
$tcp_obj = ($tc_obj/$tl) * 100;



$tcp_win = ($tc_win/$to) * 100;
$tcp_lin = ($tc_lin/$to) * 100;
$tcp_mac = ($tc_mac/$to) * 100;

$insertTable4= "INSERT INTO exctracttabletot(`c_java`, `c_javascript`, `c_r`, `c_php`, `c_python`, `c_swift`, `c_chash`, `c_cplus`, `c_obj`, `c_year`) VALUES ('$tc_java','$tc_javascript','$tc_r','$tc_php','$tc_python','$tc_swift','$tc_chash','$tc_cplus','$tc_obj','$c_year')";

if(mysqli_query($db, $insertTable4)){
    echo "Records inserted successfully4.";
} else{
    echo "ERROR: Could not able to execute $insertTable4. " . mysqli_error($db);
}

$insertTable5 = "INSERT INTO exctracttableperc(`c_java`, `c_javascript`, `c_r`, `c_php`, `c_python`, `c_swift`, `c_chash`, `c_cplus`, `c_obj`,  `c_year`) VALUES ('$tcp_java','$tcp_javascript','$tcp_r','$tcp_php','$tcp_python','$tcp_swift','$tcp_chash','$tcp_cplus','$tcp_obj','$c_year')";

if(mysqli_query($db, $insertTable5)){
    echo "Records inserted successfully5.";
} else{
    echo "ERROR: Could not able to execute $insertTable5. " . mysqli_error($db);
}

$insertTable6 = "INSERT INTO exctracttableostot(`c_win`, `c_lin`, `c_mac`, `c_year`) VALUES ('$tc_win','$tc_lin','$tc_mac','$c_year')";

if(mysqli_query($db, $insertTable6)){
    echo "Records inserted successfully6.";
} else{
    echo "ERROR: Could not able to execute $insertTable6. " . mysqli_error($db);
}

$insertTable3= "INSERT INTO exctracttableosperc(`c_win`, `c_lin`, `c_mac`, `c_year`) VALUES ('".$tcp_win."','".$tcp_lin."','".$tcp_mac."','".$c_year."')";

if(mysqli_query($db, $insertTable3)){
    //echo "Records inserted successfully.";
} else{
    //echo "ERROR: Could not able to execute $insertTable3. " . mysqli_error($db);
}


}
echo "<div style='font: bold 18px arial,verdana;padding: 0 0 0 160px;'>".@$msg.@$arfile."</div>";
//}
?>



</form>



