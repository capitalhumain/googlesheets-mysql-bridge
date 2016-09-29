<?php

class DBUtil {

    public $_mysqli;

	public function __construct($db_host, $db_user_name, $db_password, $db_name) {
        $this->_mysqli = new mysqli($db_host, $db_user_name, $db_password, $db_name);
		$this->_mysqli->query("SET NAMES 'utf8'");
    }

	public function escape_string($query){
		return $this->_mysqli->escape_string($query);
	}  

    public function query($query) {
        return $this->_mysqli->query($query);
    }

    public function getLastId() {
        return $this->_mysqli->insert_id;
    }

    public function fetchRow($query) {
        $result = $this->query($query);
        if ($result === false) {
            echo "FAIELD " . $query;
            return null;
        }
        return $result->fetch_assoc();
    }

    public function fetchAll_LastQuery_From_MultiQuery($query) {

        $result = $this->_mysqli->multi_query($query);

        $results = array();

        do {
            $records = array();

            $result = $this->_mysqli->store_result();
            if ($result != False) {
                while ($row = $result->fetch_assoc()) {
                    $records[] = $row;
                }
            }
            if (is_object($result)) {
                $result->free_result();
            }
            $results[] = $records;
        } while ($this->_mysqli->next_result());

        if (count($results) > 1)
            return $results[count($results) - 1];
        return False;
    }

    public function fetchAll($query) {
        $return = array();
        $result = $this->query($query);
        if ($result === false) {
            echo "FAIELD " . $query;
        } else {
            while ($row = $result->fetch_assoc()) {
                $return[] = $row;
            }
        }
        return $return;
    }

    public function getAffected() {
        return $this->_mysqli->affected_rows;
    }

    public function begin() {
        $query = "START TRANSACTION;";
        $this->query($query);
    }

    public function commit() {
        $query = "COMMIT;";
        $this->query($query);
    }

    public function rollback() {
        $query = "ROLLBACK;";
        $this->query($query);
    }

}
