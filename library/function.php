<?php
function doLogin()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userName = $_POST['username'];
	$password = $_POST['password'];
	
	// first, make sure the username & password are not empty
	if ($userName == '') {
		$errorMessage = 'You must enter your username';
	} else if ($password == '') {
		$errorMessage = 'You must enter the password';
	} else {
		
			
		// check the database and see if the username and password combo do match
		$sql = "SELECT id, security_id
		        FROM security_users 
				WHERE username = '$userName' AND password = '$password'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$row = dbFetchAssoc($result);
			$_SESSION['user_id'] = $row['id'];
			$_SESSION['security_id'] = $row['security_id'];
			//$userId = $row['id'];
			
			
			/*$sql = "SELECT ent_id, surname, othername, email
		        	FROM enterpreneur 
					WHERE ent_id = '$entId' AND status != 'Suspend'";
			$result = dbQuery($sql);
					if (dbNumRows($result) == 1) {
						$row = dbFetchAssoc($result);
						$_SESSION['sound_id'] = $row['ent_id'];
						$email = $row['email'];
						$sur   = $row['surname']; 
						$other = $row['othername'];
						$name  = $sur.' '.$other; 
						// log the time when the user last login*/
						$sql = "UPDATE security_users
								SET last_login = NOW() 
								WHERE id = '{$row['id']}'";
						dbQuery($sql);
						
						 // log the user activity		   
						/*$sql    = "INSERT INTO tbl_activity (id, action, publisher_id, page, user_name, name, login) 
                 		   		   VALUES ('', 'Log in', '$entId', 'Enterprenuer', '$email', '$name', now())";
   			     		$result = dbQuery($sql) or die(mysql_error());*/
						header('Location: coop/');
						exit;

		} else {
			$errorMessage = 'Wrong username or password';
		}		
			
	}
	
	return $errorMessage;
}


function checkUser()
{
	// if the session id is not set, redirect to login page
	if (!isset($_SESSION['user_id'])) {
		header('Location: ../index.php');
		exit;
	}
	
	@$id = $_SESSION['user_id'];

	// the user want to logout
	if (isset($_GET['logout'])) {
		doLogout();
	}
	
	return $id;
}


function addAccountYear()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	@$start = $_POST['start'];
	$end 	= $_POST['end'];
	$userId = $_POST['userid'];
	// first, make sure the username & password are not empty
	if ($start == '') {
		$errorMessage = 'You must enter the start month';
	} else if ($end == '') {
		$errorMessage = 'You must enter the end month';
	} else {
		$sql = "SELECT id
		        FROM account_year
				WHERE start = '$start' OR end = '$end'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$errorMessage = 'Invalid entry';
		} else {
			$sql = "INSERT INTO account_year(current, start, end, created_id, created_on) 
                    VALUES ('1', '$start', '$end', '$userId', now())";
   			dbQuery($sql);
			
		
			$sql = "SELECT id FROM `account_year` ORDER BY id desc limit 1,1";
			$result = dbQuery($sql);
			$row = dbFetchAssoc($result);
			$id = $row['id'];

			
			$sql = "UPDATE account_year SET current = '0'
					WHERE id = '$id'";
			dbQuery($sql);
			
			/*$sqlup = "SELECT f.id as oldId 
					  FROM field_options f, account_year a 
					  WHERE a.end = f.field_name and a.id = '$id'";
			$resultup = dbQuery($sqlup);	
			$rowup = dbFetchAssoc($resultup);
			$oldId = $rowup['oldId'];*/
			
			// set admin balance to zero and reset admin charges for deduction at the end of accoount year
			$sql1 = "SELECT distinct(table_name), amount
		             FROM nick_name n,  fix_payment f
				     WHERE n.field_options_id = f.field_options_id AND n.field_options_id = '5'";
			$result1 = dbQuery($sql1);
			$row1 = dbFetchAssoc($result1);
			$nam = $row1['table_name'];
			$amt = $row1['amount'];
			
			$fbal = $nam.'_bal'; 
			
			$sql = "UPDATE deduction SET $nam = '$amt'";
			dbQuery($sql);
			
			/*$sql = "UPDATE monthly_payment SET $fbal = 0 where month_year = '$oldId'";
			dbQuery($sql);*/
			
			// set mosque balance to zero at the end of accoount year
			/*$sqlm = "SELECT table_name
		             FROM nick_name
				     WHERE field_options_id = '238'";
			$resultm = dbQuery($sqlm);
			$rowm = dbFetchAssoc($resultm);
			$mnam = $rowm['table_name'];
			
			$mbal = $mnam.'_bal';
			
			$sqlm = "UPDATE monthly_payment SET $mbal = 0 where month_year = '$oldId'";
			dbQuery($sqlm);*/
			
			$errorMessage = 'Account Year was successfully added';
		}		
	}

	
	return $errorMessage;
}


function addField()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	@$fieldName = $_POST['fieldname'];
	$fieldId = $_POST['fieldid'];
	$userId = $_POST['userid'];
	@$fieldValue = $_POST['fieldvalue'];
	
	// first, make sure the fieldname are not empty
	if ($fieldName == '') {
		$errorMessage = 'You must enter the field name';
	} else if (!preg_match("#^[a-zA-Z0-9äöüÄÖÜ ]+$#", $fieldName)) {
		$errorMessage = 'invalid entry';
	}else {
		
			
		// check the database and see if the username and password combo do match
		$sql = "SELECT id
		        FROM field_options 
				WHERE field_name = '$fieldName'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$errorMessage = 'The field name is already existing';
		} else {
			if ($fieldId == 3 ||  $fieldId == 4 ||  $fieldId == 5 || $fieldId == 10) {
				$sql = "INSERT INTO field_options(field_id, field_name, created_id, created_on) 
						VALUES ('$fieldId', '$fieldName', '$userId', now())";
							$result = dbQuery($sql) or die(mysql_error());
						
				$field = strtolower($fieldName);
				$tableName = preg_replace('/\s+/', '_', $field);
				$tableBalance = $tableName.'_bal';
				
				$fieldOptionsId = dbInsertId();
				
				$sql = "INSERT INTO nick_name(field_id, field_name, table_name, field_options_id) 
						VALUES ('$fieldId', '$fieldName', '$tableName', '$fieldOptionsId')";
							$result = dbQuery($sql) or die(mysql_error());
				
				$sql = "ALTER table monthly_payment ADD $tableName double NULL DEFAULT  '0', ADD $tableBalance double NULL DEFAULT  '0'";	
				$result = dbQuery($sql) or die(mysql_error());
				
				
				$sql = "ALTER table deduction ADD $tableName double NULL DEFAULT  '0'";	
				$result = dbQuery($sql) or die(mysql_error());
				
				
				$errorMessage = 'Payment type was successfully added';	
				
			} else if ($fieldId == 6){
					$sql11 = "SELECT id as accountid, start, end 
							  FROM account_year 
							  WHERE current = '1'";						
					$result11 = dbQuery($sql11);
					if (dbNumRows($result11) == 1) {
						$row11 = dbFetchAssoc($result11);
						$yearId = $row11['accountid'];
						$sql = "INSERT INTO field_options(field_id, field_name, created_id, created_on) 
								VALUES ('$fieldId', '$fieldName', '$userId', now())";
								dbQuery($sql);
						$fieldOptionsId = dbInsertId();
	
							/*$sql1 = "SELECT trust_id, pl_no FROM security_users WHERE security_id = 4";
							$result1 = dbQuery($sql1);
							while($row1 = dbFetchAssoc($result1)) {
							extract($row1);	
						
							$sql2 = "INSERT INTO monthly_payment(id, trust_id, pl_no, month_year, act_year_id, status, created_id, created_on) 
									VALUES ('', '$trust_id', '$pl_no', '$fieldOptionsId', '$yearId', '0', '$userId', now())";
							dbQuery($sql2);
							}*/
							
							/*$sqlOrg = "INSERT INTO org_monthly_payment(id, month_year, act_year_id, created_id, created_on) 
									VALUES ('', '$fieldOptionsId', '$yearId', '$userId', now())";
							dbQuery($sqlOrg);
							
							$sqlOrg1 = "SELECT table_name FROM nick_name WHERE field_id = 2";
							$resultOrg1 = dbQuery($sqlOrg1);	
							while($rowOrg1 = dbFetchAssoc($resultOrg1)) {
								extract($rowOrg1);
								$tableBal = $table_name.'_bal';
		
								$sqlOrg2 = "SELECT $tableBal as balance FROM org_monthly_payment order by id desc limit 1,1";
								$resultOrg2 = dbQuery($sqlOrg2);
								$rowOrg2 = dbFetchAssoc($resultOrg2);
								$balance = $rowOrg2['balance'];
								
								$sqlOrg3 = "UPDATE org_monthly_payment 
										   SET $tableBal = '$balance' WHERE month_year = '$fieldOptionsId'";
								dbQuery($sqlOrg3);
								
							}*/
						
						
							
							$errorMessage = 'The month session was successfully added';
						
					} else {
						$errorMessage = 'No Account Year Found';
					}
				//}
				} else {
					$sql = "INSERT INTO field_options(field_id, field_name, created_id, created_on) 
							VALUES ('$fieldId', '$fieldName', '$userId', now())";
								$result = dbQuery($sql) or die(mysql_error());
								
					$errorMessage = 'Field was successfully added';
				}
			
			}
				
			
	}
	
	return $errorMessage;
}

function editField()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldName = $_POST['fieldname'];
	$id = $_POST['id'];
	$userId = $_POST['userid'];
	$fieldId = $_POST['fieldid'];
	
	// first, make sure the fieldname are not empty
	if ($fieldName == '') {
		$errorMessage = 'You must enter the field name';
	} else {
			
		// check the database and see if the username and password combo do match
		$sql = "SELECT id
		        FROM field_options 
				WHERE field_name = '$fieldName'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$errorMessage = 'The field name is already existing';
		} else {

			if ($fieldId == 3 || $fieldId == 5 || $fieldId == 10 || $fieldId == 11 || $fieldId == 14 || $fieldId == 4 || $fieldId == 2){
						
				$sql = "UPDATE field_options
						SET field_name = '$fieldName', modify_id = '$userId', modify_on = NOW() 
						WHERE id = '$id'";
						dbQuery($sql);
					
				$field = strtolower($fieldName);
				$tableName = preg_replace('/\s+/', '_', $field);
				$tableBalance = $tableName.'_bal';
			
				$sql = "SELECT table_name, field_name
		        		FROM nick_name 
						WHERE field_options_id = '$id'";
				$result = dbQuery($sql);
				$row = dbFetchAssoc($result);	
				$oldTableName = $row['table_name'];
				$oldTableBalance = $row['table_name'].'_bal';
				$oldFieldName = $row['field_name'];		
							
				$sql = "ALTER table monthly_payment CHANGE $oldTableName $tableName double NULL DEFAULT  '0', CHANGE $oldTableBalance $tableBalance double NULL DEFAULT  '0'";	
						dbQuery($sql);
				
				$sql = "ALTER table deduction CHANGE $oldTableName $tableName double NULL DEFAULT  '0'";	
				$result = dbQuery($sql) or die(mysql_error());
				
				$sql = "UPDATE nick_name
						SET field_name = '$fieldName', table_name = '$tableName' 
						WHERE field_options_id = '$id'";
						dbQuery($sql);
						
							
				if ($fieldId == 2){
					$sql = "ALTER table org_monthly_payment CHANGE $oldTableName $tableName double NULL DEFAULT  '0', CHANGE $oldTableBalance $tableBalance double NULL DEFAULT  '0'";	
					
					$result = dbQuery($sql) or die(mysql_error());
				}
						
				$errorMessage = 'Payment type was successfully updated';
			}/* else if ($fieldId == 6){
				$sql = "UPDATE field_options
						SET field_name = '$fieldName', modify_id = '$userId', modify_on = NOW() 
						WHERE id = '$id'";
						dbQuery($sql);
				
				$sql = "INSERT INTO field_options(id, field_id, field_name, created_id, created_on) 
						VALUES ('', '$fieldId', '$fieldName', '$userId', now())";
						dbQuery($sql);
				$fieldOptionsId = dbInsertId();
				
				$sql1 = "SELECT trust_id, pl_no FROM security_users WHERE security_id = 4";
				$result1 = dbQuery($sql1);
				while($row1 = dbFetchAssoc($result1)) {
				extract($row1);	
				
				$sql2 = "INSERT INTO monthly_payment(id, trust_id, pl_no, month_year, status, created_id, created_on) 
						VALUES ('', '$trust_id', '$pl_no', '$fieldOptionsId', '0', '$userId', now())";
						dbQuery($sql2);
						
				}
				
			}*/ else {
				$sql = "UPDATE field_options
						SET field_name = '$fieldName', modify_id = '$userId', modify_on = NOW() 
						WHERE id = '$id'";
						dbQuery($sql);	
						$errorMessage = 'Field was successfully updated';	
			}
			
			
		}		
			
	}
	
	return $errorMessage;
}

function loanRefree($id){
	$sql = "SELECT first_name, last_name
		    FROM security_users 
			WHERE pl_no = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['first_name'].' '.$row['last_name'];
		return $name;
}

function modifyname($id){
	$sql = "SELECT first_name, last_name
		    FROM security_users 
			WHERE id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['first_name'].' '.$row['last_name'];
		return $name;
}

function plName($id){
	$sql = "SELECT first_name, middle_name, last_name
		    FROM security_users 
			WHERE pl_no = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['first_name'].' '.$row['last_name'].' '.$row['middle_name'];
		return $name;
}

function createdName($id){
	$sql = "SELECT first_name, last_name
		    FROM security_users 
			WHERE id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['first_name'].' '.$row['last_name'];
		return $name;
}

function stateName($id){
	$sql = "SELECT field_name
		    FROM field_options 
			WHERE id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['field_name'];
		return $name;
}

function titleName($id){
	$sql = "SELECT field_name
		    FROM field_options 
			WHERE id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['field_name'];
		return $name;
}

function OrgName($name){
	$sql = "SELECT field_name
		    FROM nick_name 
			WHERE table_name = '$name'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['field_name'];
		return $name;
}

function LoanName($id){
	$sql = "SELECT field_name
		    FROM field_options 
			WHERE id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['field_name'];
		return $name;
}

function MonthYear($id){
	$sql = "SELECT field_name
		    FROM field_options 
			WHERE id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['field_name'];
		return $name;
}

function MonthID($name){
	$sql = "SELECT id
		    FROM field_options 
			WHERE field_name = '$name'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$id = $row['id'];
		return $id;
}


function loanID($table_name){
	$sql = "SELECT field_options_id 
			FROM nick_name 
			WHERE table_name = '$table_name'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$loanId = $row['field_options_id'];
		return $loanId;
}

function LoanFieldID($table_name){
	$sql = "SELECT field_id 
			FROM nick_name 
			WHERE table_name = '$table_name'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$loanFieldID = $row['field_id'];
		return $loanFieldID;
}

function ImageName($id){
	$sql = "SELECT field_name
		    FROM field_options 
			WHERE id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['field_name'];
		return $name;
}

function fieldName($id){
	$sql = "SELECT field_name
		    FROM field_options 
			WHERE id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['field_name'];
		return $name;
}

function MemberTypeName($id){
	$sql = "SELECT member_name
		    FROM member_type 
			WHERE member_id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['member_name'];
		return $name;
}

function tableName($id){
	$sql = "SELECT  table_name
		    FROM nick_name 
			WHERE field_options_id = '$id'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$name = $row['table_name'];
		return $name;
}


function birthday($birthday){ 
    $age = strtotime($birthday);
    
    if($age === false){ 
        return false; 
    } 
    
    list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age)); 
	
    $now = strtotime("now"); 
    list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now)); 
	
    $age = $y2 - $y1; 
    
    if((int)($m2.$d2) < (int)($m1.$d1)) 
        $age -= 1; 
       $retire = 65 -  $age;
	   
	 $retiret =  $y2 + $retire;
	 $tret = $retiret - $y1;
	 if ($tret > 65){
     return ($retiret-1).'-'.$m1.'-'.$d1; 
	 } else {
		 return $retiret.'-'.$m1.'-'.$d1;
	 }
} 


function service($service){ 
    $age = strtotime($service);
    
    if($age === false){ 
        return false; 
    } 
    
    list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age)); 
	
    $now = strtotime("now"); 
    list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now)); 
	
    $age = $y2 - $y1; 
    
    if((int)($m2.$d2) < (int)($m1.$d1)) 
        $age -= 1; 
       	return $age;
} 


function addLoan()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldId     = $_POST['fieldid'];
	/*$minAmount = $_POST['minAmount'];
	$maxAmount   = $_POST['maxAmount'];*/
	/*$month 	     = $_POST['month'];*/
	$per   		 = $_POST['percent'];
	$userId    	 = $_POST['userid'];
	$percent     = $per/100;
	// first, make sure the fieldname are not empty
	if ($fieldId == '') {
		$errorMessage = 'You must enter the loan type';
	/*} else if ($minAmount == '') {
		$errorMessage = 'You must enter the minimum amount';
	} else if ($maxAmount == '') {
		$errorMessage = 'You must enter the maximum amount';*/
	}else {
		// check the database and see if the username and password combo do match
		$sql = "SELECT id
		        FROM loan_list
				WHERE field_options_id = '$fieldId'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$errorMessage = 'The field name is already existing';
		} else {
			$sql = "INSERT INTO loan_list(field_options_id,  percent, created_id, created_on) 
                    VALUES ('$fieldId', '$percent', '$userId', now())";
   			     		$result = dbQuery($sql);
			
			$errorMessage = 'Loan type was successfully added';
		}		
			
	}
	
	return $errorMessage;
}

function editLoan()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldId     = $_POST['fieldid'];
	/*$minAmount = $_POST['minAmount'];
	$maxAmount   = $_POST['maxAmount'];
	$month       = $_POST['month'];*/
	$per         = $_POST['percent'];
	$userId      = $_POST['userid'];
	$listId      = $_POST['listid'];
	$percent     = $per/100;
	// first, make sure the fieldname are not empty
	if ($fieldId == '') {
		$errorMessage = 'You must enter the loan type';
	/*} else if ($minAmount == '') {
		$errorMessage = 'You must enter the minimum amount';
	} else if ($maxAmount == '') {
		$errorMessage = 'You must enter the maximum amount';*/
	}else {			
			$sql = "UPDATE loan_list
					SET field_options_id = '$fieldId', percent = '$percent', modify_id = '$userId', modify_on = NOW() 
					WHERE id = '$listId'";
					dbQuery($sql);
			
			$errorMessage = 'loan list was successfully updated';
	}		
	
	return $errorMessage;
}


function addInvestment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldId     = $_POST['fieldid'];
	/*$minAmount = $_POST['minAmount'];
	$maxAmount   = $_POST['maxAmount'];*/
	$month 	     = $_POST['month'];
	$per   		 = $_POST['percent'];
	$userId    	 = $_POST['userid'];
	$percent     = $per/100;
	// first, make sure the fieldname are not empty
	if ($fieldId == '') {
		$errorMessage = 'You must enter the investment type';
	/*} else if ($minAmount == '') {
		$errorMessage = 'You must enter the minimum amount';
	} else if ($maxAmount == '') {
		$errorMessage = 'You must enter the maximum amount';*/
	}else {
		// check the database and see if the username and password combo do match
		$sql = "SELECT id
		        FROM inv_list
				WHERE field_options_id = '$fieldId'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$errorMessage = 'The field name is already existing';
		} else {
			$sql = "INSERT INTO inv_list(field_options_id, month,  percent, created_id, created_on) 
                    VALUES ('$fieldId', '$month', '$percent', '$userId', now())";
   			     		$result = dbQuery($sql);
			
			$errorMessage = 'Investment type was successfully added';
		}		
			
	}
	
	return $errorMessage;
}

