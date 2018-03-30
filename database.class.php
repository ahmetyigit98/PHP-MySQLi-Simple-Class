<?php

/*
   PHP-MySQLi-Simple-Class
   30.03.2018
*/

class Database {

	public $server   = '';
	public $user     = '';
	public $pass     = '';
	public $database = '';
	public $debug_sql = 0;
	public $pconnect = 0;
	public $error_page = '/500.html';
	public $logging = 1;
	public $log_dir = '';
	public $charset = 'UTF-8';
	public $affected_rows = 0;
	public $pre = '';

	private $error = '';
	private $errno = 0;
	private $errurl = '';
	private $link = '';
	private $query_id = 0;
	private $result = '';
	private $queries = array();
	private $sql_num = 0;
	
	/**
	* Set up connection to the database
	* @param  String  $server     Mysql server hostname
	* @param  String  $user       Mysql username
	* @param  String  $pass       Mysql password
	* @param  String  $database   Database to use
	*/

	public function Database($server='localhost', $user='', $pass='', $database='', $pre='') {
		
		$server = $this->pconnect === 1 ? 'p:'.$server : $server;
		
		$this->server = $server;
		$this->user = $user;
		$this->pass = $pass;
		$this->database = $database;
		$this->pre = $pre;
	
	}

		
	/**
	* Connect to the database
	* @return Object              Mysqli
	*/
	
	public function connect() {
		
		mb_internal_encoding($this->charset);
		mb_regex_encoding($this->charset);

		mysqli_report(MYSQLI_REPORT_STRICT);

		try {
			$this->link = mysqli_connect($this->server, $this->user, $this->pass, $this->database);
		} catch ( Exception $e ) {
			$this->oops("Could not connect to server: <b>$this->server</b>.");
		}

		mysqli_set_charset($this->link, $this->charset);

		$this->server='';
		$this->user='';
		$this->pass='';
		$this->database='';

		return $this->link;
	
	}
	
	/**
	* Close connection to the database
	* @return
	*/
	
	public function close() {
		
		if(!@mysqli_close($this->link)){
			$this->oops("Connection close failed.");
		}
		
		return true;
	
	}
	
	/**
	* Gets the number of affected rows in a previous MySQL operation.
	* @return integer The affected rows
	*/
	
	public function affected_rows(){
		return $this->affected_rows;
	}
	
	/**
	* Escape string
	* @param  string  $string     input text
	* @return string  $string     output text
	*/
	
	
	public function escape($string) {
		
		if(get_magic_quotes_runtime()) $string = stripslashes($string);
		
		return @mysqli_real_escape_string($this->link, $string);
	
	}
	
	/**
	* Strip slashes
	* @param  string  $string     input text
	* @return string  $string     output text
	*/

	public function slashes($string) {
		
		if(get_magic_quotes_runtime()==0) $string = stripslashes($string);
		else $string = $string;

		return $string;
	
	}
	
	/**
	* Clean a string
	* @param  string  $string     input text
	* @return string  $string     output text
	*/
	
	public function clean($string) {
		
		$string = trim( $string );
		$string = preg_replace("/[^\x20-\xFF]/","",@strval($string));
		$string = strip_tags($string);
		$string = htmlspecialchars( $string, ENT_QUOTES );
		$string = mysqli_real_escape_string($this->link, $string);

		return $string;
	
	}

	/**
	* Make a custom query to the database
	* @param  string  $string     sql query
	* @return object  $result     mysqli object
	*/
	
	public function query($sql) {
		
		$start = microtime(true);

		$result = @mysqli_query($this->link, $sql);

		if (!$result) {
			$this->oops("<b>MySQL Query fail:</b> $sql");
			return false;
		}

		$this->affected_rows = @mysqli_affected_rows($this->link);

		$this->sql_num++;
		$this->queries[] = $sql; 

		$duration = microtime(true) - $start;

		$duration = round($duration, 4);

		if($this->debug_sql == 2) echo '<p>[<b>'.$duration.'</b> / '.$this->affected_rows.'] '.$sql.'</p>';

		return $result;
	
	}

	/**
	* Fetch query results
	* @param  object  $result     mysqli object
	* @return array  $record   array of database results
	*/
	
