<?php
include "dbutil.php";

class GoogleSheetsMySQLBridge{
	
	private $db_host = '';
	private $db_username = '';
	private $db_password = '';
	private $db_name = '';
	
	private $RULES = array(
			array(
				'from_csv' => 'https://docs.google.com/spreadsheets/d/1BirUILeSVAFJs7E6IYr84BZiUij7BQt9FJohhDUdgVY/pub?gid=868951319&single=true&output=csv',
				'to_table' => 'tt_category',
				'sync_type'=> 'delete_and_insert'
			),
		);
		
	public function setMYSQLAccess($db_host, $db_username, $db_password, $db_name){
		$this->db_host = $db_host;
		$this->db_username = $db_username;
		$this->db_password = $db_password;
		$this->db_name = $db_name;
		
	}
	public function setGoogleSheetsRules($rules){
		$this->RULES = $rules;
	}
	
	
	public function sync()
	{
		
		$SYNCS = $this->RULES;		
		$db = new DBUtil($this->db_host, $this->db_username, $this->db_password, $this->db_name);
		
		
		for ($i = 0; $i < count($SYNCS); $i++){
			$to_table = $SYNCS[$i]['to_table'];
			$sync_type = $SYNCS[$i]['sync_type'];
			
			
			echo "<h1>$to_table</h1>";
			$fields = $db->fetchAll("show columns from $to_table");
			$db_fields = array();
			echo "<h3>Table $to_table has ". count($fields) . " fields</h3>";
			for($j = 0; $j < count($fields); $j++){
				$db_fields[] = $fields[$j]['Field'];
				echo "<div>{$fields[$j]['Field']}</div>";
			}
			
			if($sync_type == 'delete_and_insert'){
				$db->query("TRUNCATE $to_table");
			}
			
			
			$url = $SYNCS[$i]['from_csv'] . "&t=". time();
			echo "<h5>$url</h5>";

			ini_set('auto_detect_line_endings', TRUE);
			if (($handle = fopen($url, 'r')) !== FALSE) 
			{
				$header = fgetcsv($handle, 2048, ',', '"');
				echo "<h3>CSV has ". count($header) . " columns</h3>";				
				for($j = 0; $j < count($header); $j++){					
					$ok = in_array($header[$j], $db_fields);		
					$color = $ok !== false ? 'back' : 'red';
					echo "<div style='color:$color'>{$header[$j]}</div>";
				}
				
				while (($data = fgetcsv($handle, 2048, ',', '"')) !== FALSE) 
				{
					if(isset($SYNCS[$i]['fake_data'])){
						for ($j = 0; $j < count($SYNCS[$i]['fake_data']); $j++)
						{
							$field = $SYNCS[$i]['fake_data'][$j];
							if(!in_array($field, $header))
							{
								$header[] = $field;
							}
							$data[] = rand(50000, 10000000);
							
						}
					}
					
					if($sync_type == 'delete_and_insert'){
						
						$query = $this->build_insert_query($db, $to_table, $header,$data);					
						
					} else if ($sync_type = 'update_if_exist_or_insert_new'){
						//check exist
						$id_field = $SYNCS[$i]['id_field'];
						$exists = $this->check_exists($db, $to_table, $id_field, $header, $data);
						
						//update 
						if(!$exists)
						{
							$query = $this->build_insert_query($db, $to_table, $header,$data);	
						} else {
							$query = $this->build_update_query($db, $to_table, $id_field,  $header,$data);	
						}
					}
					echo $query;
					echo "<br>";
					$db->query($query);
					
					
				}
				fclose($handle);
			}
		}
	}	
	function build_insert_query($db, $table, $header,$data){
		for ($i = 0; $i < count($header); $i++)
		{
			$header[$i] = "`".$header[$i]."`";
		}
		$header_seg = implode(",", $header);
		
		for ($i = 0; $i < count($data); $i++)
		{
			$data[$i] = "'".$db->escape_string($data[$i]). "'";
		}
		$data_seg = implode(",", $data);
		return "INSERT INTO `$table` 
			($header_seg) 
			VALUES ($data_seg);";
	}
	function get_id_value($id_field, $header, $data){
		$index = array_search($id_field, $header);
		if($index === false){
			echo "ERORRRRRRRRR $id_field not found in header csv";
		}
		return $data[$index];
	}
	function check_exists($db, $table, $id_field, $header, $data){
		
		$id_value = $this->get_id_value($id_field, $header, $data);		
		$rs = $db->fetchAll("select * from $table where $id_field = '$id_value'");
		
		return count($rs) > 0;		
	}
	function build_update_query($db, $table, $id_field, $header,$data){
		
		$id_value = $this->get_id_value($id_field, $header, $data);		
		$seg = array();
		for ($i = 0; $i < count($header); $i++)
		{
			$field_key = $header[$i];
			$field_val = $db->escape_string($data[$i]);
			$seg[] = "`$field_key` = '$field_val'";
		}
		$seg_sql = implode(",", $seg);
		return "UPDATE `$table` SET $seg_sql where $id_field = '$id_value'";
	}
}