function editInvestment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldId     = $_POST['fieldid'];
	/*$minAmount = $_POST['minAmount'];
	$maxAmount   = $_POST['maxAmount'];*/
	$month       = $_POST['month'];
	$per         = $_POST['percent'];
	$userId      = $_POST['userid'];
	$listId      = $_POST['listid'];
	$percent     = $per/100;
	// first, make sure the fieldname are not empty
	if ($fieldId == '') {
		$errorMessage = 'You must enter the investment type';
	/*} else if ($minAmount == '') {
		$errorMessage = 'You must enter the minimum amount';
	} else if ($maxAmount == '') {
		$errorMessage = 'You must enter the maximum amount';*/
	}else {			
			$sql = "UPDATE inv_list
					SET field_options_id = '$fieldId', month = '$month', percent = '$percent', modify_id = '$userId', modify_on = NOW() 
					WHERE id = '$listId'";
					dbQuery($sql);
			
			$errorMessage = 'Investment list was successfully updated';
	}		
	
	return $errorMessage;
}




function addFixPayment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldId   = $_POST['fieldid'];
	$amount = $_POST['amount'];
	$userId    = $_POST['userid'];
	/*$membershipId    = $_POST['membershipid'];
	$levelId    = $_POST['levelid'];*/
	
	// first, make sure the fieldname are not empty
	if ($fieldId == '') {
		$errorMessage = 'You must select fix payment name';
	} else if ($amount == '') {
		$errorMessage = 'You must enter the amount';
	} else {
		
		$sql1 = "SELECT id
		        FROM fix_payment
				WHERE field_options_id = '$fieldId'";
		$result1 = dbQuery($sql1);
		if (dbNumRows($result1) == 1) {
			$errorMessage = 'The field name is already existing';
		} else {

			$sql3 = "SELECT table_name FROM nick_name n, field_options f WHERE n.field_name = f.field_name AND f.id = '$fieldId'";
			$result3 = dbQuery($sql3);
			$row3 = dbFetchAssoc($result3);
			$tableName = $row3['table_name'];

			$sql4 = "SELECT  pl_no FROM security_users WHERE security_id = '4'";
			$result4 = dbQuery($sql4);
			//if (dbNumRows($result4) > 0) {	
				while($row4 = dbFetchAssoc($result4)) {
					extract($row4);
			
						$sql = "UPDATE deduction
								SET $tableName = '$amount' 
								WHERE  pl_no = '$pl_no'";
								dbQuery($sql);
				}
				
				// Insert into fix payment
				$sql2 = "INSERT INTO fix_payment(field_options_id, amount, created_id, created_on) 
                     VALUES ('$fieldId', '$amount', '$userId', now())";
   					 dbQuery($sql2);
				
				$errorMessage = 'The fix amount was successfully added';
			//} else {
			//$errorMessage = 'No member on the system';
			//}	
		}		
			
	}
	
	return $errorMessage;
}

function editFixPayment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldId   = $_POST['fieldid'];
	$amount    = $_POST['amount'];
	/*$membershipId    = $_POST['membershipid'];
	$levelId    = $_POST['levelid'];*/
	$userId    = $_POST['userid'];
	$fixId    = $_POST['fixid'];
	
	// first, make sure the fieldname are not empty
	if ($fieldId == '') {
		$errorMessage = 'You must select fix payment name';
	} else if ($amount == '') {
		$errorMessage = 'You must enter the amount';
	} else {	
		$sql1 = "SELECT id
		        FROM fix_payment
				WHERE field_options_id = '$fieldId' AND amount = '$amount'";
		$result1 = dbQuery($sql1);
		if (dbNumRows($result1) == 1) {
			$errorMessage = 'The field name is already existing';
		} else {
	
			$sql3 = "SELECT table_name FROM nick_name n, field_options f WHERE n.field_name = f.field_name AND f.id = '$fieldId'";
			$result3 = dbQuery($sql3);
			$row3 = dbFetchAssoc($result3);
			$tableName = $row3['table_name'];

			$sql4 = "SELECT  pl_no FROM security_users WHERE security_id = 4";
			$result4 = dbQuery($sql4);
				
				while($row4 = dbFetchAssoc($result4)) {
					extract($row4);
			
						$sql = "UPDATE deduction
								SET $tableName = '$amount' 
								WHERE pl_no = '$pl_no'";
								dbQuery($sql);
				}
					
			$sql = "UPDATE fix_payment
					SET field_options_id = '$fieldId', amount = '$amount', modify_id = '$userId', modify_on = NOW() 
					WHERE id = '$fixId'";
					dbQuery($sql);
			
			$errorMessage = 'Fix payment was successfully updated';
		
	}	
	}
	
	return $errorMessage;
}


function addNewPayment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldId   = $_POST['fieldid'];
	$amount = $_POST['amount'];
	$userId    = $_POST['userid'];
	
	// first, make sure the fieldname are not empty
	if ($fieldId == '') {
		$errorMessage = 'You must select new payment name';
	} else if ($amount == '') {
		$errorMessage = 'You must enter the amount';
	} else {
		
		$sql1 = "SELECT id
		        FROM new_payment
				WHERE field_options_id = '$fieldId'";
		$result1 = dbQuery($sql1);
		if (dbNumRows($result1) == 1) {
			$errorMessage = 'The field name is already existing';
		} else {

			$sql3 = "SELECT table_name FROM nick_name n, field_options f WHERE n.field_name = f.field_name AND f.id = '$fieldId'";
			$result3 = dbQuery($sql3);
			$row3 = dbFetchAssoc($result3);
			$tableName = $row3['table_name'];

			$sql4 = "SELECT  pl_no FROM security_users WHERE security_id = 4 AND new_status = 'new'";
			$result4 = dbQuery($sql4);
			//if (dbNumRows($result4) > 0) {	
				while($row4 = dbFetchAssoc($result4)) {
					extract($row4);
			
						$sql = "UPDATE deduction
								SET $tableName = '$amount' 
								WHERE pl_no = '$pl_no'";
								dbQuery($sql);
				}
				
				// Insert into fix payment
				$sql2 = "INSERT INTO new_payment(field_options_id, amount, created_id, created_on) 
                     VALUES ('$fieldId', '$amount', '$userId', now())";
   					 dbQuery($sql2);
				
				$errorMessage = 'The new payment amount was successfully added';
			//} else {
			//$errorMessage = 'No member on the system';
			//}	
		}		
			
	}
	
	return $errorMessage;
}

function editNewPayment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$fieldId   = $_POST['fieldid'];
	$amount    = $_POST['amount'];
	$userId    = $_POST['userid'];
	$newId    = $_POST['newid'];
	
	// first, make sure the fieldname are not empty
	if ($fieldId == '') {
		$errorMessage = 'You must select new payment name';
	} else if ($amount == '') {
		$errorMessage = 'You must enter the amount';
	} else {	
			$sql3 = "SELECT table_name FROM nick_name n, field_options f WHERE n.field_name = f.field_name AND f.id = '$fieldId'";
			$result3 = dbQuery($sql3);
			$row3 = dbFetchAssoc($result3);
			$tableName = $row3['table_name'];

			$sql4 = "SELECT  pl_no FROM security_users WHERE security_id = 4 AND new_status = 'new'";
			$result4 = dbQuery($sql4);
				
				while($row4 = dbFetchAssoc($result4)) {
					extract($row4);
			
						$sql = "UPDATE deduction
								SET $tableName = '$amount' 
								WHERE pl_no = '$pl_no'";
								dbQuery($sql);
				}
					
			$sql = "UPDATE new_payment
					SET field_options_id = '$fieldId', amount = '$amount', modify_id = '$userId', modify_on = NOW() 
					WHERE id = '$newId'";
					dbQuery($sql);
			
			$errorMessage = 'new payment was successfully updated';
		
	}		
	
	return $errorMessage;
}


function addReceipt()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';

	$name = $_POST['name'];
	$userId    = $_POST['userid'];
	
	// first, make sure the fieldname are not empty
	if ($name == '') {
		$errorMessage = 'You must enter name';
	} else {
		
		$sql1 = "SELECT id
		        FROM receipt
				WHERE name = '$name'";
		$result1 = dbQuery($sql1);
		if (dbNumRows($result1) == 1) {
			$errorMessage = 'The receipt name is already existing';
		} else {

			
				// Insert into receipt
				$sql2 = "INSERT INTO receipt(name, created_id, created_on) 
                     	 VALUES ('$name', '$userId', now())";
   					 dbQuery($sql2);
				
				$errorMessage = 'The receipt name was successfully added';

		}		
			
	}
	
	return $errorMessage;
}

function editReceipt()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$name    = $_POST['name'];
	$userId    = $_POST['userid'];
	$newId    = $_POST['newid'];
	
	// first, make sure the fieldname are not empty
	if ($name == '') {
		$errorMessage = 'You must enter name';
	} else {	
			$sql = "UPDATE receipt
					SET name = '$name', modify_id = '$userId', modify_on = NOW() 
					WHERE id = '$newId'";
					dbQuery($sql);
			
			$errorMessage = 'receipt name was successfully updated';
		
	}		
	
	return $errorMessage;
}



function addWithdraw()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';

	$name = $_POST['name'];
	$userId    = $_POST['userid'];
	
	// first, make sure the fieldname are not empty
	if ($name == '') {
		$errorMessage = 'You must enter name';
	} else {
		
		$sql1 = "SELECT id
		        FROM withdraw
				WHERE name = '$name'";
		$result1 = dbQuery($sql1);
		if (dbNumRows($result1) == 1) {
			$errorMessage = 'The withdraw name is already existing';
		} else {

			
				// Insert into withdraw
				$sql2 = "INSERT INTO withdraw(name, created_id, created_on) 
                     	 VALUES ('$name', '$userId', now())";
   					 dbQuery($sql2);
				
				$errorMessage = 'The withdraw name was successfully added';

		}		
			
	}
	
	return $errorMessage;
}

function editWithdraw()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$name    = $_POST['name'];
	$userId    = $_POST['userid'];
	$newId    = $_POST['newid'];
	
	// first, make sure the fieldname are not empty
	if ($name == '') {
		$errorMessage = 'You must select new payment name';
	} else {	
			$sql = "UPDATE withdraw
					SET name = '$name', modify_id = '$userId', modify_on = NOW() 
					WHERE id = '$newId'";
					dbQuery($sql);
			
			$errorMessage = 'withdraw name was successfully updated';
		
	}		
	
	return $errorMessage;
}

function addInflow()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';

	$infid = $_POST['infId'];
	$inflowDetail = $_POST['inflowDetail'];
	$userId    = $_POST['userid'];
	$amount = $_POST['amount'];
	@$misc = $_POST['misc'];
	$monthId    = $_POST['monthyear'];
	$yearId    = $_POST['yearid'];
	
	// first, make sure the fieldname are not empty
	if ($infid == '') {
		$errorMessage = 'You must select the type of inflow';
	} else {
		$sql = "SELECT field_id
		        FROM field_options where id = '$infid'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$fieldId = $row['field_id'];
		
				// Insert into withdraw
				$sql2 = "INSERT INTO inflow(inf_id, field_id, detail, amount, misc, month_year, act_year_id, created_id, created_on) 
                     	 VALUES ('$infid', '$fieldId', '$inflowDetail', '$amount', '$misc', '$monthId', '$yearId', '$userId', now())";
   					 dbQuery($sql2);
				
				$errorMessage = 'The general inflow was successfully added';

				
			
	}
	
	return $errorMessage;
}


function addUpkeep()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';

	$upid = $_POST['upId'];
	$upkeepDetail = $_POST['upkeepDetail'];
	$userId    = $_POST['userid'];
	$amount = $_POST['amount'];
	@$misc = $_POST['misc'];
	$monthId    = $_POST['monthyear'];
	$yearId    = $_POST['yearid'];
	
	// first, make sure the fieldname are not empty
	if ($upid == '') {
		$errorMessage = 'You must select upkeep type';
	} else {
		$sql = "SELECT field_id
		        FROM field_options where id = '$upid'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$fieldId = $row['field_id'];
		
				// Insert into withdraw
				$sql2 = "INSERT INTO bus_upkeep(up_id, field_id, detail, amount, misc, month_year, act_year_id, created_id, created_on) 
                     	 VALUES ('$upid', '$fieldId', '$upkeepDetail', '$amount', '$misc', '$monthId', '$yearId', '$userId', now())";
   					 dbQuery($sql2);
				
				$errorMessage = 'The general outflow was successfully added';

				
			
	}
	
	return $errorMessage;
}

/*
function addInvestment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';

	$invid = $_POST['invId'];
	$invDetail = $_POST['invDetail'];
	$userId    = $_POST['userid'];
	$amount = $_POST['amount'];
	@$misc = $_POST['misc'];
	$monthId    = $_POST['monthyear'];
	$yearId    = $_POST['yearid'];
	
	// first, make sure the fieldname are not empty
	if ($invid == '') {
		$errorMessage = 'You must select investment type';
	} else {
		$sql = "SELECT field_id
		        FROM field_options where id = '$invid'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$fieldId = $row['field_id'];
		
				// Insert into withdraw
				$sql2 = "INSERT INTO investment(id, inv_id, field_id, detail, amount, misc, month_year, act_year_id, created_id, created_on) 
                     	 VALUES ('', '$invid', '$fieldId', '$invDetail', '$amount', '$misc', '$monthId', '$yearId', '$userId', now())";
   					 dbQuery($sql2);
				
				$errorMessage = 'The investment was successfully added';

				
			
	}
	
	return $errorMessage;
}
*/

function addFinance()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$fid = $_POST['fId'];
	$firstname = $_POST['firstname'];
	$middlename = $_POST['middlename'];
	$lastname = $_POST['lastname'];
	$haddress = $_POST['haddress'];
	$tel = $_POST['tel'];
	$busname = $_POST['busname'];
	$busaddress = $_POST['busaddress'];
	$cname = $_POST['cname'];
	$caddress = $_POST['caddress'];
	$plocation = $_POST['plocation'];
	$pdetail = $_POST['pdetail'];
	$ramount = $_POST['ramount'];
	$eamount = $_POST['eamount'];
	
	/*$bcharges = $_POST['bcharges'];
	$cfee = $_POST['cfee'];
	
	$percentage = $_POST['percentage'];*/
	$edate = $_POST['edate'];
	$monthId = $_POST['monthyear'];
	$userId    = $_POST['userid'];
	$yearId = $_POST['yearid'];
	
	// first, make sure the fieldname are not empty
	if ($fid == '') {
		$errorMessage = 'You must select investment type';
	} else {
		$sql = "SELECT field_id
		        FROM field_options where id = '$fid'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$fieldId = $row['field_id'];
		
		/*$tprofit = $eamount - $ramount;
		$profit = ($percentage/100) * $tprofit;
		
		$tpayback = $profit + $bcharges + $cfee + $ramount;*/
		
				// Insert into finance				 
		$sql2 = "INSERT INTO `finance`(`f_id`, `field_id`, `first_name`, `middle_name`, `last_name`, `h_address`, `tel`, `bus_name`, `bus_address`, `client_name`, `client_address`, `project_location`, `project_detail`, `amount_requested`, `expected_amount`, `exp_date`, `month_year`, `act_year_id`, `status`, `created_id`, `created_on`) VALUES ('$fid','$fieldId','$firstname','$middlename','$lastname','$haddress','$tel','$busname','$busaddress','$cname','$caddress','$plocation','$pdetail','$ramount','$eamount','$edate','$monthId','$yearId','1','$userId',now())";
   		dbQuery($sql2);
				
		$id = dbInsertId();
		
		$sql3 = "INSERT INTO `finance_payment`(`fin_id`, `payback_amount`, `balance`, `act_year_id`) VALUES ('$id','$eamount','$eamount','$yearId')";
   		dbQuery($sql3);
		
		$errorMessage = 'The Finance was successfully added';

				
			
	}
	
	return $errorMessage;
}


function addPayment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$finid = $_POST['finId'];
	$amount = $_POST['amount'];
	$monthId = $_POST['monthyear'];
	$userId    = $_POST['userid'];
	$yearId    = $_POST['yearid'];
	
	// first, make sure the fieldname are not empty
	if ($amount == '') {
		$errorMessage = 'You must enter the amount';
	} else {
		$sql = "SELECT balance 
		        FROM `finance_payment` where fin_id = '$finid' order by pay_id desc limit 1";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$balance1 = $row['balance'];
		$balance2 = $balance1 - $amount;
		
		
		if ($balance2 >= 0){
		
		if ($balance2 == 0){
			$sql3 = "INSERT INTO `finance_payment`(`fin_id`, `payback_amount`, `payment`, `balance`, `month_year`, `act_year_id`, `date_comp`, `created_id`, `created_on`) VALUES ('$finid','$balance1','$amount', '$balance2', '$monthId', '$yearId', now(), '$userId', now())";
			$sqlup = "UPDATE finance SET status = 0 WHERE id = '$finid'";	
			dbQuery($sqlup);
		} else {
			$sql3 = "INSERT INTO `finance_payment`(`fin_id`, `payback_amount`, `payment`, `balance`, `month_year`, `act_year_id`, `created_id`, `created_on`) VALUES ('$finid','$balance1','$amount', '$balance2', '$monthId', '$yearId', '$userId', now())";
		}
			dbQuery($sql3);
			$errorMessage = 'The Payment was successfully added';
		
		} else {
			$errorMessage = 'The amount enter is more than balance';
		}
				// Insert into finance				 		
	}
	
	return $errorMessage;
}


function addExternal()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$cid = $_POST['cId'];
	$firstname = $_POST['firstname'];
	$middlename = $_POST['middlename'];
	$lastname = $_POST['lastname'];
	$haddress = $_POST['haddress'];
	$tel = $_POST['tel'];
	$cdetail = $_POST['cdetail'];
	$camount = $_POST['camount'];
	$samount = $_POST['samount'];
	$period = $_POST['period'];
	$monthId = $_POST['monthyear'];
	$yearId = $_POST['yearid'];
	$userId    = $_POST['userid'];
	
	// first, make sure the fieldname are not empty
	if ($cid == '') {
		$errorMessage = 'You must select commodity type';
	} else {
		$sql = "SELECT field_id
		        FROM field_options where id = '$cid'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$fieldId = $row['field_id'];
		
		//$profit = $samount - $camount;
		$schedule = $samount/$period;
		
		
				// Insert into external				 
		$sql2 = "INSERT INTO `external`(`com_id`, `field_id`, `first_name`, `middle_name`, `last_name`, `h_address`, `tel`, `com_detail`, `cost_amount`, `selling_amount`, `period`, `schedule`, `act_year_id`, `month_year`, `status`, `created_id`, `created_on`) VALUES ('$cid','$fieldId','$firstname','$middlename','$lastname','$haddress','$tel','$cdetail','$camount','$samount','$period','$profit','$schedule','$yearId','$monthId', '1', '$userId',now())";
   		dbQuery($sql2);
				
		
		
		$errorMessage = 'The external commodity was successfully added';

				
			
	}
	
	return $errorMessage;
}