	public function fetch_array($result) {
			
		if(empty($result)) return false;
		
		$record = mysqli_fetch_assoc($result);
		
		return $record;
	
	}
	
	/**
	* Query + fetch results
	* @param  string  $sql     mysqli query
	* @return array  $out   array of database results
	*/
	
	public function fetch_all_array($sql) {
		
		$result = $this->query($sql);
		
		if(empty($result)) return false;
		
		$out = array();

		while ($row = $this->fetch_array($result)) { 
			$out[] = $row;
		}

		$this->free_result($result);
		
		return $out;
	
	}
	
	/**
	* Free result
	*/
	
	public function free_result($result) {
		
		mysqli_free_result($result);
		
		return true;

	}
	
	/**
	* Make a query for get a first row
	* @param  string  $query     sql query
	* @return object  $out     mysqli object
	*/

	public function query_first($query) {
		
		$result = $this->query($query);
		$out = $this->fetch_array($result);
		$this->free_result($result);
		
		return $out;
	
	}

	/**
	* Update
	* @param  string  $table   table
	* @param array  $data     array of values
	* @param string  $where    where condition
	*/
	
	public function query_update($table, $data, $where='1') {
		
		$q="UPDATE `".$this->pre.$table."` SET ";

		foreach($data as $key=>$val) {
			if(strtolower($val)=='null') $q.= "`$key` = NULL, ";
			elseif(strtolower($val)=='now()') $q.= "`$key` = NOW(), ";
			elseif(strtolower($val)=='unix_timestamp()') $v.="UNIX_TIMESTAMP(), ";
			elseif(preg_match("/HEX\((.+)\)/", $val) == true)  $q.= '`'.$key.'` = '.$val.', '; 
			else $q.= "`$key`='".$this->escape($val)."', ";
		}

		$q = rtrim($q, ', ') . ' WHERE '.$where.';';

		return $this->query($q);
	
	}
	
	/**
	* Insert ignore
	* @param  string  $table   table
	* @param array  $data     array of values
	*/
	
	public function query_insert_ignore($table, $data) {
		$q="INSERT IGNORE INTO `".$this->pre.$table."` ";
		$v=''; $n='';

		foreach($data as $key=>$val) {
			$n.="`$key`, ";
			if(strtolower($val)=='null') $v.="NULL, ";
			elseif(strtolower($val)=='now()') $v.="NOW(), ";
			elseif(strtolower($val)=='unix_timestamp()') $v.="UNIX_TIMESTAMP(), ";
			elseif(preg_match("/HEX\((.+)\)/", $val) == true)  $q.= '`'.$key.'` = '.$val.', '; 
			else $v.= "'".$this->escape($val)."', ";
		}

		$q .= "(". rtrim($n, ', ') .") VALUES (". rtrim($v, ', ') .");";

		if($this->query($q)){

			return mysqli_insert_id($this->link);
		} else return false;

	}
	
	/**
	* Insert
	* @param  string  $table   table
	* @param array  $data     array of values
	*/
	
	public function query_insert($table, $data) {
		
		if(empty($data)) return false;
		
		$q="INSERT INTO `".$this->pre.$table."` ";
		$v=''; $n='';

		foreach($data as $key=>$val) {
			$n.="`$key`, ";
			if(strtolower($val)=='null') $v.="NULL, ";
			elseif(strtolower($val)=='now()') $v.="NOW(), ";
			elseif(strtolower($val)=='unix_timestamp()') $v.="UNIX_TIMESTAMP(), ";
			elseif(preg_match("/HEX\((.+)\)/", $val) == true)  $v.= ''.$val.', '; 
			else $v.= "'".$this->escape($val)."', ";
		}

		$q .= "(". rtrim($n, ', ') .") VALUES (". rtrim($v, ', ') .");";

		if($this->query($q)){

			return mysqli_insert_id($this->link);
		
		} else return false;

	}
	
	/**
	* Replace
	* @param  string  $table   table
	* @param array  $data     array of values
	*/
	
