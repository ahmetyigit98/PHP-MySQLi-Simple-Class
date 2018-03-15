<?php

/*
   PHP-MySQLi-Simple-Class
   16.03.2018
*/

class Database {

	public $server   = "";
	public $user     = "";
	public $pass     = "";
	public $database = "";
	public $debug_sql = 0;
	public $pconnect = 0;
	public $error_page = "/500.html";
	public $logging = 1;
	public $log_dir = '';
	public $charset = "UTF-8";
	public $affected_rows = 0;
	public $my_queries = array();

	private $error = "";
	private $errno = 0;
	private $link = '';
	private $query_id = 0;
	private $result = '';
	
	/**
	 * Construct connection to the database
	 * @param  String  $server     Mysql server hostname
	 * @param  String  $user       Mysql username
	 * @param  String  $pass       Mysql password
	 * @param  String  $database   Database to use
	 * @return 
	 */

	public function Database($server='localhost', $user='', $pass='', $database='') {
		
		$server = $this->pconnect === 1 ? 'p:'.$server : $server;
		
		$this->server = $server;
		$this->user = $user;
		$this->pass = $pass;
		$this->database = $database;
	
	}

	// Constructor - End	

	// Connect - Begin
	
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
	
	// Connect - End

    // Close connection - Begin
	
	public function close() {
		
		if(!@mysqli_close($this->link)){
			$this->oops("Connection close failed.");
		}
	
	}
	
	// Close connection - End
	
	// Escape - Begin
	
	public function escape($string) {
		
		if(get_magic_quotes_runtime()) $string = stripslashes($string);
		
		return @mysqli_real_escape_string($this->link, $string);
	
	}

	public function slashes($string) {
		
		if(get_magic_quotes_runtime()==0) $string = stripslashes($string);
		else $string = $string;

		return $string;
	
	}
	
	public function clean($string) {
		
	$string = trim( $string );
	$string = preg_replace("/[^\x20-\xFF]/","",@strval($string));
	$string = strip_tags($string);
	$string = htmlspecialchars( $string, ENT_QUOTES );
	$string = mysqli_real_escape_string($this->link, $string);

	return $string;
	
	}

	// Escape - End
	
	// Query - Begin
	
	public function query($sql) {
		
		$start = microtime(true);

		$result  = @mysqli_query($this->link, $sql);

		if (!$result) {
			$this->oops("<b>MySQL Query fail:</b> $sql");
			return false;
		}

		$this->affected_rows = @mysqli_affected_rows($this->link);

		$this->sql_num++;
		$this->my_queries[] = $sql; 

		$duration = microtime(true) - $start;

		$duration = round($duration, 4);

		if($this->debug_sql == 2) echo '<p>[<b>'.$duration.'</b> / '.$this->affected_rows.'] '.$sql.'</p>';

		return $result;
	
	}

	// Query - End

	// Fetch array - Begin
	
	public function fetch_array($result) {
			
		if(empty($result)) return false;
		
		$record = mysqli_fetch_assoc($result);
		
		return $record;
	
	}
	
	// Fetch array - End

	// Fetch array all - Begin
	
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
	
	// Fetch array all - End

	// Free result - Begin
	
	public function free_result($result) {
		
		mysqli_free_result($result);
		
		return true;

	}
	
	// Free result - End
	
	// Single query - Begin

	public function query_first($query) {
		
		$result = $this->query($query);
		$out = $this->fetch_array($result);
		$this->free_result($result);
		
		return $out;
	
	}

	// Single query - End

	// Update - Begin
	
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
	
	// Update - End

    // Insert ignore - Begin
	
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
	
	// Insert ignore - End

	// Insert - Begin
	
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
	
	// Insert - End

	// Replace - Begin
	
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
	
	// Replace - End

	// Log - Begin
	
	public function oops($msge='') {

		if($this->link){
			$this->error=mysqli_error($this->link);
			$this->errno=mysqli_errno($this->link);
		}
		else{
			$this->error=mysqli_error();
			$this->errno=mysqli_errno();
		}

		// log

		if($this->logging==1) $this->sql_log($msge);

		// msg

		if($this->debug_sql == 1) {

		?>
			<table align="center" border="1" cellspacing="0" style="background:white;color:black;width:80%;">
			<tr><th colspan=2>Database Error</th></tr>
			<tr><td align="right" valign="top">Message:</td><td><?php echo $msge; ?></td></tr>
			<?php if(mb_strlen($this->error)>0) echo '<tr><td align="right" valign="top" nowrap>MySQL Error:</td><td>'.$this->error.'</td></tr>'; ?>
			<tr><td align="right">Date:</td><td><?php echo date("l, F j, Y \a\\t g:i:s A"); ?></td></tr>
			<tr><td align="right">Script:</td><td><a href="<?php echo @$_SERVER['REQUEST_URI']; ?>"><?php echo @$_SERVER['REQUEST_URI']; ?></a></td></tr>
			<?php if(mb_strlen(@$_SERVER['HTTP_REFERER'])>0) echo '<tr><td align="right">Referer:</td><td><a href="'.@$_SERVER['HTTP_REFERER'].'">'.@$_SERVER['HTTP_REFERER'].'</a></td></tr>'; ?>
			</table>
		<?php die();} else { header('location: '.$this->error_page.''); die; }
	}
	
	public function sql_log($msge='') {

		$url = 'http://'.$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];

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
	
	// Log - End

} // eof

?>