function addExternalPayment()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$cid = $_POST['cId'];
	$amount = $_POST['amount'];
	$monthId = $_POST['monthyear'];
	$userId  = $_POST['userid'];
	$yearId  = $_POST['yearid'];	
	
	// first, make sure the fieldname are not empty
	if ($amount == '') {
		$errorMessage = 'You must enter the amount';
	} else {
		$sql = "SELECT balance 
		        FROM `external_payment` where c_id = '$cid' order by pay_id desc limit 1";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		if (@$_POST['samount']) {
			$balance1 = $_POST['samount'];
			$balance2 = $balance1 - $amount;
		} else {
			$balance1 = $row['balance'];
			$balance2 = $balance1 - $amount;
		}
		
		if ($balance2 >= 0){
		
		if ($balance2 == 0){
			$sql3 = "INSERT INTO `external_payment`(`c_id`, `amount`, `payment`, `balance`, `month_year`, `act_year_id`, `date_comp`, `created_id`, `created_on`) VALUES ('$cid','$balance1','$amount', '$balance2', '$monthId', '$yearId', now(), '$userId', now())";
			$sqlup = "UPDATE external set status = 0 WHERE id = '$cid'";
			dbQuery($sqlup);
		} else {
			$sql3 = "INSERT INTO `external_payment`(`c_id`, `amount`, `payment`, `balance`, `month_year`, `act_year_id`, `created_id`, `created_on`) VALUES ('$cid','$balance1','$amount', '$balance2', '$monthId', '$yearId', '$userId', now())";
		}
			dbQuery($sql3);
			$errorMessage = 'The Payment was successfully added';
		
		} else {
			$errorMessage = 'The amount enter is more than the balance';
		}
				// Insert into finance				 		
	}
	
	return $errorMessage;
}



function addAdmin()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	$titleid   	  = $_POST['titleid'];
	$firstname 	  = $_POST['firstname'];	
	$middlename   = $_POST['middlename'];
	$lastname     = $_POST['lastname'];
	$username     = $_POST['username'];
	$password     = $_POST['password'];
	$level     = $_POST['level'];
	
	// first, make sure the fieldname are not empty
	if ($username == '') {
		$errorMessage = 'You must enter the username';
	} else if ($password == '') {
		$errorMessage = 'You must enter the password';
	}else {
		// check the database and see if the username and password combo do match
		$sql = "SELECT id
		        FROM security_users
				WHERE username = '$username'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$errorMessage = 'The username is already existing';
		} else {
			$sql = "INSERT INTO security_users(username, password, first_name, middle_name, last_name, status, created_user_id, created_on, security_id, title_id, start_date)
                    VALUES ('$username', '$password', '$firstname', '$middlename', '$lastname', '$$level', '$userId', now(), '2', '$titleid', now())";
   			     		$result = dbQuery($sql);
			
			$errorMessage = 'Admin was successfully added';
		}		
			
	}
	
	return $errorMessage;
}


function editAdmin()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	$id   	  = $_POST['id'];
	$titleid   	  = $_POST['titleid'];
	$firstname 	  = $_POST['firstname'];	
	$middlename   = $_POST['middlename'];
	$lastname     = $_POST['lastname'];
	


		
		$sql = "UPDATE security_users
				SET first_name = '$firstname', middle_name = '$middlename', last_name = '$lastname', title_id = '$titleid', modified_user_id = '$userId', modified_on = now()
				WHERE id = '$id'";
				
		$result = dbQuery($sql);
		$errorMessage = 'Admin was successfully updated';



	return $errorMessage;
}

function adminAccount()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$id   		= $_POST['id'];
	$username 		= $_POST['username'];
	@$password 		= $_POST['password'];
	@$oldpassword 	= $_POST['oldpassword'];
	@$newpassword    = $_POST['newpassword'];
	@$cnewpassword   = $_POST['cnewpassword'];
	$secid 		= $_POST['secid'];
	
	if ($secid == 1){
		// first, make sure the fieldname are not empty
		if ($newpassword == '') {
			$errorMessage = 'You must enter the new password';
		} else if ($cnewpassword == '') {
			$errorMessage = 'You must enter the confirm new password';
		} else if ($newpassword != $cnewpassword){
			$errorMessage = 'The new and confirm password are not tally';
		}else {			
				$sql = "UPDATE security_users
						SET password = '$newpassword' 
						WHERE id = '$id'";
						dbQuery($sql);
				
				$errorMessage = 'The admin password was successfully updated';
		}		
	} else {
		if ($password == '') {
			$errorMessage = 'You must enter the password';
		} else if ($newpassword == '') {
			$errorMessage = 'You must enter the new password';
		} else if ($cnewpassword == '') {
			$errorMessage = 'You must enter the confirm new password';
		} else if ($password != $oldpassword){
			$errorMessage = 'The password is not correct';
		} else if ($newpassword != $cnewpassword){
			$errorMessage = 'The new and confirm password are not tally';
		}else {			
				$sql = "UPDATE security_users
						SET password = '$newpassword' 
						WHERE id = '$id'";
						dbQuery($sql);
				
				$errorMessage = 'The admin password was successfully updated';
		}		
	}	
		return $errorMessage;
}


function addMember()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	
	$plNo   	  = $_POST['plno'];
	@$ippis   	  = $_POST['ippis'];
	/*@$memberTypeId = $_POST['membertypeid'];
	@$levelid     = $_POST['levelid'];*/
	
	@$titleid   	  = $_POST['titleid'];
	@$firstname 	  = $_POST['firstname'];	
	@$middlename   = $_POST['middlename'];
	@$lastname     = $_POST['lastname'];
	@$birthdate 	  = $_POST['birthdate'];
	@$image        = $_FILES['fileselect'];
	@$catImage 	  = uploadImage('fileselect', SRV_ROOT . 'upload/');
	@$gender  	  = $_POST['gender'];
	@$stateid  	  = $_POST['stateid'];
	@$address 	  = $_POST['address'];	
	@$email     	  = $_POST['email'];
	@$tel          = $_POST['tel'];
	@$nationality  = $_POST['nationality'];
	
	@$ledgerno  	  = $_POST['ledgerno'];
	@$department  	  = $_POST['department'];
	@$designation  	  = $_POST['designation'];
	@$assumption  	  = $_POST['assumption'];
	@$confirmation  	  = $_POST['confirmation'];
	
	@$bank1  	  = $_POST['bank1'];
	@$accountname1  	  = $_POST['accountname1'];
	@$accountno1 	  = $_POST['accountno1'];
	
	@$bank2  	  = $_POST['bank2'];
	@$accountname2  	  = $_POST['accountname2'];
	@$accountno2 	  = $_POST['accountno2'];
	
	@$bank3  	  = $_POST['bank3'];
	@$accountname3  	  = $_POST['accountname3'];
	@$accountno3 	  = $_POST['accountno3'];
	
	
	/*@$organisationid = $_POST['organisationid'];
	
	if( is_array($organisationid)){
		echo @$org;
		while (list ($key, $val) = each ($organisationid)) {
		//echo "$val <br>";
			@$org .= $val . ",";
		}
		$org = substr($org,0,-1);
		//echo $org;
	} else {
		$org = $organisationid;
	}*/

	

	// first, make sure the fieldname are not empty
	/*if ($username == '') {
		$errorMessage = 'You must enter the username';
	} else if ($password == '') {
		$errorMessage = 'You must enter the password';
	} else if ($catImage == '') {
		$errorMessage = 'Image is empty or too big';
	}else {*/
		// check the database and see if the username and password combo do match
		$sql = "SELECT id
		        FROM security_users
				WHERE ippis = '$ippis' AND tel = '$tel'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$errorMessage = 'The member is already existing';
		} else {
			
			/*if ($memberTypeId == '25') {
				$ippis = '';
			} else {
				$ippis = ippis();
			}*/
			
			
			@$sql = "INSERT INTO security_users(pl_no, ippis, email, tel, first_name, middle_name, last_name, date_of_birth, state_id, gender, address, status, created_user_id, created_on, security_id, title_id, start_date, new_status, nationality, ledgerno, department, designation, assumption, confirmation, bank1, accountname1, accountno1, bank2, accountname2, accountno2, bank3, accountname3, accountno3, signature)
                    VALUES ('$plNo', '$ippis', '$email', '$tel', '$firstname', '$middlename', '$lastname', '$birthdate', '$stateid', '$gender', '$address', '1', '$userId', now(), '4', '$titleid', now(), 'new', '$nationality', '$ledgerno', '$department', '$designation', '$assumption', '$confirmation', '$bank1', '$accountname1', '$accountno1', '$bank2', '$accountname2', '$accountno2', '$bank3', '$accountname3', '$accountno3', '$catImage')";
   		    $result = dbQuery($sql);
						
			$secId = dbInsertId();	
		
			$sql1 = "INSERT INTO deduction( pl_no,  created_id, created_on)
                    VALUES ('$plNo',  '$userId', now())";
   			$result1 = dbQuery($sql1);
			
			$sql2 = "SELECT amount, table_name FROM fix_payment f, nick_name n WHERE f.field_options_id = n.field_options_id";
			$result2 = dbQuery($sql2);
			while($row2 = dbFetchAssoc($result2)) {
				extract($row2);	
				
				$sql3 = "UPDATE deduction
						SET $table_name = '$amount'
						WHERE  pl_no = '$plNo'";
						dbQuery($sql3);
			}
			
			$sql5 = "SELECT amount, table_name FROM new_payment f, nick_name n WHERE f.field_options_id = n.field_options_id";
			$result5 = dbQuery($sql5);
			while($row5 = dbFetchAssoc($result5)) {
				extract($row5);	
				
				$sql6 = "UPDATE deduction
						SET $table_name = '$amount'
						WHERE pl_no = '$plNo'";
						dbQuery($sql6);
			}
			
			
			
			
			$errorMessage = 'Member was successfully added';
		}		

	return $errorMessage;
}


function editMember()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$plNo   	  = $_POST['plno'];
	@$userId    	  = $_POST['userid'];
	@$ippis   	  = $_POST['ippis'];

	/*@$memberTypeId = $_POST['membertypeid'];
	@$membershipId = $_POST['membershipid'];
	@$levelid     = $_POST['levelid'];*/
	@$titleid   	  = $_POST['titleid'];
	@$firstname 	  = $_POST['firstname'];	
	@$middlename   = $_POST['middlename'];
	@$lastname     = $_POST['lastname'];
	@$birthdate 	  = $_POST['birthdate'];
	//$oldimage 	  = $_POST['image'];
	/*@$image        = $_FILES['fileselect'];
	@$catImage 	  = uploadImage('fileselect', SRV_ROOT . 'upload/');*/
	@$gender  	  = $_POST['gender'];
	@$stateid  	  = $_POST['stateid'];
	@$address 	  = $_POST['address'];	
	@$email     	  = $_POST['email'];
	@$tel          = $_POST['tel'];
	@$nationality  = $_POST['nationality'];
	
	
	@$ledgerno  	  = $_POST['ledgerno'];
	@$department  	  = $_POST['department'];
	@$designation  	  = $_POST['designation'];
	@$assumption  	  = $_POST['assumption'];
	@$confirmation  	  = $_POST['confirmation'];
	
	@$bank1  	  = $_POST['bank1'];
	@$accountname1  	  = $_POST['accountname1'];
	@$accountno1 	  = $_POST['accountno1'];
	
	@$bank2  	  = $_POST['bank2'];
	@$accountname2  	  = $_POST['accountname2'];
	@$accountno2 	  = $_POST['accountno2'];
	
	@$bank3  	  = $_POST['bank3'];
	@$accountname3  	  = $_POST['accountname3'];
	@$accountno3 	  = $_POST['accountno3'];

	//@$location     	  = $_POST['location'];
	/*@$organisationid = $_POST['organisationid'];
	
	if( is_array($organisationid)){
		echo @$org;
		while (list ($key, $val) = each ($organisationid)) {
		//echo "$val <br>";
			@$org .= $val . ",";
		}
		$org = substr($org,0,-1);
		//echo $org;
	} else {
		$org = $organisationid;
	}*/
	
	

	/*if ($memberTypeId == '25') {
				$ippis = '';
	} else {
				$ippis = ippis();
	}*/
	 // if uploading a new image
     // remove old image
	/* if ($catImage != '') {
        _deleteImage($id);
		$catImage = "'$catImage'";*/
		
		$sql = "UPDATE security_users
				SET ippis = '$ippis', first_name = '$firstname', middle_name = '$middlename', 
				last_name = '$lastname', date_of_birth = '$birthdate', state_id = '$stateid', gender = '$gender', address = '$address', 
				title_id = '$titleid', modified_user_id = '$userId', modified_on = now(), tel = '$tel', email = '$email', nationality = '$nationality', ledgerno = '$ledgerno', department = '$department', designation = '$designation', assumption = '$assumption', confirmation = '$confirmation', bank1 = '$bank1', accountname1 = '$accountname1', accountno1 = '$accountno1', bank2 = '$bank2', accountname2 = '$accountname2', accountno2 = '$accountno2', bank3 = '$bank3', accountname3 = '$accountname3', accountno3 = '$accountno3'
				WHERE pl_no = '$plNo'";
				
		$result = dbQuery($sql);
				
		
		$sql2 = "SELECT amount, table_name FROM fix_payment f, nick_name n WHERE f.field_options_id = n.field_options_id";
		$result2 = dbQuery($sql2);
			while($row2 = dbFetchAssoc($result2)) {
				extract($row2);	
				
				$sql3 = "UPDATE deduction
						SET $table_name = '$amount'
						WHERE pl_no = '$plNo'";
						dbQuery($sql3);
			}
		
		$errorMessage = 'Member was successfully updated';
		
	


	return $errorMessage;
}

function memberAccount()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$plNo   	  = $_POST['plno'];
	$username 		= $_POST['username'];
	$password 		= $_POST['password'];
	@$oldpassword 	= $_POST['oldpassword'];
	$newpassword    = $_POST['newpassword'];
	$cnewpassword   = $_POST['cnewpassword'];
	
	// first, make sure the fieldname are not empty
	if ($password == '') {
		$errorMessage = 'You must enter the password';
	} else if ($newpassword == '') {
		$errorMessage = 'You must enter the new password';
	} else if ($cnewpassword == '') {
		$errorMessage = 'You must enter the confirm new password';
	} else if ($password != $oldpassword){
		$errorMessage = 'The password is not correct';
	} else if ($newpassword != $cnewpassword){
		$errorMessage = 'The new and confirm password are not tally';
	}else {			
			$sql = "UPDATE security_users
					SET password = '$newpassword' 
					WHERE pl_no = '$plNo'";
					dbQuery($sql);
			
			$errorMessage = 'The admin password was successfully updated';
	}		
	
	return $errorMessage;
}

function addUpload()
{
	// if we found an error save the error message in this variable
	$msg = '';
	
	$id    	  = $_POST['secid'];
	//$imageId 	  = $_POST['image'];
	$image        = $_FILES['fileselect'];
	
	_deleteImage($id);
	//$img = ImageName($imageId);

	$catImage 	  = uploadImage('fileselect', SRV_ROOT . 'upload/');
	
			$sql = "UPDATE security_users set photo_name = '$catImage', modified_on = now() WHERE pl_no = '$id'";
			$result = dbQuery($sql);	
		
			$msg = 'Passport was successfully uploaded';
		
	
	return $msg;
}


function addRefree()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$plNo   	  = $_POST['plno'];
	$refid  	  = $_POST['ippis'];
	
	
	
	$sql = "SELECT id, member_id FROM security_users
			WHERE pl_no = '$refid'";
	$result    = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$row 	   = dbFetchAssoc($result);
			$memberid = $row['member_id'];

			if ($memberid == '1'){
			$sql = "INSERT INTO refree(pl_no, refid, created_id, created_on) VALUES ('$plNo', '$refid',                    '$userId', now())";
			$result = dbQuery($sql);	
			$errorMessage = 'Refree was successfully added';
			}else {	
			$errorMessage = 'This member is not Coop Member and Staff';	
			}
		} else {
			$errorMessage = 'There is no member with Member Id '.$refid ;
		}
	
			
	
	return $errorMessage;
}


function editRefree()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$plNo   	  = $_POST['plno'];
	$listId   	  = $_POST['listid'];
	$refid  	  = $_POST['ippis'];
	
	
	$sql = "SELECT id, member_id FROM security_users
			WHERE pl_no = '$refid'";
	$result    = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$row 	   = dbFetchAssoc($result);
			$memberid = $row['member_id'];

			if ($memberid == '1'){
			$sql = "UPDATE refree set refid = '$refid', modify_id = '$userId', modify_on = now() WHERE pl_no = '$plNo' AND id = '$listId'";
			$result = dbQuery($sql);	
			$errorMessage = 'Refree was successfully updated';
			}else {	
			$errorMessage = 'This member is not Coop Member and Staff';	
			}
		} else {
			$errorMessage = 'There is no member with Member Id '.$refid ;
		}
			
	
	return $errorMessage;
}


function addBenef()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$plNo   	  = $_POST['plno'];
	$secId   	  = $_POST['secid'];
	$name  	 	  = $_POST['name'];
	$relation  	  = $_POST['relation'];
	$address  	  = $_POST['address'];
	$tel  	 	  = $_POST['tel'];
	$email   	  = $_POST['email'];
	

	
	
			$sql = "INSERT INTO beneficiary(pl_no, sec_id, name, relation, address, tel, email, created_id, created_on) VALUES ('$plNo', '$secId', '$name', '$relation', '$address', '$tel', '$email', '$userId', now())";
			$result = dbQuery($sql);
			
			$errorMessage = 'Beneficiary was successfully added';
			
	
			
	
	return $errorMessage;
}


function editBenef()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];;
	$plNo   	  = $_POST['plno'];
	$secId   	  = $_POST['secid'];
	$name  	 	  = $_POST['name'];
	$relation  	  = $_POST['relation'];
	@$address  	  = $_POST['address'];
	$tel  	 	  = $_POST['tel'];
	$email   	  = $_POST['email'];
	
	
			$sql = "UPDATE beneficiary set name = '$name', relation = '$relation', address = '$address', tel = '$tel', email = '$email', modify_id = '$userId', modify_on = now() WHERE pl_no = '$plNo' AND sec_id = '$secId'";
			$result = dbQuery($sql);	
			$errorMessage = 'Refree was successfully updated';
			
	
	return $errorMessage;
}




function addDeduction()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	$plNo   	  = $_POST['plno'];
	$secId   	  = $_POST['secid'];

	$sql = "INSERT INTO deduction(pl_no, sec_id, created_id, created_on)
                    VALUES ('$plNo', '$secId', '$userId', now())";
   			     		$result = dbQuery($sql);
						
		$sql1 = "SELECT field_name, table_name FROM nick_name";
					$result1 = dbQuery($sql1);
					while($row1 = dbFetchAssoc($result1)) {
					extract($row1);	
					@$name  = $_POST[$table_name];
					$sql2 = "UPDATE deduction
							SET $table_name = '$name'
							WHERE  pl_no = '$plNo'";
					$result = dbQuery($sql2);
					}
			
			//$errorMessage = 'Deduction detail was successfully added';
	header('Location: index.php?view=detail&page=deduction&val='.$secId);
	
	//return $errorMessage;
}