	public function query_replace($table, $data, $where='') {
		
		$q="REPLACE INTO `".$this->pre.$table."` ";
		$v=''; $n='';

		foreach($data as $key=>$val) {
			$n.="`$key`, ";
			if(strtolower($val)=='null') $v.="NULL, ";
			elseif(strtolower($val)=='now()') $v.="NOW(), ";
			else $v.= "'".$this->escape($val)."', ";
		}

		if($where) $where = ' WHERE '.$where.'';

		$q .= "(". rtrim($n, ', ') .") VALUES (". rtrim($v, ', ') .")$where;";

		if($this->query($q)){
			return mysqli_insert_id($this->link);
		}
		else return false;

	}

	/**
	* Error message
	* @param  string  $msge message text
	*/
	
	public function oops($msge='') {
		
		# MySQLi Errors - begin

		if($this->link) {
			$this->error=mysqli_error($this->link);
			$this->errno=mysqli_errno($this->link);
		} 
		
		$this->errurl = 'http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		
		# MySQLi Errors - end

		# Logging in file - begin

		if($this->logging==1) $this->sql_log($msge);
		
		# Logging in file - end

		# display error message - begin
		
		if($this->debug_sql == 0) { // no debug, production mode
			
			header('location: '.$this->error_page.''); 
			die;
			
		} elseif($this->debug_sql == 1 || $this->debug_sql == 2) {  // debug mode
			
			$error_html = '';
			$error_text = !empty($this->error) && mb_strlen($this->error) > 0 ? $this->error : '';
			$error_dt = date("l, F j, Y \a\\t g:i:s A");
			$error_requrl = !empty($_SERVER['REQUEST_URI']) && mb_strlen($_SERVER['REQUEST_URI']) > 0 ? $_SERVER["DOCUMENT_ROOT"].$_SERVER['REQUEST_URI'] : '';
			$error_ref = !empty($_SERVER['HTTP_REFERER']) &&  mb_strlen($_SERVER['HTTP_REFERER']) > 0 ? $_SERVER['HTTP_REFERER'] : '';

			$error_html .= '
			<table align="center" border="1" cellspacing="0" style="background:white;color:black;width:80%;">
				<tr>
					<th colspan="2">Database Error</th>
				</tr>
				<tr>
					<td align="right" valign="top">Message:</td>
					<td>'.$msge.'</td>
				</tr>';
			
			if(!empty($error_text)) {
				
				$error_html .= '
				<tr>
					<td align="right" valign="top" nowrap>MySQL Error:</td>
					<td>'.$error_text.'</td>
				</tr>';
			
			}
			
			$error_html .= '
			<tr>
				<td align="right">Date:</td>
				<td>'.$error_dt.'</td>
			</tr>';
			
			if(!empty($this->errurl)) {
			
				$error_html .= '
				<tr>
					<td align="right">URL:</td>
					<td><a href="'.$this->errurl.'">'.$this->errurl.'</a>
					</td>
				</tr>';
				
			}
			
			if(!empty($error_requrl)) {
			
				$error_html .= '<tr>
					<td align="right">Script:</td>
					<td><a href="'.$error_requrl.'">'.$error_requrl.'</a>
					</td>
				</tr>';
				
			}
	
			if(!empty($error_ref)) {
				$error_html .= '
				<tr>
					<td align="right">Referer:</td>
					<td><a href="'.$error_ref.'">'.$error_ref.'</a></td>
				</tr>';
			
			}
			
			$error_html .= '</table>';
			
			echo $error_html;
			die;
			
		}  else {
			
			die;
			
		}
		
		# display error message - end
	
	}
	
	/**
	* Write SQL error in file
	* @param  string  $msge message text
	*/
	
	public function sql_log($msge='') {

		$url = $this->errurl;
		$string = $this->error.' '.$msge.'';

		if(isset($_SESSION['user']['login'])) $userstr = $_SESSION['user']['login']; else $userstr = 'anonymous';

		$fullstring = date('Y-m-d H:i:s').' '.$_SERVER['REMOTE_ADDR'].' '.$url.' '.$userstr.' '.$string."\r\n";

		$this->log_dir = SITEPATH.'logs/mysql/';
		
		$filename = $this->log_dir.'mysql.txt';
		$fd = fopen($filename,'a+');
		fwrite($fd, $fullstring);
		fclose($fd);

		@chmod($filename, 0777);

		return true;

	}

} // eof

?>