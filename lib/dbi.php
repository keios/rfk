<?php

class DBI {
	var $database;
	function DBI($hostname, $user, $pass, $db){
		$this->database = new mysqli($hostname, $user, $pass, $db);
		if ($this->database->connect_error) {
    		die('Connect Error (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);
		}
        $this->execute("use $db;");
        $this->execute('set names \'utf8\';');
	}
	function escape($string){
		return $this->database->real_escape_string($string);
	}
	function query($query){
		$result = $this->database->query($query);
		if($this->database->errno){
			error_log('[radioauth] '.$this->database->error.' [query] '.$query);
			return false;
		}
		return $result;
	}
	function execute($query){
		if($this->query($query)){
			return true;		
		}else{
			return false;
		}
	}
	function num_rows($result){
		return $result->num_rows;	
	}
	function fetch($result){
		return $result->fetch_assoc();	
	}
	function close(){
		$this->database->close();	
	}
	function insert_id(){
		return $this->database->insert_id;	
	}
}

?>