function editDeduction()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	$plNo   	  = $_POST['plno'];

	$sql1 = "SELECT field_name, table_name, field_options_id  FROM nick_name";
	$result1 = dbQuery($sql1);
	while($row1 = dbFetchAssoc($result1)) {
	extract($row1);	
	@$name  = $_POST[$table_name];
	$sql2 = "UPDATE deduction
			 SET $table_name = '$name'
			 WHERE pl_no = '$plNo'";
	$result = dbQuery($sql2);
	
	
	
	$sql3 = "UPDATE application
			 SET payment_schedule = '$name'
			 WHERE pl_no = '$plNo' AND loan_id = '$field_options_id'";
	$result = dbQuery($sql3);
	}
	
	$errorMessage = 'Deduction was successfully updated';
		
	return $errorMessage;
}


function addApplication()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$monthyear    = $_POST['monthyear'];
	$plNo   	  = $_POST['plNo'];
	$loanId	  	  = $_POST['loanId'];
	@$purpose     = $_POST['loanPurpose'];
	$camount  	  = $_POST['cAmount'];
	@$bCharges    = $_POST['bCharges'];
	/*@$cFee    	  = $_POST['cFee'];*/
	$pPeriod	  = $_POST['pPeriod'];
	@$plno1  	  = $_POST['plno1'];
	@$plno2  	  = $_POST['plno2'];
	@$plno3  	  = $_POST['plno3'];
	@$plno4  	  = $_POST['plno4'];
	@$plno5  	  = $_POST['plno5'];
	@$plno6  	  = $_POST['plno6'];
	$yearId  	  = $_POST['yearid'];
		
	// first, make sure the fieldname are not empty
	if ($plNo == '') {
		$errorMessage = 'You must enter the member id';
	} else if ($loanId == '0') {
		$errorMessage = 'You must select loan';
	} else if ($camount == '') {
		$errorMessage = 'You must enter the cost amount';
	}  else if ($pPeriod == '') {
		$errorMessage = 'You must enter the payment period';
	} /*else if ($plno1 == '') {
		$errorMessage = 'You must enter the MEM ID of Refree 1';
	} else if ($plno2 == '') {
		$errorMessage = 'You must enter the MEM ID of Refree 2';
	} else if ($plno3 == '') {
		$errorMessage = 'You must enter the MEM ID of Refree 3 ';
	} else if ($plno4 == '') {
		$errorMessage = 'You must enter the MEM ID of Refree 4 ';
	} else if (($plno1 == $userIppis) || ($plno2 == $userIppis) || ($plno3 == $userIppis) || ($plno4 == $userIppis)){
		$errorMessage = 'One of the ippis is invalid';
	} else if (($memberid1 != 24) && ($memberid2 != 24) || ($memberid3 != 24) || ($memberid4 != 24)){
		$errorMessage = 'One of the refree is not OAUTHC staff';
	}*/ else {
		/*if (!empty($id)){
		$sql = "SELECT id FROM field_options WHERE field_name = '$id'";
		$result     = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$monthyear = $row['id'];
	 	}*/
		
		$sql = "SELECT field_id FROM field_options WHERE id = '$loanId'";
		$result     = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$catId = $row['field_id'];
		
		$month 	   = $pPeriod;
		//$profit = $samount - $amount;
		$totalAmount = $camount;
		$schedule = $totalAmount/$month;
		
		/*if (($amount < $minAmount) || ($amount > $maxAmount)){
		$errorMessage = 'The amount you entered is not within the range of the loan selected';	
		} else {
			$sql = "SELECT sum(loanable_savings) as savings
					FROM monthly_payment
					WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
			$result    = dbQuery($sql);
			$row 	   = dbFetchAssoc($result);
			$loanable   = 2 * $row['savings'];
				if ($totalAmount > $loanable){
				$errorMessage = 'Sorry, you are not eligible for the amount you entered';		
				} else {*/
					
				$sql3 = "SELECT table_name FROM nick_name WHERE  field_options_id = '$loanId'";
				$result3 = dbQuery($sql3);
				$row3 = dbFetchAssoc($result3);
				$tableName = $row3['table_name'];
				$tableBalance = $tableName.'_bal';
	
				$sql6 = "SELECT sum($tableName) as credit, sum($tableBalance) as debit FROM monthly_payment WHERE pl_no = '$plNo'";
				$result6 = dbQuery($sql6);
				$row6 = dbFetchAssoc($result6);
				$tcredit = $row6['credit'];
				$tdebit = $row6['debit'];
				
				$bal =  $tdebit - $tcredit;
				if ($bal < 0){
					$nbal = abs($bal);
					$balance = $totalAmount - $nbal;
				} else {
					$balance = $totalAmount;
				}
				
				$sql1 = "SELECT sum(payment_schedule) as payment_schedule, sum(balance) as balance FROM application
						 WHERE pl_no = '$plNo' AND loan_id = '$loanId' AND status = '1'";
				$result1 = dbQuery($sql1);
				$row1 = dbFetchAssoc($result1);
				@$presentSchedule = $row1['payment_schedule'];
				$newSchedule = @$presentSchedule + $schedule;
						
				$sql = "INSERT INTO monthly_payment(pl_no, $tableBalance, month_year, act_year_id, created_id, created_on) VALUES ('$plNo', '$totalAmount', '$monthyear', '$yearId', '$userId', now())";
						dbQuery($sql);
				
				$sql = "UPDATE deduction
						SET $tableName = '$newSchedule' 
						WHERE pl_no = '$plNo'";
						dbQuery($sql);
						
				
				
				
				$sql = "INSERT INTO application(pl_no, loan_id, cat_id, month_year, act_year_id, purpose, cost_amount, total, balance, payment_schedule, month, date_approved, status, plno1, plno2, plno3, plno4, plno5, plno6, created_id, created_on, b_charges) VALUES ('$plNo', '$loanId', '$catId', '$monthyear', '$yearId', '$purpose', '$camount', '$totalAmount', '$balance', '$schedule', '$month', now(), '1', '$plno1', '$plno2', '$plno3', '$plno4', '$plno5', '$plno6', '$userId', now(), '$bCharges')";
				$result = dbQuery($sql);	
				
				$appId = dbInsertId();
				$sql = "INSERT INTO app_tem_charges(app_id, loan_id, pl_no, b_charges) VALUES ('$appId', '$loanId', '$plNo', '$bCharges')";
				dbQuery($sql);
				
				$errorMessage = 'Applications was successfully added';
				//header('Location: index.php?view=detail&page=deduction&val='.$secId);
				//}
		//}
	}
		return $errorMessage;
}


function addInvApp()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$monthyear    = $_POST['monthyear'];
	$plNo   	  = $_POST['plNo'];
	$loanId	  	  = $_POST['loanId'];
	@$purpose     = $_POST['loanPurpose'];
	$camount  	  = $_POST['cAmount'];
	@$bCharges    = $_POST['bCharges'];
	/*@$cFee    	  = $_POST['cFee'];*/
	$pPeriod	  = $_POST['pPeriod'];
	@$plno1  	  = $_POST['plno1'];
	@$plno2  	  = $_POST['plno2'];
	@$plno3  	  = $_POST['plno3'];
	@$plno4  	  = $_POST['plno4'];
	@$plno5  	  = $_POST['plno5'];
	@$plno6  	  = $_POST['plno6'];
	$yearId  	  = $_POST['yearid'];
		
	// first, make sure the fieldname are not empty
	if ($plNo == '') {
		$errorMessage = 'You must enter the member id';
	} else if ($loanId == '0') {
		$errorMessage = 'You must select loan';
	} else if ($camount == '') {
		$errorMessage = 'You must enter the cost amount';
	}  else if ($pPeriod == '') {
		$errorMessage = 'You must enter the payment period';
	} /*else if ($plno1 == '') {
		$errorMessage = 'You must enter the MEM ID of Refree 1';
	} else if ($plno2 == '') {
		$errorMessage = 'You must enter the MEM ID of Refree 2';
	} else if ($plno3 == '') {
		$errorMessage = 'You must enter the MEM ID of Refree 3 ';
	} else if ($plno4 == '') {
		$errorMessage = 'You must enter the MEM ID of Refree 4 ';
	} else if (($plno1 == $userIppis) || ($plno2 == $userIppis) || ($plno3 == $userIppis) || ($plno4 == $userIppis)){
		$errorMessage = 'One of the ippis is invalid';
	} else if (($memberid1 != 24) && ($memberid2 != 24) || ($memberid3 != 24) || ($memberid4 != 24)){
		$errorMessage = 'One of the refree is not OAUTHC staff';
	}*/ else {
		/*if (!empty($id)){
		$sql = "SELECT id FROM field_options WHERE field_name = '$id'";
		$result     = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$monthyear = $row['id'];
	 	}*/
		

		$month 	   = $pPeriod;
		//$profit = $samount - $amount;
		$totalAmount = $camount + $bCharges;
		$schedule = $totalAmount/$month;
		
		/*if (($amount < $minAmount) || ($amount > $maxAmount)){
		$errorMessage = 'The amount you entered is not within the range of the loan selected';	
		} else {
			$sql = "SELECT sum(loanable_savings) as savings
					FROM monthly_payment
					WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
			$result    = dbQuery($sql);
			$row 	   = dbFetchAssoc($result);
			$loanable   = 2 * $row['savings'];
				if ($totalAmount > $loanable){
				$errorMessage = 'Sorry, you are not eligible for the amount you entered';		
				} else {*/
					
				$sql3 = "SELECT table_name FROM nick_name WHERE  field_options_id = '$loanId'";
				$result3 = dbQuery($sql3);
				$row3 = dbFetchAssoc($result3);
				$tableName = $row3['table_name'];
				$tableBalance = $tableName.'_bal';
	
				$sql6 = "SELECT sum($tableName) as credit, sum($tableBalance) as debit FROM monthly_payment WHERE pl_no = '$plNo'";
				$result6 = dbQuery($sql6);
				$row6 = dbFetchAssoc($result6);
				$tcredit = $row6['credit'];
				$tdebit = $row6['debit'];
				
				$bal =  $tdebit - $tcredit;
				if ($bal < 0){
					$nbal = abs($bal);
					$balance = $totalAmount - $nbal;
				} else {
					$balance = $totalAmount;
				}
				
				$sql1 = "SELECT sum(payment_schedule) as payment_schedule, sum(balance) as balance FROM application
						 WHERE pl_no = '$plNo' AND loan_id = '$loanId' AND status = '1'";
				$result1 = dbQuery($sql1);
				$row1 = dbFetchAssoc($result1);
				@$presentSchedule = $row1['payment_schedule'];
				$newSchedule = @$presentSchedule + $schedule;
						
				$sql = "INSERT INTO monthly_payment( pl_no, $tableBalance, month_year, act_year_id, created_id, created_on) VALUES ( '$plNo', '$totalAmount', '$monthyear', '$yearId', '$userId', now())";
						dbQuery($sql);
				
				$sql = "UPDATE deduction
						SET $tableName = '$newSchedule' 
						WHERE pl_no = '$plNo'";
						dbQuery($sql);
						
				
				
				
				$sql = "INSERT INTO application(pl_no, loan_id, month_year, act_year_id, purpose, cost_amount, total, balance, payment_schedule, month, date_approved, status, plno1, plno2, plno3, plno4, plno5, plno6, created_id, created_on, b_charges) VALUES ('$plNo', '$loanId', '$monthyear', '$yearId', '$purpose', '$camount', '$totalAmount', '$balance', '$schedule', '$month', now(), '1', '$plno1', '$plno2', '$plno3', '$plno4', '$plno5', '$plno6', '$userId', now(), '$bCharges')";
				$result = dbQuery($sql);	
				
				$appId = dbInsertId();
				$sql = "INSERT INTO app_tem_charges(app_id, loan_id, pl_no, b_charges) VALUES ('$appId', '$loanId', '$plNo', '$bCharges')";
				dbQuery($sql);
				
				$errorMessage = 'Investment application was successfully added';
				//header('Location: index.php?view=detail&page=deduction&val='.$secId);
				//}
		//}
	}
		return $errorMessage;
}
	

			

/*
function addApplication()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$trustId   	  = $_POST['trustid'];
	$plNo   	  = $_POST['plno'];
	$userIppis    = $_POST['userippis'];
	$secId   	  = $_POST['secid'];
	$loanId	  	  = $_POST['loanid'];
	$amount  	  = $_POST['amount'];
	@$purpose  	  = $_POST['purpose'];
	@$id  		  = $_POST['monthyear'];
	@$ippis1  	  = $_POST['ippis1'];
	@$ippis2  	  = $_POST['ippis2'];
	@$ippis3  	  = $_POST['ippis3'];
	@$ippis4  	  = $_POST['ippis4'];
	
		@$sqli1 = "SELECT member_id FROM security_users
				  WHERE ippis = '$ippis1'";
		@$resulti1  = dbQuery($sqli1);
		@$rowi1 	   = dbFetchAssoc($resulti1);
		@$memberid1 = $rowi1['member_id'];
		
		@$sqli2 = "SELECT member_id FROM security_users
				  WHERE ippis = '$ippis2'";
		@$resulti2  = dbQuery($sqli1);
		@$rowi2 	   = dbFetchAssoc($resulti2);
		@$memberid2 = $rowi2['member_id'];
		
		@$sqli3 = "SELECT member_id FROM security_users
				  WHERE ippis = '$ippis3'";
		@$resulti3  = dbQuery($sqli3);
		@$rowi3 	   = dbFetchAssoc($resulti3);
		@$memberid3 = $rowi3['member_id'];
		
		@$sqli4 = "SELECT member_id FROM security_users
				  WHERE ippis = '$ippis4'";
		@$resulti4  = dbQuery($sqli4);
		@$rowi4 	   = dbFetchAssoc($resulti4);
		@$memberid4 = $rowi4['member_id'];
		
	// first, make sure the fieldname are not empty
	if ($loanId == '0') {
		$errorMessage = 'You must select loan';
	} else if ($amount == '') {
		$errorMessage = 'You must enter the amount';
	} else if ($id == '') {
		$errorMessage = 'You must select the month';
	} else if ($ippis1 == '') {
		$errorMessage = 'You must enter the IPPIS of Refree 1';
	} else if ($ippis2 == '') {
		$errorMessage = 'You must enter the IPPIS of Refree 2';
	} else if ($ippis3 == '') {
		$errorMessage = 'You must enter the IPPIS of Refree 3 ';
	} else if ($ippis4 == '') {
		$errorMessage = 'You must enter the IPPIS of Refree 4 ';
	} else if (($ippis1 == $userIppis) || ($ippis2 == $userIppis) || ($ippis3 == $userIppis) || ($ippis4 == $userIppis)){
		$errorMessage = 'One of the ippis is invalid';
	} else if (($memberid1 != '24') || ($memberid2 != '24') || ($memberid3 != '24') || ($memberid4 != '24')){
		$errorMessage = 'One of the refree is not OAUTHC staff';
	} else {
		if (!empty($id)){
		$sql = "SELECT id FROM field_options WHERE field_name = '$id'";
		$result     = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$monthyear = $row['id'];
	 	}
	
	
		$sql = "SELECT month, percent
				FROM loan_list
				WHERE field_options_id = '$loanId'";
		$result    = dbQuery($sql);
		$row 	   = dbFetchAssoc($result);
		$month 	   = $row['month'];
		$percent   = $row['percent'];
		
		$profit = $amount * $percent;
		$totalAmount = $amount + $profit;
		$schedule = $totalAmount/$month;
		
		if (($amount < $minAmount) || ($amount > $maxAmount)){
		$errorMessage = 'The amount you entered is not within the range of the loan selected';	
		} else {
			$sql = "SELECT sum(loanable_savings) as savings
					FROM monthly_payment
					WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
			$result    = dbQuery($sql);
			$row 	   = dbFetchAssoc($result);
			$loanable   = 2 * $row['savings'];
				if ($totalAmount > $loanable){
				$errorMessage = 'Sorry, you are not eligible for the amount you entered';		
				} else {
					
				$sql3 = "SELECT table_name FROM nick_name n, field_options f WHERE  f.id = n.field_options_id AND f.id = '$loanId'";
				$result3 = dbQuery($sql3);
				$row3 = dbFetchAssoc($result3);
				$tableName = $row3['table_name'];
				$tableBalance = $tableName.'_bal';
				
				$sql1 = "SELECT sum(payment_schedule) as payment_schedule, sum(balance) as balance FROM application
						 WHERE sec_id = '$secId' AND trust_id = '$trustId' AND loan_id = '$loanId' AND status = '1'";
				$result1 = dbQuery($sql1);
				$row1 = dbFetchAssoc($result1);
				@$presentSchedule = $row1['payment_schedule'];
				@$presentBalance = $row1['balance'];
				$newSchedule = @$presentSchedule + $schedule;
				$newTotal = @$presentBalance + $totalAmount;
			
				$sql = "UPDATE monthly_payment
						SET  $tableBalance = '-$newTotal'   
						WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND month_year = '$monthyear'";
						dbQuery($sql);
				
				$sql = "UPDATE deduction
						SET $tableName = '$newSchedule' 
						WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
						dbQuery($sql);		
				
				$sql = "INSERT INTO application(id, trust_id, pl_no, sec_id, loan_id, month_year, purpose, amount, profit, total, balance, payment_schedule,                    month, date_approved, status, ippis1, ippis2, ippis3, ippis4, created_id, created_on) VALUES ('', '$trustId', '$plNo', '$secId', '$loanId', '$monthyear', '$purpose', '$amount', '$profit', '$totalAmount', '$totalAmount', '$schedule', '$month', now(), '1', '$ippis1', '$ippis2', '$ippis3', '$ippis4', '$userId', now())";
				$result = dbQuery($sql);	
				$errorMessage = 'Applications was successfully added';
				//header('Location: index.php?view=detail&page=deduction&val='.$secId);
				}
		}
	}
		return $errorMessage;
}*/
			
		
		
		
	 


