<?php
	error_reporting(E_ALL);
	include "googlesheets_mysql_bridge/googlesheets_mysql_bridge.php";

	$bridge = new GoogleSheetsMySQLBridge();
	$bridge->setMYSQLAccess('localhost', 'root', '', 'gmbdemo');
	$bridge->setGoogleSheetsRules(
		array(
			array(
				'from_csv' => 'https://docs.google.com/spreadsheets/d/1JFhllpCTTBOz9e4Y_HeYkCiK-Z3w2cZM9gZ7eoGblVw/pub?gid=0&single=true&output=csv',
				'to_table' => 'apps',
				'sync_type'=> 'delete_and_insert'
			)
		)
	);
	$bridge->sync();


