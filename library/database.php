<?php
require_once 'library/db.php';

$db = new mysqli($dbHost,$dbUser,$dbPass,$dbName);
// Check for errors
if($db->connect_errno > 0){
die('Unable to connect to database [' . $db->connect_error . ']');
}

$db->autocommit(TRUE);

function dbQuery($sql)
{	
	global $db;
	return $db->query($sql);
	#return $sql->get_result();
}

function dbFreeResult($result)
{
	return $result->free();
}

function dbStoreResult($sql)
{
	return $sql->store_result();
}

function dbAffectedRows()
{
	global $db;
	return $db->affected_rows;
}

function dbFetchAssoc($result)
{
	return $result->fetch_assoc();
}


function dbNumRows($result)
{
	return @$result->num_rows;
}

function dbEscape()
{
	global $db;
	return $db->escape_string;
}

function tbClose($sql)
{
	return $sql->close();
}

function dbClose($db)
{
	return $db->close();
}

function dbFetchArray($result) {
	//return $result->fetch();
	return mysql_fetch_array($result);
}

function dbFetchRow($result) 
{
	return mysql_fetch_row($result);
}

function dbInsertId()
{
    global $db;
	//return mysql_insert_id();
        return $db->insert_id;
}

function dbSelect($dbName)
{
	return mysql_select_db($dbName);
}
?>