function editApplication()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	
	$plNo   	  = $_POST['plno'];
	$secId   	  = $_POST['secid'];
	$oldLoanId	  = $_POST['oldloanid'];
	$listid	  	  = $_POST['listid'];
	$amount  	  = $_POST['amount'];
	@$purpose  	  = $_POST['purpose'];
	@$id   = $_POST['monthyear'];
	
	if (!empty($id)){
		$sql = "SELECT id FROM field_options WHERE field_name = '$id'";
		$result     = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$monthyear = $row['id'];
	}
	
	$sql = "SELECT max_amount, min_amount, month, percent
		    FROM loan_list
			WHERE field_options_id = '$oldLoanId'";
	$result    = dbQuery($sql);
	$row 	   = dbFetchAssoc($result);
	$maxAmount = $row['max_amount'];
	$minAmount = $row['min_amount'];
	$month 	   = $row['month'];
	$percent   = $row['percent'];
	
	$profit = $amount * $percent;
	$totalAmount = $amount + $profit;
	$schedule = $totalAmount/$month;
	
	/*if (($amount < $minAmount) || ($amount > $maxAmount)){
	$errorMessage = 'The amount you entered is not within the range of the loan selected';	
	} else {
		$sql = "SELECT sum(loanable_savings) as savings
				FROM monthly_payment
				WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
		$result    = dbQuery($sql);
		$row 	   = dbFetchAssoc($result);
		$loanable   = 2 * $row['savings'];
			if ($totalAmount > $loanable){
			$errorMessage = 'Sorry, you are not eligible for the amount you entered';		
			} else {*/
				
			$sql3 = "SELECT table_name FROM nick_name n, field_options f WHERE  f.id = n.field_options_id AND f.id = '$oldLoanId'";
			$result3 = dbQuery($sql3);
			$row3 = dbFetchAssoc($result3);
			$tableName = $row3['table_name'];
			$tableBalance = $tableName.'_bal';
			
			$sql1 = "SELECT payment_schedule, total FROM application WHERE sec_id = '$secId' AND  id = '$listid'";
 			$result1 = dbQuery($sql1);
			$row1 = dbFetchAssoc($result1);
			@$presentSchedule = $row1['payment_schedule'];
			@$presentTotal = $row1['total'];
			
			$sql1 = "SELECT sum(payment_schedule) as payment_schedule, sum(balance) as balance FROM application
			         WHERE sec_id = '$secId' AND loan_id = '$oldLoanId' AND status = '1'";
 			$result1 = dbQuery($sql1);
			$row1 = dbFetchAssoc($result1);
			@$totalSchedule = $row1['payment_schedule'];
			@$totalBalance = $row1['balance'];

			$newSchedule = @$totalSchedule + $schedule - @$presentSchedule;
			$newTotal = @$totalBalance + $totalAmount - @$presentTotal;
			
			/*$sql2 = "SELECT $tableName FROM deduction WHERE sec_id = '$secId' AND trust_id = '$trustId'";
 			$result2 = dbQuery($sql2);
			$row2 = dbFetchAssoc($result2);
			@$tableValue = $row2[$tableName];
			$firstValue = $tableValue - $presentSchedule;
			$newSchedule = @$firstValue + $schedule;*/
			
			$sql = "UPDATE monthly_payment
					SET  $tableBalance = '-$newTotal'   
					WHERE  pl_no = '$plNo' AND month_year = '$monthyear'";
					dbQuery($sql);
			
			
			$sql = "UPDATE deduction
					SET $tableName = '$newSchedule' 
					WHERE  pl_no = '$plNo'";
					dbQuery($sql);
			
			
			$sql = "UPDATE application
					SET month_year = '$monthyear', purpose = '$purpose', amount = '$amount', profit = '$profit', total = '$totalAmount', balance =                    '$totalAmount', payment_schedule = '$schedule', month = '$month', modify_id = '$userId', modify_on = now()
					WHERE  pl_no = '$plNo' AND loan_id = '$oldLoanId' AND id = '$listid'";
					dbQuery($sql);			
/*			
			$sql = "INSERT INTO application(id, trust_id, pl_no, sec_id, loan_id, purpose, amount, profit, payment_schedule, month, created_id,                    created_on) VALUES ('', '$trustId', '$plNo', '$secId', '$loanId', '$purpose', '$amount', '$profit', '$schedule', '$month',                    '$userId', now())";
			$result = dbQuery($sql);*/	
			$errorMessage = 'Applications was successfully updated';
			//header('Location: index.php?view=detail&page=deduction&val='.$secId);
			//}
	//}
	return $errorMessage;
}

function addMwithdraw()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$monthyear    = $_POST['monthyear'];
	$monthId  	  = MonthID($monthyear);
	$plNo   	  = $_POST['plNo'];
	$assetName	  = $_POST['assetId'];
	$tableBalance = $assetName.'_bal';
	$wAmount	 = $_POST['wAmount'];
	$aAmount	 = $_POST['aAmount'];
	$yearId	 = $_POST['yearid'];
	
	
	

		
	// first, make sure the fieldname are not empty
	if ($wAmount == '') {
		$errorMessage = 'You must enter the amount';
	} else if ($assetName == '') {
		$errorMessage = 'You must select the asset name';
	} else {
		
		if ($aAmount >= $wAmount){
			$sql = "INSERT INTO m_withdraw(pl_no, month_year, month_id, act_year_id, asset_name, amount, created_id, created_on) VALUES ('$plNo', '$monthyear', '$monthId', '$yearId', '$assetName',  '$wAmount', '$userId', now())";
					$result = dbQuery($sql);	
					$errorMessage = 'Withdraw was successfully';
		} else {
					$errorMessage = 'Insufficient fund';
		}
	}
		return $errorMessage;
}

function addOwithdraw()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userid    	  = $_POST['userid'];
	$tableName    = $_POST['tName'];
	$tableBalance = $tableName.'_bal';
	$monthyear    = $_POST['monthyear'];
	$monthid  	  = MonthID($monthyear);
	
	$orgid		= $_POST['orgId'];
	$prebalance	= $_POST['preBalance'];
	$curinflow	= $_POST['curInflow'];
	$tbalance	= $_POST['tBalance'];
	$wamount	= $_POST['wAmount'];
	$soccharges	= $_POST['socCharges'];
	/*$bancharges	= $_POST['banCharges'];*/
	$twamount =  $wamount + $soccharges;
	$curbalance	  = $_POST['curBalance'];
	$yearId	  = $_POST['yearid'];
	
	// first, make sure the fieldname are not empty
	if ($wamount == '0') {
		$errorMessage = 'You must enter the amount';
	} else {
			
			if ($curbalance >= 0){
				$sql = "UPDATE org_monthly_payment
						SET  $tableBalance = '$curbalance'  
						WHERE month_year = '$monthid'"; 
						dbQuery($sql);
		
				$sql = "INSERT INTO org_withdraw(`table_name`, `month_year`, `act_year_id`, `pre_balance`, `cur_inflow`, `t_balance`, `soc_charges`, `w_amount`, `total_outflow`, `cur_balance`, `created_id`, `created_on`) value ('$tableName', '$monthid', '$yearId', '$prebalance', '$curinflow', '$tbalance', '$soccharges', '$wamount', '$twamount', '$curbalance', '$userid', now())";
				$result = dbQuery($sql);	
				$errorMessage = 'Withdraw was successfully';
			} else {
				$errorMessage = 'Insufficient Fund';
			}
		
	}
		return $errorMessage;
}

function addInterest()
{
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$monthId 	  = $_POST['monthid'];
	$monthyear    = MonthYear($monthId);
	$yearId 	  = $_POST['yearid'];
	$table_name 	  = $_POST['tname'];
	$tableBalance = $table_name.'_bal';
	@$value  = $_POST['amount'];
	$bankname 	  = $_POST['bankname'];
	
	if ($value == '') {
		$errorMessage = 'You must enter the amount';
	} else {
					/*$sqlOrg = "SELECT $table_name as orgLastPay, $tableBalance as orgbal FROM org_monthly_payment WHERE act_year_id = '$yearId' AND month_year = '$monthId'";
					$resultOrg = dbQuery($sqlOrg);
					$rowOrg = dbFetchAssoc($resultOrg);
					$orgBal = $rowOrg['orgbal'];
					$orgLastPay = $rowOrg['orgLastPay'];
					$orgBalance = $orgBal + $value;
					$orgNewPay = $orgLastPay + $value;
					
				
					$sqlOrg2 = "UPDATE org_monthly_payment
						 	 	SET $table_name = '$orgNewPay', $tableBalance = '$orgBalance', modify_id = '$userId', modify_on = now()
							 	WHERE month_year = '$monthId'";
					dbQuery($sqlOrg2);*/
				
					$sqlOrg3 = "INSERT INTO org_monthly_payment(`month_year`, `act_year_id`, $table_name, `created_on`, `created_id`) VALUES ('$monthId','$yearId','$value', now(),'$userId')";
					dbQuery($sqlOrg3);
					
					$sqlOrg3 = "INSERT INTO `interest`(`month_year`, `act_year_id`, `amount`, `bank`, `created_on`, `created_id`) VALUES ('$monthId','$yearId','$value','$bankname',now(),'$userId')";
					dbQuery($sqlOrg3);
					$errorMessage = 'The interest was successfully added';
	}
	
					return $errorMessage;
}


function staffContribution()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	$plNo   	  = $_POST['plno'];
	$monthId 	  = $_POST['monthid'];
	$monthyear    = MonthYear($monthId);
	$yearId 	  = $_POST['yearid'];
		$sql = "SELECT id FROM monthly_payment WHERE pl_no = '$plNo' AND status = '1' AND month_year = '$monthId'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$errorMessage = "Payment has already been made";
		} else {
				// start s1			
				$sql1 = "SELECT table_name, id, field_id, field_options_id as opId FROM nick_name";
				$result1 = dbQuery($sql1);
				//start while1
				$t = 0;
				while($row1 = dbFetchAssoc($result1)) {
				extract($row1);	
				$loanId = loanID($table_name);
				$FieldID = LoanFieldID($table_name);
				@$tableBalance =  $table_name.'_bal';
				
				/*$sqlw = "SELECT amount as withdraw FROM m_withdraw WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND withdraw_id = '$loanId' order by id desc limit 1,1";
				$resultw = dbQuery($sqlw);
				$roww = dbFetchAssoc($resultw);
				$withValue = $roww['withdraw'];*/
						 
				//if ($opId == 5){
				$sql6 = "SELECT $tableBalance as bal FROM monthly_payment WHERE pl_no = '$plNo' AND act_year_id = '$yearId' order by id desc limit 1,1";
				//$sql6 = "SELECT max($tableBalance) as bal FROM monthly_payment WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND act_year_id = '$yearId'";
				$result6 = dbQuery($sql6);
				$row6 = dbFetchAssoc($result6);
				$tableValue = $row6['bal'];
				
				
				
				
				@$value  = $_POST[$table_name];
				
				
				$feeLoanId = LoanId($table_name);
				
				$sqlFee = "SELECT (b_charges + c_fee) as fee, app_id FROM app_tem_charges WHERE loan_id = '$feeLoanId' AND pl_no = '$plNo'";
			    $resultFee = dbQuery($sqlFee);
				if(dbNumRows($resultFee) == 1 && $value != 0){
					$rowFee = dbFetchAssoc($resultFee);
					$fee = $rowFee['fee'];
					$appId = $rowFee['app_id'];
					
					$sqlDF = "DELETE FROM app_tem_charges WHERE loan_id = '$feeLoanId' AND pl_no = '$plNo' AND app_id = '$appId'";
					dbQuery($sqlDF);
					
					@$newValue  = $_POST[$table_name] - $fee;
						
				} else {
					@$newValue  = $_POST[$table_name];
				}
				
				//@$balance = $tableValue + $value - $withValue;
				@$balance = $tableValue + $value;
				$t = $t + $value;
				

				if ($field_id == '14'){	 
					$sql9 = "UPDATE deduction
							 SET $table_name = '0'
							 WHERE pl_no = '$plNo'";
					dbQuery($sql9);
			
				
					$sql9 = "UPDATE security_users
							 SET new_status = 'old'
							 WHERE pl_no = '$plNo'";
					dbQuery($sql9);
	
					
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$value', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE pl_no = '$plNo' AND month_year = '$monthId'";
					dbQuery($sql2);
					
				} else if ($field_id == '2') {
					$sqlOrg = "SELECT $table_name as orgLastPay, $tableBalance as orgbal FROM org_monthly_payment WHERE act_year_id = '$yearId' AND month_year = '$monthId'";
					$resultOrg = dbQuery($sqlOrg);
					$rowOrg = dbFetchAssoc($resultOrg);
					$orgBal = $rowOrg['orgbal'];
					$orgLastPay = $rowOrg['orgLastPay'];
					$orgBalance = $orgBal + $value;
					$orgNewPay = $orgLastPay + $value;
					
					
				
					$sqlOrg2 = "UPDATE org_monthly_payment
						 	 	SET $table_name = '$orgNewPay', $tableBalance = '$orgBalance', modify_id = '$userId', modify_on = now()
							 	WHERE month_year = '$monthId'";
					dbQuery($sqlOrg2);
				
					$sql2 = "UPDATE monthly_payment
						 	 SET $table_name = '$value', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE pl_no = '$plNo' AND month_year = '$monthId'";
					dbQuery($sql2);
					
					if($opId != 52){
					$sqlOrgDep = "INSERT INTO org_deposit(act_year_id, month_year, amount, table_name) VALUES ('$yearId', '$monthId', '$value', '$table_name')";
			dbQuery($sqlOrgDep);
					}
				
				 } else if ($field_id == '5') {
					$sqlw = "SELECT sum(amount) as amount FROM m_withdraw WHERE pl_no = '$plNo' AND asset_name = '$table_name' AND month_year = '$monthyear'";
					 $resultw = dbQuery($sqlw);
					 $roww = dbFetchAssoc($resultw);
					 $withdraw = $roww['amount'];
					 
					 if ($withdraw == 0){
						 $sqlw = "UPDATE monthly_payment
						 	 	  SET $table_name = '$value', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
							 	  WHERE pl_no = '$plNo' AND month_year = '$monthId'";
						 dbQuery($sqlw);
						 
					 } else {
						 $sqlw1 = "SELECT $tableBalance as bal FROM monthly_payment WHERE pl_no = '$plNo' AND act_year_id = '$yearId' order by id desc limit 1";
				//$sql6 = "SELECT max($tableBalance) as bal FROM monthly_payment WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND act_year_id = '$yearId'";
						$resultw1 = dbQuery($sqlw1);
						$roww1 = dbFetchAssoc($resultw1);
						$tableValuew = $roww1['bal'];
				
						@$valuew  = $_POST[$table_name];
						//@$newValue  = $_POST[$table_name];
						//@$balance = $tableValue + $value - $withValue;
						@$balancew = $tableValuew + $valuew;
						 $sql2 = "UPDATE monthly_payment
						 	 	  SET $table_name = '$valuew', $tableBalance = '$balancew', status = '1', modify_id = '$userId', modify_on = now()
							 	  WHERE pl_no = '$plNo' AND month_year = '$monthId'";
						 dbQuery($sql2);
					 }
					 
					 
				 } else if ($field_id == '10' ){
					$opBalance = $tableValue + $value;
					if (($opId == 5) && ($opBalance >= 3600) || ($opId == 6) && ($opBalance >= 31200)) {
					   $sql9 = "UPDATE deduction
							 SET $table_name = '0'
							 WHERE pl_no = '$plNo'";
					   dbQuery($sql9);	
					}
					
					
					
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$value', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE pl_no = '$plNo' AND month_year = '$monthId'";
					dbQuery($sql2);
					
					
		
				} else if (($field_id == '3') || ($field_id == '4')){
					if ($newValue == 0) {
						$sql7 = "SELECT sum(balance) as abalance FROM application WHERE pl_no = '$plNo' AND 
								 loan_id = '$loanId'";
						$result7 = dbQuery($sql7);
						$row7 = dbFetchAssoc($result7);
						$aBalance = $row7['abalance'];
						
						$sql2 = "UPDATE monthly_payment
								 SET $table_name = '$newValue', $tableBalance = '-$aBalance', status = '1', modify_id = '$userId', modify_on = now()
								 WHERE pl_no = '$plNo' AND month_year = '$monthId'";
								 dbQuery($sql2);
					} else {
					while($newValue != '0'){
						$sql7 = "SELECT id, payment_schedule, balance as abalance FROM application WHERE pl_no = '$plNo' AND 
								 loan_id = '$loanId' AND balance != 0";
						$result7 = dbQuery($sql7);
						
						if (dbNumRows($result7) > 0) {
							
							$mappBalance = 0;
							while($row7 = dbFetchAssoc($result7)) {
								$paySchedule = $row7['payment_schedule'];
								$aBalance = $row7['abalance'];
								$appId = $row7['id'];
								
								if ($newValue >= $paySchedule){
									$appBalance = $aBalance - $paySchedule;
									$mappBalance = $mappBalance + $appBalance;
									
									
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									$sql2 = "UPDATE monthly_payment
											 SET $table_name = '$value', $tableBalance = '-$mappBalance', status = '1', modify_id = '$userId', modify_on = now()
											 WHERE pl_no = '$plNo' AND month_year = '$monthId'";
									dbQuery($sql2);
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
										
								$newValue -= $paySchedule;
									
								} else {
									$appBalance = $aBalance - $newValue;
									$mappBalance = $mappBalance + $appBalance;
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
									$sql2 = "UPDATE monthly_payment
											 SET $table_name = '$value', $tableBalance = '-$mappBalance', status = '1', modify_id = '$userId', modify_on = now()
											 WHERE pl_no = '$plNo' AND month_year = '$monthId'";
									dbQuery($sql2);
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										//$newValue = 1500;
										//$appBalnce = 1250;
										//$paySchedule = 2750;
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
	
								$newValue -= $newValue;
								 	
								}	
							}
						}
					}
					}
				
				} else {
						 
					$sql2 = "UPDATE monthly_payment
						 	 SET $table_name = '$value', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE pl_no = '$plNo' AND month_year = '$monthId'";
					dbQuery($sql2);
					
					
				}
			
			
						
				$errorMessage = "Payment was successfully added";
			
			//end of s1
		
	}
	$sqlPosting = "INSERT INTO m_posting(pl_no, month_year, amount, created_id, created_on) VALUES ('$plNo', '$monthId', '$t', '$userId', now())";
			dbQuery($sqlPosting);
		}
	return $errorMessage;
}



function staffBackEnd()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];

	$plNo   	  = $_POST['plno'];
	$monthId 	  = $_POST['monthid'];
	$secId 	  = $_POST['secid'];
	
				// start s1			
				$sql1 = "SELECT table_name, id, field_id, field_options_id FROM nick_name";
				$result1 = dbQuery($sql1);
				//start while1
				while($row1 = dbFetchAssoc($result1)) {
				extract($row1);	
				@$tableBalance =  $table_name.'_bal';
				
				@$balance  = $_POST[$table_name.'b'];
				if (($field_id == '3') && (!empty ($balance))) {
					@$tot  = $_POST[$table_name.'t'];
					@$mon  = $_POST[$table_name.'m'];
					$sql = "INSERT INTO application(pl_no, sec_id, loan_id, total, balance, payment_schedule, status) VALUES ('$plNo', '$secId', '$field_options_id', '$tot', '$balance', '$mon', '1')";
					dbQuery($sql);
					
				
				$sql2 = "UPDATE monthly_payment
						 	 SET $table_name = '$mon', $tableBalance = '-$balance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE  pl_no = '$plNo' AND month_year = '$monthId'";
					dbQuery($sql2);
					
				$sql3 = "UPDATE deduction
						 	 SET $table_name = '$mon'
							 WHERE  pl_no = '$plNo'";
					dbQuery($sql3);
			
				$errorMessage = "Payment was successfully added";
				
				} else {
				
					$sql4 = "UPDATE monthly_payment
						 	SET $table_name = '$balance', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE  pl_no = '$plNo' AND month_year = '$monthId'";
					dbQuery($sql4);
				$errorMessage = "Payment was successfully added";
				
				}
				
			
			//end of s1
		
	}
	
	return $errorMessage;
}



/*function staffContributionold()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	$trustId   	  = $_POST['trustid'];
	$plNo   	  = $_POST['plno'];
	$monthId 	  = $_POST['monthid'];
	$total   	  = $_POST['total'];
	
	$sql = "SELECT id FROM monthly_payment WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND status = '1' AND month_year = '$monthId'";
	$result = dbQuery($sql);
	if (dbNumRows($result) == 1) {
		
	} else {

		$sql = "SELECT table_name FROM nick_name";
		$result = dbQuery($sql);
		$t = 0;
		while($row = dbFetchAssoc($result)) {
		extract($row);	
		$value  = $_POST[$table_name];
		$t = $t + $value;
		}
		
			if ($t != $total){
				$errorMessage = "Total amount not tally ".$t.' '.$total;
			} else {
				// start s1			
				$sql1 = "SELECT table_name, id, field_id FROM nick_name";
				$result1 = dbQuery($sql1);
				//start while1
				while($row1 = dbFetchAssoc($result1)) {
				extract($row1);	
				$sql6 = "SELECT sum($table_name) as bal FROM monthly_payment WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
				$result6 = dbQuery($sql6);
				$row6 = dbFetchAssoc($result6);
				@$tableValue = $row6['bal'];
				
				@$value  = $_POST[$table_name];
				@$balance = $tableValue + $value;
				@$tableBalance =  $table_name.'_bal';
				
				$loanId = loanID($table_name);
				$FieldID = LoanFieldID($table_name);
				// start of 7
				$sql7 = "SELECT id, payment_schedule, balance as abalance FROM application WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND 
				        loan_id = '$loanId' AND balance != 0";
				$result7 = dbQuery($sql7);
					if (dbNumRows($result7) > 0) {
						$mappBalance = 0;
						while($row7 = dbFetchAssoc($result7)) {
						extract($row7);	
						$appBalance = $abalance - $payment_schedule;
						$mappBalance = $mappBalance + $appBalance;
						 
						
						$sql8 = "UPDATE application
								 SET balance = '$appBalance'
								 WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND id = '$id'";
						dbQuery($sql8);
						
						
							if ($appBalance == 0){
								$sql9 = "UPDATE application
								 		 SET status = '0', date_completed = now()
								 		 WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND id = '$id'";
								dbQuery($sql9);
								
								$sql9 = "UPDATE deduction
								 SET $table_name = ($table_name - $payment_schedule)
								 WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
								 dbQuery($sql9);
							}
						}
						
						$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$value', $tableBalance = '-$mappBalance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND month_year = '$monthId'";
							 dbQuery($sql2);
						
							if ($field_id == '14'){	 
							$sql9 = "UPDATE deduction
								 SET $table_name = '0'
								 WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
								 dbQuery($sql9);
							}
						
					} else {
						if ($FieldID == '3'){
							$sql2 = "UPDATE monthly_payment
								 	SET $table_name = '$value', $tableBalance = '0', status = '1', modify_id = '$userId', modify_on = now()
								 	WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND month_year = '$monthId'";
								 	dbQuery($sql2);
						} else {
							$sql2 = "UPDATE monthly_payment
								 	SET $table_name = '$value', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
								 	WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND month_year = '$monthId'";
								 	dbQuery($sql2);
						}
						
							if ($field_id == '14'){	 
							$sql9 = "UPDATE deduction
								 SET $table_name = '0'
								 WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
								 dbQuery($sql9);
							}
					}
					//end of s7
					
					
					
				}
				//end of while 1
						
							$sql9 = "UPDATE security_users
								 SET new_status = 'old'
								 WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
								 dbQuery($sql9);
								 
							
				
				$errorMessage = "Payment was successfully added";
			}
			//end of s1
			
	}
	
	return $errorMessage;
}
*/

/*function externalContribution()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	$trustId   	  = $_POST['trustid'];
	$plNo   	  = $_POST['plno'];
	$monthId 	  = $_POST['monthid'];
	//$total   	  = $_POST['total'];
	
	$sql = "SELECT id FROM monthly_payment WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND status = '1' AND month_year = '$monthId'";
	$result = dbQuery($sql);
	if (dbNumRows($result) == 1) {
		
	} else {
							
				$sql1 = "SELECT table_name, field_id FROM nick_name";
				$result1 = dbQuery($sql1);
				while($row1 = dbFetchAssoc($result1)) {
				extract($row1);	
				
				@$tableBalance =  $table_name.'_bal';
				
				if ($opId == 5){
				$sql6 = "SELECT max($tableBalance) as bal FROM monthly_payment WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
				$result6 = dbQuery($sql6);
				$row6 = dbFetchAssoc($result6);
				$tableValue = $row6['bal'];
				} else {
				$sql6 = "SELECT sum($table_name) as bal FROM monthly_payment WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
				$result6 = dbQuery($sql6);
				$row6 = dbFetchAssoc($result6);
				$tableValue = $row6['bal'];
				}
				
				@$value  = $_POST[$table_name];
				@$balance = $tableValue + $value;
				@$tableBalance =  $table_name.'_bal';
				
				
			
				
				
				$loanId = loanID($table_name);
				$FieldID = LoanFieldID($table_name);
				$sql7 = "SELECT id, payment_schedule, balance as abalance FROM application WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND 
				        loan_id = '$loanId'";
				$result7 = dbQuery($sql7);
					if (dbNumRows($result7) > 0) {
						$mappBalance = 0;
						while($row7 = dbFetchAssoc($result7)) {
						extract($row7);	
						$appBalance = $abalance - $payment_schedule;
						$mappBalance = $mappBalance + $appBalance;
							if ($abalance != 0){
								$sql8 = "UPDATE application
										SET balance = '$appBalance'
										WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND id = '$id'";
								dbQuery($sql8);
							}
							
							if ($appBalance == 0){
								$sql9 = "UPDATE application
										 SET status = '0', date_completed = now()
										 WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND id = '$id'";
								dbQuery($sql9);
									
								$sql9 = "UPDATE deduction
									 	 SET $table_name = ($table_name - $payment_schedule)
									 	 WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
								dbQuery($sql9);
							}	
						}
						
						$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$value', $tableBalance = '-$mappBalance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND month_year = '$monthId'";
							 dbQuery($sql2);
							 
							 if ($field_id == '14'){	 
								$sql9 = "UPDATE deduction
								 SET $table_name = '0'
								 WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
								 dbQuery($sql9);
							 }
						
						
					} else {
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$value', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND month_year = '$monthId'";
							 dbQuery($sql2);
					}
					
						if ($FieldID == '3'){
							$sql2 = "UPDATE monthly_payment
								 	SET $table_name = '$value', $tableBalance = '0', status = '1', modify_id = '$userId', modify_on = now()
								 	WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND month_year = '$monthId'";
								 	dbQuery($sql2);
						} else {
							$sql2 = "UPDATE monthly_payment
								 	SET $table_name = '$value', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
								 	WHERE trust_id = '$trustId' AND pl_no = '$plNo' AND month_year = '$monthId'";
								 	dbQuery($sql2);
						}
						
						if ($field_id == '14'){	 
							$sql9 = "UPDATE deduction
								 SET $table_name = '0'
								 WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
								 dbQuery($sql9);
						}
						
					}
				}
							$sql9 = "UPDATE security_users
								 SET new_status = 'old'
								 WHERE trust_id = '$trustId' AND pl_no = '$plNo'";
								 dbQuery($sql9);
								 
							$errorMessage = "Payment was successfully added";
	}
	
	
	return $errorMessage;
}
*/


function loadContribution()
{
	$errorMessage = '';
	@$yearId 	  = $_POST['yearid'];
	@$monthId 	  = $_POST['monthid'];
	@$userId	= $_POST['userid'];
	@$sourceId	= 1;
	
	@$monthName = MonthYear($monthId);
	
	
	
$sql = "SELECT `pl_no` FROM deduction_upload WHERE pl_no != 0";
$result = dbQuery($sql);
//start while
while($row = dbFetchAssoc($result)) {
$plNo = $row['pl_no'];
$pl_no = $row['pl_no'];	

$sql = "INSERT INTO monthly_payment(pl_no, month_year, act_year_id, created_id, created_on, status) VALUES ('$plNo', '$monthId', '$yearId', '$userId', now(), '$sourceId')";
		dbQuery($sql);
$newId = dbInsertId();

	
$sql1 = "SELECT table_name, id, field_id, field_options_id as opId FROM nick_name";
$result1 = dbQuery($sql1);
//start while1
$t = 0;
while($row1 = dbFetchAssoc($result1)) {
	extract($row1);
	$loanId = loanID($table_name);
	$FieldID = LoanFieldID($table_name);	
	@$tableBalance =  $table_name.'_bal';
	
	$sql2 = "SELECT $table_name FROM deduction_upload WHERE pl_no = '$pl_no'";
	$result2 = dbQuery($sql2);
	$row2 = dbFetchAssoc($result2);

	@$value  = $row2[$table_name];
				
	$feeLoanId = LoanId($table_name);
	$t += $value;
				
	$sqlFee = "SELECT (b_charges + c_fee) as fee, app_id FROM app_tem_charges WHERE loan_id = '$feeLoanId' AND pl_no = '$plNo'";
			    $resultFee = dbQuery($sqlFee);
				if(dbNumRows($resultFee) == 1){
					$rowFee = dbFetchAssoc($resultFee);
					$fee = $rowFee['fee'];
					$appId = $rowFee['app_id'];
				
				 if ($value >= $fee){	
				  $sqlDF = "DELETE FROM app_tem_charges WHERE loan_id = '$feeLoanId' AND pl_no = '$plNo' AND app_id = '$appId'";
				  dbQuery($sqlDF);
					
				  $sqlBank1 = "INSERT INTO inflow(id, inf_id, field_id, amount,  month_year, act_year_id, created_id, created_on)
                              VALUES ('', 18, 7, '$fee', '$monthName', '$yearId', '$userId', now())";
				  dbQuery($sqlBank1);
				  @$newValue  = $row2[$table_name] - $fee;
				 } else {
					@$newValue  = $row2[$table_name]; 
				 }
						
				} else {
					@$newValue  = $row2[$table_name];
				}
				
				
				
				
				
				if ($field_id == '2' && $newValue != 0){	 
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);
					
				
		
		         
			
				} else if ($field_id == '5'){
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);
				
				} else if ($field_id == '10'){
					
					
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);

				} else if (($field_id == '3') || ($field_id == '4')) {
					
					$sql2 = "UPDATE monthly_payment
							SET $table_name = '$newValue'
							WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
							dbQuery($sql2);
	
						while($newValue != '0'){	
						$sql7 = "SELECT id, payment_schedule, balance as abalance FROM application WHERE pl_no = '$plNo' AND 
								 loan_id = '$loanId' AND balance != 0";
						$result7 = dbQuery($sql7);
						if (dbNumRows($result7) == 0) {
							break;
						} else {
							while($row7 = dbFetchAssoc($result7)) {
								$paySchedule = $row7['payment_schedule'];
								$aBalance = $row7['abalance'];
								$appId = $row7['id'];
							
							// New observation	
							if ($paySchedule != 0 ){// New observation	
								if ($newValue >= $paySchedule){
									$appBalance = $aBalance - $paySchedule;
									//$mappBalance = $mappBalance + $appBalance;
									
									
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance  - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
										
								$newValue -= $paySchedule;
									
								} else {
									$appBalance = $aBalance - $newValue;
									//$mappBalance = $mappBalance + $appBalance;
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										//$newValue = 1500;
										//$appBalnce = 1250;
										//$paySchedule = 2750;
										//$new = $paySchedule - $newValue;
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
	
								$newValue -= $newValue;
								 	
								}
								} else { // Else of new observation
								
								
								if ($aBalance >= $newValue){
									//$appBalance = $aBalance - $paySchedule;
									//$mappBalance = $mappBalance + $appBalance;
									$appBalance = $aBalance - $newValue;
									
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										/*$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $newValue)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);*/
										
										} else {
										$sql9 = "UPDATE application
								 		 		 SET balance = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
									
										/*$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $newValue)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);*/
										
										}
										
								$newValue = 0;
									
								} else {
									$newValue -=  $aBalance;
									//$mappBalance = $mappBalance + $appBalance;
									$sql8 = "UPDATE application
											 SET balance = '0', status = '0', payment_schedule = '0', date_completed = now()
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
								}
								
								
								} // end of new observation
							}
						}
					}
					
				
				} else {
						 
					$sql2 = "UPDATE monthly_payment
						 	 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);
				}
				
			}// end of while 1						
				
		} // end of while 
			
		    $sqlPosting = "INSERT INTO m_posting(pay_id, pl_no, month_year, amount, created_id, created_on) 
						   VALUES ('$newId', '$plNo', '$monthId', '$t', '$userId', now())";
			dbQuery($sqlPosting);




			
$sql = "TRUNCATE table deduction_upload";
mysql_query($sql);

$errorMessage = "Payment was successfully added";

return $errorMessage;
}



function specialContribution()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	@$userId    	  = $_POST['userid'];
	@$plNo   	  = $_POST['plno'];
	@$monthId 	  = $_POST['monthid'];
	@$yearId 	  = $_POST['yearid'];
	
	
	if ($plNo == '') {
		$errorMessage = 'You must enter the member id';
	} else if ($monthId == '') {
		$errorMessage = 'You must select the month';
	}   else {
		
		$sql = "INSERT INTO monthly_payment(pl_no, month_year, act_year_id, created_id, created_on) VALUES ('$plNo', '$monthId', '$yearId', '$userId', now())";
		dbQuery($sql);
		$newId = dbInsertId();
		// start s1
/*		if (dbNumRows($result) == 1) {*/		
				$sql1 = "SELECT table_name, id, field_id, field_options_id as opId FROM nick_name";
				$result1 = dbQuery($sql1);
				//start while1
				$t = 0;
				while($row1 = dbFetchAssoc($result1)) {
				extract($row1);
				$loanId = loanID($table_name);
				$FieldID = LoanFieldID($table_name);	
				
				@$tableBalance =  $table_name.'_bal';

				@$value  = $_POST[$table_name];
				
				$feeLoanId = LoanId($table_name);
				$t += $value;
				
				$sqlFee = "SELECT (b_charges + c_fee) as fee, app_id FROM app_tem_charges WHERE loan_id = '$feeLoanId' AND pl_no = '$plNo'";
			    $resultFee = dbQuery($sqlFee);
				if(dbNumRows($resultFee) == 1 && $value != 0){
					$rowFee = dbFetchAssoc($resultFee);
					$fee = $rowFee['fee'];
					$appId = $rowFee['app_id'];
					
					$sqlDF = "DELETE FROM app_tem_charges WHERE loan_id = '$feeLoanId' AND pl_no = '$plNo' AND app_id = '$appId'";
					dbQuery($sqlDF);
					
					@$newValue  = $_POST[$table_name] - $fee;
						
				} else {
					@$newValue  = $_POST[$table_name];
				}
				
				
				
				
				
				if ($field_id == '2' && $newValue != 0){	 
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);
					
					
		
				
				
				} else if ($field_id == '10' ){
					/*$opBalance = $tableValue + $value;
					if (($opId == 5) && ($opBalance >= 3600) || ($opId == 6) && ($opBalance >= 31200)) {
					   $sql9 = "UPDATE deduction
							 SET $table_name = '0'
							 WHERE pl_no = '$plNo'";
					   dbQuery($sql9);	
					}*/ 
					
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);

				} else if (($field_id == '3') || ($field_id == '4')) {
					
					$sql2 = "UPDATE monthly_payment
							SET $table_name = '$newValue'
							WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
							dbQuery($sql2);
	
						while($newValue != '0'){	
						$sql7 = "SELECT id, payment_schedule, balance as abalance FROM application WHERE pl_no = '$plNo' AND 
								 loan_id = '$loanId' AND balance != 0";
						$result7 = dbQuery($sql7);
						if (dbNumRows($result7) == 0) {
							break;
						} else {
							while($row7 = dbFetchAssoc($result7)) {
								$paySchedule = $row7['payment_schedule'];
								$aBalance = $row7['abalance'];
								$appId = $row7['id'];
								
								/*if ($newValue >= $paySchedule){
									$appBalance = $aBalance - $paySchedule;
									//$mappBalance = $mappBalance + $appBalance;
									
									
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance  - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
										
								$newValue -= $paySchedule;
									
								} else {
									$appBalance = $aBalance - $newValue;
									//$mappBalance = $mappBalance + $appBalance;
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										//$newValue = 1500;
										//$appBalnce = 1250;
										//$paySchedule = 2750;
										//$new = $paySchedule - $newValue;
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
	
								$newValue -= $newValue;
								 	
								}	*/
								
								
								// New Observation
								
								if ($paySchedule != 0 ){// New observation	
								if ($newValue >= $paySchedule){
									$appBalance = $aBalance - $paySchedule;
									//$mappBalance = $mappBalance + $appBalance;
									
									
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance  - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
										
								$newValue -= $paySchedule;
									
								} else {
									$appBalance = $aBalance - $newValue;
									//$mappBalance = $mappBalance + $appBalance;
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										//$newValue = 1500;
										//$appBalnce = 1250;
										//$paySchedule = 2750;
										//$new = $paySchedule - $newValue;
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
	
								$newValue -= $newValue;
								 	
								}
								} else { // Else of new observation
								
								
								if ($aBalance >= $newValue){
									//$appBalance = $aBalance - $paySchedule;
									//$mappBalance = $mappBalance + $appBalance;
									$appBalance = $aBalance - $newValue;
									
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										/*$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $newValue)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);*/
										
										} else {
										$sql9 = "UPDATE application
								 		 		 SET balance = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
									
										/*$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $newValue)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);*/
										
										}
										
								$newValue = 0;
									
								} else {
									$newValue -=  $aBalance;
									//$mappBalance = $mappBalance + $appBalance;
									$sql8 = "UPDATE application
											 SET balance = '0', status = '0', payment_schedule = '0', date_completed = now()
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									/*$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 	dbQuery($sql10);*/
									
									
										
	
								//$newValue -= $newValue;
								 	
								}
								
								
								} // end of new observation
								
								
							}
						}
					}
					
				
				
				} else {
						 
					$sql2 = "UPDATE monthly_payment
						 	 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);
				}
				
			}
			
			// end of while 1						
				$errorMessage = "Payment was successfully added";
			
			
		    $sqlPosting = "INSERT INTO m_posting(pay_id, pl_no, month_year, amount, created_id, created_on) VALUES ('$newId', '$plNo', '$monthId', '$t', '$userId', now())";
			dbQuery($sqlPosting);		
				
		/*} else {
			$errorMessage = "Payment on monthly contribution has not been made";
		}*/
		// end of s1
		
	}
	return $errorMessage;
}



function specialContribution1()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userId    	  = $_POST['userid'];
	$plNo   	  = $_POST['plno'];
	$monthId 	  = $_POST['monthid'];
	$yearId 	  = $_POST['yearid'];
	
	if ($plNo == '') {
		$errorMessage = 'You must enter the member id';
	} else if ($monthId == '') {
		$errorMessage = 'You must select the month';
	} else {
		
		$sql = "INSERT INTO monthly_payment(pl_no, month_year, act_year_id, created_id, created_on) VALUES ('$plNo', '$monthId', '$yearId', '$userId', now())";
		dbQuery($sql);
		$newId = dbInsertId();
		// start s1
/*		if (dbNumRows($result) == 1) {*/		
				$sql1 = "SELECT table_name, id, field_id, field_options_id as opId FROM nick_name";
				$result1 = dbQuery($sql1);
				//start while1
				$t = 0;
				while($row1 = dbFetchAssoc($result1)) {
				extract($row1);
				$loanId = loanID($table_name);
				$FieldID = LoanFieldID($table_name);	
				
				@$tableBalance =  $table_name.'_bal';

				@$value  = $_POST[$table_name];
				
				$feeLoanId = LoanId($table_name);
				
				$sqlFee = "SELECT (b_charges + c_fee) as fee, app_id FROM app_tem_charges WHERE loan_id = '$feeLoanId' AND pl_no = '$plNo'";
			    $resultFee = dbQuery($sqlFee);
				if(dbNumRows($resultFee) == 1 && $value != 0){
					$rowFee = dbFetchAssoc($resultFee);
					$fee = $rowFee['fee'];
					$appId = $rowFee['app_id'];
					
					$sqlDF = "DELETE FROM app_tem_charges WHERE loan_id = '$feeLoanId' AND pl_no = '$plNo' AND app_id = '$appId'";
					dbQuery($sqlDF);
					
					@$newValue  = $_POST[$table_name] - $fee;
						
				} else {
					@$newValue  = $_POST[$table_name];
				}
				
				@$newPay = $lastPay + $newValue;
				//@$balance = $tableValue + $newValue - $withValue;
				@$balance = $tableValue + $newValue;
				@$tableBalance =  $table_name.'_bal';
				@$t = $t + $newValue;
				
				
				
				/*if ($field_id == '14'){	 
					$sql9 = "UPDATE deduction
							 SET $table_name = '0'
							 WHERE pl_no = '$plNo'";
					dbQuery($sql9);
			
				
					$sql9 = "UPDATE security_users
							 SET new_status = 'old'
							 WHERE pl_no = '$plNo'";
					dbQuery($sql9);
	
					
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$newPay', $tableBalance = '$balance', status = '1', modify_id = '$userId', modify_on = now()
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND status = '1'";
					dbQuery($sql2);
				
				
				} else*/ 
				if ($field_id == '10' ){
					/*$opBalance = $tableValue + $value;
					if (($opId == 5) && ($opBalance >= 3600) || ($opId == 6) && ($opBalance >= 31200)) {
					   $sql9 = "UPDATE deduction
							 SET $table_name = '0'
							 WHERE pl_no = '$plNo'";
					   dbQuery($sql9);	
					}*/ 
					
					$sql2 = "UPDATE monthly_payment
							 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);

				} else if (($field_id == '3') || ($field_id == '4')) {	
				
				$sqlnew2 = "SELECT $table_name FROM deduction WHERE pl_no = '$plNo'";
				$resultnew2 = dbQuery($sqlnew2);
				$rownew2 = dbFetchAssoc($resultnew2);
				$tableValueNew2 = $rownew2[$table_name];
				if ($tableValueNew2 == '0' && $newValue != '0'){
					// do nothing
				} else {
					if ($newValue == 0) {
						/*$sql7 = "SELECT sum(balance) as abalance FROM application WHERE pl_no = '$plNo' AND loan_id = '$loanId'";
						$result7 = dbQuery($sql7);
						$row7 = dbFetchAssoc($result7);
						$aBalance = $row7['abalance'];
						
						$sql2 = "UPDATE monthly_payment
								 SET $table_name = '$newValue', $tableBalance = '-$aBalance', status = '1', modify_id = '$userId', modify_on = now()
								 WHERE pl_no = '$plNo' AND month_year = '$monthId'";
								 dbQuery($sql2);*/
								 
							// do nothing
					} else {
					while($newValue != '0'){
						$sql7 = "SELECT id, payment_schedule, balance as abalance FROM application WHERE pl_no = '$plNo' AND 
								 loan_id = '$loanId' AND balance != 0";
						$result7 = dbQuery($sql7);
						
						if (dbNumRows($result7) > 0) {
							
							$mappBalance = 0;
							while($row7 = dbFetchAssoc($result7)) {
								$paySchedule = $row7['payment_schedule'];
								$aBalance = $row7['abalance'];
								$appId = $row7['id'];
								
								if ($newValue >= $paySchedule){
									$appBalance = $aBalance - $paySchedule;
									$mappBalance = $mappBalance + $appBalance;
									
									
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									$sql2 = "UPDATE monthly_payment
											 SET $table_name = '$newValue'
											 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
									dbQuery($sql2);
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance  - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
										
								$newValue -= $paySchedule;
									
								} else {
									$appBalance = $aBalance - $newValue;
									$mappBalance = $mappBalance + $appBalance;
									$sql8 = "UPDATE application
											 SET balance = '$appBalance'
											 WHERE pl_no = '$plNo' AND id = '$appId'";
									dbQuery($sql8);	
									
									
									$sql2 = "UPDATE monthly_payment
											 SET $tableBalance = '$newValue'
											 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
									dbQuery($sql2);
									
										if ($appBalance == 0){
										$sql9 = "UPDATE application
								 		 		 SET status = '0', payment_schedule = '0', date_completed = now()
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
								
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else if ($appBalance < $paySchedule){
										$sql9 = "UPDATE application
								 		 		 SET payment_schedule = '$appBalance'
								 		 		 WHERE pl_no = '$plNo' AND id = '$appId'";
										dbQuery($sql9);
										
										//$newValue = 1500;
										//$appBalnce = 1250;
										//$paySchedule = 2750;
										//$new = $paySchedule - $newValue;
										
										$sql10 = "UPDATE deduction
								 				 SET $table_name = ($table_name + $appBalance - $paySchedule)
								 				 WHERE pl_no = '$plNo'";
								 		dbQuery($sql10);
										} else {
											// do nothing
										}
	
								$newValue -= $newValue;
								 	
								}	
							}
						}
					}
					}
				}
				} else {
						 
					$sql2 = "UPDATE monthly_payment
						 	 SET $table_name = '$newValue'
							 WHERE pl_no = '$plNo' AND month_year = '$monthId' AND id = '$newId'";
					dbQuery($sql2);
				}
				
			}
			
			// end of while 1						
				$errorMessage = "Payment was successfully added";
			
			
		    $sqlPosting = "INSERT INTO m_posting(pl_no, month_year, amount, created_id, created_on) VALUES ('$plNo', '$monthId', '$t', '$userId', now())";
			dbQuery($sqlPosting);		
				
		/*} else {
			$errorMessage = "Payment on monthly contribution has not been made";
		}*/
		// end of s1
		
	}
	return $errorMessage;
}


function addReceiptPayment()
{
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$amount  	  = $_POST['amount'];
	@$monthyear   = $_POST['monthyear'];
	@$nameid   	  = $_POST['nameid'];
	
	if (($amount == '') || ($nameid == '') || ($monthyear == '')){
	$errorMessage = 'One of the fields is emtpty';	
	} else {
		$sql = "SELECT  receipt_name_id, amount FROM payment_receipt WHERE id = (SELECT max(id) FROM payment_receipt)";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$onameid = $row['receipt_name_id'];
		$oamount = $row['amount'];
		if (($nameid == $onameid) && ($amount == $oamount))  {
			// Do nothing
		} else {

			$sql = "INSERT INTO payment_receipt (receipt_name_id, amount, month_year, created_id, created_on, post_date) 
			        VALUES ('$nameid', '$amount', '$monthyear', '$userId', now(), now())";
			$result = dbQuery($sql);
				
			$errorMessage = 'Payment Receipt was successfully added';
			//header('Location: index.php?view=detail&page=deduction&val='.$secId);
		}
	}
	
	return $errorMessage;
}



function addGeneralPayment()
{
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$amount  	  = $_POST['amount'];
	@$monthyear   = $_POST['monthyear'];
	@$nameid   	  = $_POST['nameid'];
	
	if (($amount == '') || ($nameid == '') || ($monthyear == '')){
	$errorMessage = 'One of the fields is emtpty';	
	} else {
		$sql = "SELECT  withdraw_name_id, amount FROM payment_withdraw WHERE id = (SELECT max(id) FROM payment_withdraw)";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$onameid = $row['withdraw_name_id'];
		$oamount = $row['amount'];
		if (($nameid == $onameid) && ($amount == $oamount))  {
			// Do nothing
		} else {

			$sql = "INSERT INTO payment_withdraw (withdraw_name_id, amount, month_year, created_id, created_on, post_date) 
			        VALUES ('$nameid', '$amount', '$monthyear', '$userId', now(), now())";
			$result = dbQuery($sql);
				
			$errorMessage = 'General payment was successfully added';
			//header('Location: index.php?view=detail&page=deduction&val='.$secId);
		}
	}
	
	return $errorMessage;
}





function addExpenditure()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$amount  	  = $_POST['amount'];
	@$purpose  	  = $_POST['purpose'];
	@$monthyear   = $_POST['monthyear'];

	if (($amount == '') || ($purpose == '') || ($monthyear == '')){
	$errorMessage = 'One of the fields is emtpty';	
	} else {
		$sql = "SELECT  purpose, amount FROM expenditure WHERE id = (SELECT max(id) FROM expenditure)";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$opurpose = $row['purpose'];
		$oamount = $row['amount'];
		if (($purpose == $opurpose) && ($amount == $oamount))  {
			// Do nothing
		} else {
			$sql = "SELECT id FROM field_options WHERE field_name = '$monthyear'";
				 $result     = dbQuery($sql);
				 $row = dbFetchAssoc($result);
				 $monthval = $row['id'];
			
			$sql = "INSERT INTO expenditure(month_year, purpose, amount, created_id, created_on) 
			        VALUES ('$monthval', '$purpose', '$amount', '$userId', now())";
			$result = dbQuery($sql);
				
			$errorMessage = 'Expenditure was successfully added';
			//header('Location: index.php?view=detail&page=deduction&val='.$secId);
		}
	}
	return $errorMessage;
}

function editExpenditure()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	$userId    	  = $_POST['userid'];
	$amount  	  = $_POST['amount'];
	@$purpose  	  = $_POST['purpose'];
	$oldId    	  = $_POST['oldid'];
	if (($amount == '') || ($purpose == '')){
	$errorMessage = 'One of the fields is emtpty';	
	} else {
		$sql = "UPDATE expenditure 
				SET purpose = '$purpose', amount = '$amount', modify_id = '$userId', modify_on = now() 
				WHERE id = '$oldId'"; 	       
		$result = dbQuery($sql);
				
		$errorMessage = 'Expenditure was successfully updated';
			//header('Location: index.php?view=detail&page=deduction&val='.$secId);
	}
	
	return $errorMessage;
}



/*
	Delete a category image where category = $catId
*/




/*
    Upload an image and return the uploaded image name 
*/
function uploadImage($inputName, $uploadDir)
{
    $image     = $_FILES[$inputName];
    $imagePath = '';
    
    // if a file is given
    if (trim($image['tmp_name']) != '') {
        // get the image extension
        $ext = substr(strrchr($image['name'], "."), 1); 

        // generate a random new file name to avoid name conflict
        $imagePath = md5(rand() * time()) . ".$ext";
        
		// check the image width. if it exceed the maximum
		// width we must resize it
		$size = getimagesize($image['tmp_name']);
		
		if ($size[2] > MAX_CATEGORY_IMAGE_WIDTH) {
			$imagePath = createThumbnail($image['tmp_name'], $uploadDir . $imagePath, MAX_CATEGORY_IMAGE_WIDTH);
		} else {
			// move the image to category image directory
			// if fail set $imagePath to empty string
			if (!move_uploaded_file($image['tmp_name'], $uploadDir . $imagePath)) {
				$imagePath = '';
			}
		}	
    }

    
    return $imagePath;
}














function checkSch()
{
	// if the session id is not set, redirect to login page
	if (!isset($_SESSION['sound_sch'])) {
	?>
	<script type="text/javascript">
                       
                        var strUrl = "<?php echo WEB_ROOT; ?>index.php?schId=error";
                        window.onload = function()
                        { 
                          window.setTimeout('redirect(strUrl)', 100);
                        }
                        function redirect(strUrl)
                        {
                          document.location=strUrl;
                        }
                        </script>
	<?php
	}
	
	
	@$id = $_SESSION['sound_sch'];
		
	
	
	
	// the user want to logout
	if (isset($_GET['logout'])) {
		doLogout();
	}
	
	return $id;
}
function checkUserId()
{
	// if the session id is not set, redirect to login page
	
	@$id = $_SESSION['sound_id'];
		
	
	
	
	// the user want to logout
	if (isset($_GET['logout'])) {
		doLogout();
	}
	
	return $id;
}


function checkOrderId()
{
	// if the session id is not set, redirect to login page
	
	//@$id = $_SESSION['sound_app_id'];
	//include ("../reg/index.php
		if (!isset($_SESSION['sound_order_id'])) {
		header('Location: ../reg/index.php');
		exit;
		}
	
	
	@$id = $_SESSION['sound_order_id'];
	return $id;
}

function checkSorderId()
{
	// if the session id is not set, redirect to login page
	
	//@$id = $_SESSION['sound_app_id'];
	//include ("../reg/index.php
			   //include ("../nstudent/opayment.php
		if (!isset($_SESSION['sound_sorder_id'])) {
		header('Location: ../nstudent/opayment.php');
		exit;
		}
	
	
	@$id = $_SESSION['sound_sorder_id'];
	return $id;
}


function doLogout()
{
	if (isset($_SESSION['sound_id'])) {
		unset($_SESSION['sound_id']);
		session_unregister('sound_id');
	}
	//include ("../../index.php	
	header('Location: ../../index.php');
	exit;
}


function doLoginEn()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userName = $_POST['email'];
	$password = $_POST['password'];
	
	// first, make sure the username & password are not empty
	if ($userName == '') {
		$errorMessage = 'You must enter your username';
	} else if ($password == '') {
		$errorMessage = 'You must enter the password';
	} else {
		
			
		// check the database and see if the username and password combo do match
		$sql = "SELECT ent_id
		        FROM tbl_ent_user 
				WHERE user_name = '$userName' AND user_password = '$password'";
		$result = dbQuery($sql);
		if (dbNumRows($result) == 1) {
			$row = dbFetchAssoc($result);
			$entId = $row['ent_id'];
			
			
			$sql = "SELECT ent_id, surname, othername, email
		        	FROM enterpreneur 
					WHERE ent_id = '$entId' AND status != 'Suspend'";
			$result = dbQuery($sql);
					if (dbNumRows($result) == 1) {
						$row = dbFetchAssoc($result);
						$_SESSION['sound_id'] = $row['ent_id'];
						$email = $row['email'];
						$sur   = $row['surname']; 
						$other = $row['othername'];
						$name  = $sur.' '.$other; 
						// log the time when the user last login
						$sql = "UPDATE tbl_ent_user
								SET regdate = NOW() 
								WHERE ent_id = '{$row['ent_id']}'";
						dbQuery($sql);
						
						 // log the user activity		   
						$sql    = "INSERT INTO tbl_activity (action, publisher_id, page, user_name, name, login) 
                 		   		   VALUES ('Log in', '$entId', 'Enterprenuer', '$email', '$name', now())";
   			     		$result = dbQuery($sql) or die(mysql_error());
						
						?>
						<script type="text/javascript">
                        
                        
                        
                        var strUrl = "<?php echo WEB_ROOT; ?>enterpreneur/enterpreneur_page/index.php";
                        
                        window.onload = function()
                        { 
                          window.setTimeout('redirect(strUrl)', 100);
                        }
                        function redirect(strUrl)
                        {
                          document.location=strUrl;
                        }
                        </script>
	
						<?php
					}else {
							$errorMessage = 'The Account has been suspended';
					}
		} else {
			$errorMessage = 'Wrong username or password';
		}		
			
	}
	
	return $errorMessage;
}

function doLoginIn()
{
	// if we found an error save the error message in this variable
	$errorMessage = '';
	
	$userName = $_POST['username'];
	$password = $_POST['password'];
	$schId = $_POST['school'];
	
	// first, make sure the username & password are not empty
	if ($userName == '') {
		$errorMessage = 'You must enter your username';
	} else if ($password == '') {
		$errorMessage = 'You must enter the password';
	} else if ($schId == '') {
		$errorMessage = 'You must select the school';
	} else {
	
	
		if ($schId == 1){
			// check the database and see if the username and password combo do match
			$sql = "SELECT std_id
					FROM tbl_british_login
					WHERE username = '$userName' AND password = '$password'";
					
		}
		if ($schId == 2){
			$sql = "SELECT std_id
					FROM tbl_international_login
					WHERE username = '$userName' AND password = '$password'";
		} 
		if ($schId == 3){
			$sql = "SELECT std_id
					FROM tbl_montessori_login
					WHERE username = '$userName' AND password = '$password'";
		}
			$result = dbQuery($sql);
		
				if (dbNumRows($result) == 1) {
					$row = dbFetchAssoc($result);
					$stdId = $row['std_id'];
					$_SESSION['sound_sch'] = $schId;
					$_SESSION['sound_id'] = $row['std_id'];
					
						?>
						<script type="text/javascript">
                       
                        var strUrl = "<?php echo WEB_ROOT; ?>nstudent/index.php";
                        window.onload = function()
                        { 
                          window.setTimeout('redirect(strUrl)', 100);
                        }
                        function redirect(strUrl)
                        {
                          document.location=strUrl;
                        }
                        </script>
						<?php	
                 }else {
                     $errorMessage = 'Error Username or Password';
                 }
		} 	
		
		
		
		
	
	
	return $errorMessage;
}

function doPay($feePayment,$schFee, $feeInt){
	$msg = '';
	$userId		= $_POST['id'];
	//$amount		= $_POST['amount'];
	$class		= $_POST['class'];
	$term 		= $_POST['term'];
	$schId      = $_POST['schId'];
	$name		= $_POST['name'];
	$tel		= $_POST['tel'];
	$email 		= $_POST['email'];

	
	$orderId = date("YmdHis");
	
	$sql1 = "SELECT service_type FROM $feeInt WHERE school_id = $schId";
	$result1 = dbQuery($sql1);
	$row1 = dbFetchAssoc($result1);
	$service = $row1['service_type'];	
	
	$sql2 = "SELECT amount FROM $schFee WHERE class_id = $class AND term_id = $term";
	$result2 = dbQuery($sql2);
	$row2 = dbFetchAssoc($result2);
	$amount = $row2['amount'];
	
		
				 $sql   = "INSERT INTO $feePayment  (order_id, std_id, class, term, amount, status, sch_id, name, tel, email, paydate, service) 
                 		   VALUES ('$orderId', '$userId', '$class', '$term', '$amount', '1', '$schId', '$name', '$tel', '$email', '', '$service')";
   			     $result = dbQuery($sql) or die(mysql_error());
				 //include ("../nstudent/bpayment.php
							//$id = dbInsertId();
				// header('Location: ../nstudent/bpayment.php?payId='.$id);
		//exit;
		$_SESSION['sound_sorder_id'] = $orderId;
		?>
		<script type="text/javascript">
                       
                        var strUrl = "<?php echo WEB_ROOT; ?>nstudent/bpayment.php";
                        window.onload = function()
                        { 
                          window.setTimeout('redirect(strUrl)', 100);
                        }
                        function redirect(strUrl)
                        {
                          document.location=strUrl;
                        }
                        </script>
                        <?php
}
	
function doUpdatePay($o, $a,$b,$c,$d,$feePayment,$feeReport) {
	$oorderId = $o;
	$orderId = $a;
    $status = $b;
    $RRR = $c;
    $statuscode = $d;

	
	
	 $sql   = "INSERT INTO $feeReport (order_id, oorder_id, status, RRR, statuscode, tdate) 
                 		   VALUES ('$orderId', '$oorderId', '$status', '$RRR', '$statuscode', now())";
   			     $result = dbQuery($sql) or die(mysql_error());
		
		if (($statuscode == '00') || ($statuscode == '01')){
			$sql = "UPDATE $feePayment
			SET paydate = NOW(), status = '2' 
			WHERE order_id = '$orderId'";
	dbQuery($sql);
				 //include ("../reg/ret.php");
			// destroy the session 
			unset($_SESSION['sound_sorder_id']);
				?>
		<script type="text/javascript">
                       
                        var strUrl = "<?php echo WEB_ROOT; ?>nstudent/invoice.php?oorderId=<?php echo $oorderId; ?>";
                        window.onload = function()
                        { 
                          window.setTimeout('redirect(strUrl)', 100);
                        }
                        function redirect(strUrl)
                        {
                          document.location=strUrl;
                        }
                        </script>
                        <?php
				//header('Location: ../nstudent/invoice.php?oorderId='.$oorderId);
				
				//exit;
		}else {
				//header('Location: ../nstudent/invoice2.php');
				?>
				<script type="text/javascript">
                       
                        var strUrl = "<?php echo WEB_ROOT; ?>nstudent/invoice2.php";
                        window.onload = function()
                        { 
                          window.setTimeout('redirect(strUrl)', 100);
                        }
                        function redirect(strUrl)
                        {
                          document.location=strUrl;
                        }
                        </script>
                        <?php
		}
?>	
	<!--<script type="text/javascript">
                       
                        var strUrl = "<?php// echo WEB_ROOT; ?>nstudent/invoice.php?payId=<?php// echo $payId; ?>";
                        window.onload = function()
                        { 
                          window.setTimeout('redirect(strUrl)', 100);
                        }
                        function redirect(strUrl)
                        {
                          document.location=strUrl;
                        }
                        </script>
	-->
<?php	
		 //header('Location: ../nstudent/invoice.php?payId='.$payId);
		//exit;
}


function doReg(){
	$msg = '';
	$surname		= $_POST['surname'];
	$firstname		= $_POST['first_name'];
	@$othername		= $_POST['other_name'];
	$email 			= $_POST['email'];
	$phone 			= $_POST['phone_number'];
	@$address 		= $_POST['address'];
	$school 		= $_POST['school'];
	$sclass 		= $_POST['sclass'];
	
	if ($school == '1'){
		$regPayment = 'tbl_british_reg_payment';
		$regReport = 'tbl_british_regpay_report';
		$regInt = 'tbl_british_regfee_int';
		
	}
	if ($school == '2'){
		$regPayment = 'tbl_international_reg_payment';
		$regReport = 'tbl_international_regpay_report';
		$regInt = 'tbl_international_regfee_int';
		
	}
	if ($school == '3'){
		$regPayment = 'tbl_montessori_reg_payment';
		$regReport = 'tbl_montessori_regpay_report';
		$regInt = 'tbl_montessori_regfee_int';

	}

	$sql1 = "SELECT service_type, amount FROM $regInt";
	$result1 = dbQuery($sql1);
	$row1 = dbFetchAssoc($result1);
	$service = $row1['service_type'];
	$amount = $row1['amount'];
	$word = $row1['word'];
	
	$sql2 = "SELECT school_name FROM tbl_school WHERE school_id = $school";
	$result2 = dbQuery($sql2);
	$row2 = dbFetchAssoc($result2);
	$schoolName = $row2['school_name'];
	
	
		$appId = userIn();
		$orderId = date("YmdHis");
				 $sql   = "INSERT INTO $regPayment(app_id, order_id, surname, firstname, othername, email, tel, address, school, sclass, status, amount, word, regdate, paydate) 
                 		   VALUES ('$appId', '$orderId', '$surname', '$firstname', '$othername', '$email', '$phone', '$address', '$schoolName', '$sclass', '1', '$amount', '$word', now(), '')";
   			     $result = dbQuery($sql) or die(mysql_error());
				 
				 $_SESSION['sound_order_id'] = $orderId;
				 $_SESSION['sound_reg'] = $school;
				 //include ("../reg/ret.php");
				 ?>
	
	<script type="text/javascript">
                       
                        var strUrl = "<?php echo WEB_ROOT; ?>reg/ret.php";
                        window.onload = function()
                        { 
                          window.setTimeout('redirect(strUrl)', 100);
                        }
                        function redirect(strUrl)
                        {
                          document.location=strUrl;
                        }
                        </script>
	
<?php
				// header('Location: ../reg/ret.php?status=ok');
				 
				/* 
					$To = $email;
					$Subject = 'Message from Brosma Network';
					$Message = 'Thank you for signinig up with Brosma Network<br>. The registration is in progress, you will be contacted once the registration is reviewed and 				   					accepted by the administrator<br>. Regards' ;
					$Headers = "From: info@brosma.com \r\n" .
					"Reply-To: info@brosma.com \r\n" .
					"Content-type: text/html; charset=UTF-8 \r\n";
					@mail($To, $Subject, $Message, $Headers);
					
					
					$To = 'adedegy@gmail.com';
					$Subject = 'Message from Brosma Network';
					$Message = 'Someone just registered as investor' ;
					$Headers = "From: info@brosma.com \r\n" .
					"Reply-To: info@brosma.com \r\n" .
					"Content-type: text/html; charset=UTF-8 \r\n";
					@mail($To, $Subject, $Message, $Headers);
				
			$msg = "You have successfully registered, kindly check your mail for further information";	 
	
	return $msg;
	*/
}
	
function doRegUpdate($o,$a,$b,$c,$d,$regPayment,$regReport) {
	$oorderId = $o;
	$orderId = $a;
    $status = $b;
    $RRR = $c;
    $statuscode = $d;

	
	
	 $sql   = "INSERT INTO $regReport (order_id, oorder_id, status, RRR, statuscode, tdate) 
                 		   VALUES ('$orderId', '$oorderId', '$status', '$RRR', '$statuscode', now())";
   			     $result = dbQuery($sql) or die(mysql_error());
		
		if (($statuscode == '00') || ($statuscode == '01')){
				 //include ("../reg/ret.php");
			// destroy the session 
				$sql = "UPDATE $regPayment
			SET paydate = NOW(), status = '2' 
			WHERE order_id = '$orderId'";
	dbQuery($sql);
				
				
				unset($_SESSION['sound_order_id']);
				//unset($_SESSION['sound_reg']);
				
				header('Location: ../reg/ret1.php?oorderId='.$oorderId);
				exit;
		}else {
				header('Location: ../reg/ret2.php');
		}
}
	
	
function invest() {
	$msg = '';
	$amount		= $_POST['amount'];
	$userId		= $_POST['userid'];
	$busId		= $_POST['busid'];
	
	$amt = displayAmount($amount);
	
	$sql = "SELECT surname, othername, email, comp_name, bus_file as filename, bus_cost FROM investor, tbl_ent_bus WHERE inv_id = '$userId' AND bus_id = $busId";
	$result = dbQuery($sql);
	$row = dbFetchAssoc($result);
	extract ($row); 
	$name = $surname.' '.$othername;
	$bcost = displayAmount($bus_cost);
	
	if ($amount <= $bus_cost){
	
	$sql   = "INSERT INTO tbl_indication (id, inv_id, bus_id, name, amount, proj_title, file, bus_cost, ind_date) 
              VALUES ('', '$userId', '$busId', '$name', '$amount', '$comp_name', '$filename', '$bus_cost', now())";
    $result = dbQuery($sql);
	
					$To = $email;
					$Subject = 'Message from Brosma Network';
					$Message = 'Thank you for indicating amount you wish to invest on Brosma Network<br>. The investment request is being processed and you will be update by the administrator soon<br>. Regards' ;
					$Headers = "From: info@brosma.com \r\n" .
					"Reply-To: info@brosma.com \r\n" .
					"Content-type: text/html; charset=UTF-8 \r\n";
					@mail($To, $Subject, $Message, $Headers);
		
		
		
		$mailto = "adedegy@gmail.com";			
		$path = 'gallery/busFile/';
		$from_mail = "info@brosma.com";
		$from_name = "Brosma Network";
		$replyto = "info@brosma.com";
		$subject = "An investor has indicated interest to invest";
		$message =  'Investor with the following details is interested in an investment:
		
		Investor Name: '. $name.'
		Amount to Invest: N'.$amt.'.00
		Project Name: '.$comp_name.'
		Business Cost: N'.$bcost.'.00
		
		
		Regards';
			
	$res = mail_attachment4($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message);
	
	$msg = "The amount has been submitted successsfully";
	} else {
		$msg = "The amount submitted should be less than or equal to the cost of the project";
	}
	return $msg;
}




function userEn(){
	$data = "1234567890abcdefghijklmnopqrstuwxyz";
				 $word1 = md5(rand() * time()) . $data;
				 @$user = EN.(substr($word1,0,8));
				 return $user;
}


function staff(){
	//$data = "1234567890";
				 $word1 = (rand() * time());
				 @$user = STA00000.(substr($word1,0,3));
				 return $user;
}


function admin(){
	//$data = "1234567890";
				 $word1 = (rand() * time());
				 @$user = ADM00000.(substr($word1,0,3));
				 return $user;
}

function member(){
	//$data = "1234567890";
				 $word1 = (rand() * time());
				 @$user = substr($word1,0,10);
				 return $user;
}

function pl(){
		$sql = "SELECT max(pl_no) as pl
		        FROM security_users 
				WHERE security_id = '4'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$plNo = $row['pl'];
		if ($plNo){
			$newPlNo = $plNo + 1;
		} else {
			$newPlNo = 1;
		}
		return $newPlNo;
}

function ippis(){
		$sql = "SELECT max(ippis) as ippis
		        FROM security_users 
				WHERE security_id = '4' AND member_id = '24'";
		$result = dbQuery($sql);
		$row = dbFetchAssoc($result);
		$ippis = $row['ippis'];
		if (@$ippis){
			$newippis = $ippis + 1;
		} else {
			$newippis = 1;
		}
		return $newippis;
}


function createThumbnail($srcFile, $destFile, $width, $quality = 75)
{
	$thumbnail = '';
	
	if (file_exists($srcFile)  && isset($destFile))
	{
		$size        = getimagesize($srcFile);
		$w           = number_format($width, 0, ',', '');
		$h           = number_format(($size[1] / $size[0]) * $width, 0, ',', '');
		
		$thumbnail =  copyImage($srcFile, $destFile, $w, $h, $quality);
	}
	
	// return the thumbnail file name on sucess or blank on fail
	return basename($thumbnail);
}

/*
	Copy an image to a destination file. The destination
	image size will be $w X $h pixels
*/
function copyImage($srcFile, $destFile, $w, $h, $quality = 75)
{
    $tmpSrc     = pathinfo(strtolower($srcFile));
    $tmpDest    = pathinfo(strtolower($destFile));
    $size       = getimagesize($srcFile);

    if ($tmpDest['extension'] == "gif" || $tmpDest['extension'] == "jpg")
    {
       $destFile  = substr_replace($destFile, 'jpg', -3);
       $dest      = imagecreatetruecolor($w, $h);
       imageantialias($dest, TRUE);
    } elseif ($tmpDest['extension'] == "png") {
       $dest = imagecreatetruecolor($w, $h);
       imageantialias($dest, TRUE);
    } else {
      return false;
    }

    switch($size[2])
    {
       case 1:       //GIF
           $src = imagecreatefromgif($srcFile);
           break;
       case 2:       //JPEG
           $src = imagecreatefromjpeg($srcFile);
           break;
       case 3:       //PNG
           $src = imagecreatefrompng($srcFile);
           break;
       default:
           return false;
           break;
    }

    imagecopyresampled($dest, $src, 0, 0, 0, 0, $w, $h, $size[0], $size[1]);

    switch($size[2])
    {
       case 1:
       case 2:
           imagejpeg($dest,$destFile, $quality);
           break;
       case 3:
           imagepng($dest,$destFile);
    }
    return $destFile;

}

/*
	Create the paging links
*/
function getPagingNav($sql, $pageNum, $rowsPerPage, $queryString = '')
{
	$result  = mysql_query($sql) or die('Error, query failed. ' . mysql_error());
	$row     = mysql_fetch_array($result, MYSQL_ASSOC);
	$numrows = $row['numrows'];
	
	// how many pages we have when using paging?
	$maxPage = ceil($numrows/$rowsPerPage);
	
	$self = $_SERVER['PHP_SELF'];
	
	// creating 'previous' and 'next' link
	// plus 'first page' and 'last page' link
	
	// print 'previous' link only if we're not
	// on page one
	if ($pageNum > 1)
	{
		$page = $pageNum - 1;
		$prev = " <a href=\"$self?page=$page{$queryString}\">[Prev]</a> ";
	
		$first = " <a href=\"$self?page=1{$queryString}\">[First Page]</a> ";
	}
	else
	{
		$prev  = ' [Prev] ';       // we're on page one, don't enable 'previous' link
		$first = ' [First Page] '; // nor 'first page' link
	}
	
	// print 'next' link only if we're not
	// on the last page
	if ($pageNum < $maxPage)
	{
		$page = $pageNum + 1;
		$next = " <a href=\"$self?page=$page{$queryString}\">[Next]</a> ";
	
		$last = " <a href=\"$self?page=$maxPage{$queryString}{$queryString}\">[Last Page]</a> ";
	}
	else
	{
		$next = ' [Next] ';      // we're on the last page, don't enable 'next' link
		$last = ' [Last Page] '; // nor 'last page' link
	}
	
	// return the page navigation link
	return $first . $prev . " Showing page <strong>$pageNum</strong> of <strong>$maxPage</strong> pages " . $next . $last; 
}


function mail_attachment4($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
    $file = $path.$filename;
    $file_size = filesize($file);
    $handle = fopen($file, "r");
    $content = fread($handle, $file_size);
    fclose($handle);
    $content = chunk_split(base64_encode($content));
    $uid = md5(uniqid(time()));
    $name = basename($file);
    $header = "From: ".$from_name." <".$from_mail.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
    $header .= "This is a multi-part message in MIME format.\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
    $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $header .= $message."\r\n\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; // use different content types here
    $header .= "Content-Transfer-Encoding: base64\r\n";
    $header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
    $header .= $content."\r\n\r\n";
    $header .= "--".$uid."--";
    @mail($mailto, $subject, "", $header);

}


function buildSchoolOptions($schId = 0)
{
	$sql = "SELECT school_id, school_parent_id, school_name
			FROM tbl_school
			ORDER BY school_id";
	$result = dbQuery($sql) or die('Cannot get School. ' . mysql_error());
	
	$programme = array();
	while($row = dbFetchArray($result)) {
		list($id, $parentId, $name) = $row;
		
		if ($parentId == 0) {
			// we create a new array for each top level programme
			$programme[$id] = array('name' => $name, 'children' => array());
		} else {
			// the child categories are put int the parent category's array
			$programme[$parentId]['children'][] = array('id' => $id, 'name' => $name);	
		}
	}	
	
	// build combo box options
	$list = '';
	foreach ($programme as $key => $value) {
		$name     = $value['name'];
		$children = $value['children'];
		
		$list .= "<option value=\"$key\"";
		if ($key == $schId) {
			$list.= " selected";
		}
			
		$list .= ">$name</option>\r\n";

		foreach ($children as $child) {
			$list .= "<option value=\"{$child['id']}\"";
			if ($child['id'] == $schId) {
				$list.= " selected";
			}
			
			$list .= ">&nbsp;&nbsp;{$child['name']}</option>\r\n";
		}
	}
	
	return $list;
}




function buildClassOptions($classId,$tableClass) {
$sql = "SELECT class_id, class_parent_id, class_name
		FROM $tableClass
		ORDER BY class_id";
	$result = dbQuery($sql) or die('Cannot get Class. ' . mysql_error());	
	$programme = array();
	while($row = dbFetchArray($result)) {
		list($id, $parentId, $name) = $row;
		
		if ($parentId == 0) {
			// we create a new array for each top level programme
			$programme[$id] = array('name' => $name, 'children' => array());
		} else {
			// the child categories are put int the parent category's array
			$programme[$parentId]['children'][] = array('id' => $id, 'name' => $name);	
		}
	}	
	
	// build combo box options
	$list = '';
	foreach ($programme as $key => $value) {
		$name     = $value['name'];
		$children = $value['children'];
		
		$list .= "<option value=\"$key\"";
		if ($key == $classId) {
			$list.= " selected";
		}
			
		$list .= ">$name</option>\r\n";

		foreach ($children as $child) {
			$list .= "<option value=\"{$child['id']}\"";
			if ($child['id'] == $classId) {
				$list.= " selected";
			}
			
			$list .= ">&nbsp;&nbsp;{$child['name']}</option>\r\n";
		}
	}
	
	return $list;
}

function buildTermOptions($TermId,$tableTerm )
{
	$sql = "SELECT term_id, term_parent_id, term_name
			FROM $tableTerm 
			ORDER BY term_id";
	$result = dbQuery($sql) or die('Cannot get Term. ' . mysql_error());
	
	$programme = array();
	while($row = dbFetchArray($result)) {
		list($id, $parentId, $name) = $row;
		
		if ($parentId == 0) {
			// we create a new array for each top level programme
			$programme[$id] = array('name' => $name, 'children' => array());
		} else {
			// the child categories are put int the parent category's array
			$programme[$parentId]['children'][] = array('id' => $id, 'name' => $name);	
		}
	}	
	
	// build combo box options
	$list = '';
	foreach ($programme as $key => $value) {
		$name     = $value['name'];
		$children = $value['children'];
		
		$list .= "<option value=\"$key\"";
		if ($key == $TermId) {
			$list.= " selected";
		}
			
		$list .= ">$name</option>\r\n";

		foreach ($children as $child) {
			$list .= "<option value=\"{$child['id']}\"";
			if ($child['id'] == $progId) {
				$list.= " selected";
			}
			
			$list .= ">&nbsp;&nbsp;{$child['name']}</option>\r\n";
		}
	}
	
	return $list;
}

function _deleteImage($id)
{

    // we will return the status
    // whether the image deleted successfully
    $deleted = false;

	// get the image(s)
    $sql = "SELECT photo_name 
            FROM security_users
            WHERE pl_no = '$id'";
	
	/*if (is_array($trustId)) {
		$sql .= " IN (" . implode(',', $trustId) . ")";
	} else {
		$sql .= " = $trustId";
	}	

    $result = dbQuery($sql);
    
    if (dbNumRows($result)) {
        while ($row = dbFetchAssoc($result)) {
	        // delete the image file
    	    $deleted = @unlink(SRV_ROOT .'upload/' . $row['photo_name']);
		}	
    }*/
    $result = dbQuery($sql);
	$row = dbFetchAssoc($result);
	$deleted = @unlink(SRV_ROOT .'upload/' . $row['photo_name']);
	
    return $deleted;
}
?>