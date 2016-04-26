<?php

class SimpleLog {

	var $type = "";
	
	var $db_host = "localhost";
	var $db_name = "";
	var $db_user = "";
	var $db_pass = "";
	
	var $file = "";
	
	var $compile = false;
	var $compile_arr = array();

	function SimpleLog( $config ) {
	
		$this->type = $config['type'];
			
		$this->db_host = $config['db_host'];
		$this->db_name = $config['db_name'];
		$this->db_user = $config['db_user'];
		$this->db_pass = $config['db_pass'];
			
		$this->file = $config['file'];
		
		$this->compile = $config['compile'];
		
	}
	
	function gettime() {
	
		return date( "Y-m-d H:i:s" );
	
	}
	
	function add_compile( $text ) {
	
		$this->compile_arr[] = array( $text, $this->gettime() );
		return true;
		
	}
	
	function get_format_logs( $log_array ) {
	
		$query_slice = array();
		
		foreach( $log_array as $log_info ) {
				
			$query_slice[] = $this->get_format( $log_info[0], $log_info[1] );
					
		}
		
		$query_slice[] = ""; //для переноса строки в конце файла
		
		return $query_slice;
		
	}
	
	//Функция определения формата записи данных логов в файл (или stdout)
	function get_format( $text, $time ) {
	
		$format = <<<HTML
[{$time}] {$text}
HTML;

return $format;
	
	}

	function add( $text, $no_compile = true ) {
	
		if ( $this->compile && $no_compile ) { //если добавление массовое
		
			$this->add_compile( $text );
			return true;
			
		}
		
		$log_array = array();
		if ( $no_compile ) $log_array[] = array( $text, $this->gettime() ); //если добавление единичное
		else $log_array = $text; //если идет массовое добавление
	
		$log_added = false;
		
		foreach( $log_array as $log_key => $log_info ) {
		
			$text_type = gettype( $log_info[0] );
			if ( $text_type == "object" && @get_class( $log_info[0] ) == "Exception" ) {}
			elseif ( in_array( $text_type, array( "array", "object" ) ) ) $log_info[0] = print_r( $log_info[0], true );
			
			$log_array[$log_key] = $log_info;
		
		}
		
		$query_slice = array();
		
		if ( $this->type == "db" ) {
		
			foreach( $log_array as $log_info ) {
			
				$log_info[0] = DB::addsafe( $log_info[0] );
				$query_slice[] = "('{$log_info[0]}', '{$log_info[1]}')";
				
			}
			
			if ( DB::query( "INSERT INTO simplelog (text, date) VALUES " . implode( ", ", $query_slice ) ) ) $log_added = true;
			
		}
		elseif ( $this->type == "file" ) {
		
			$query_slice = $this->get_format_logs( $log_array );
		
			if ( file_put_contents( $this->file, implode( "\r\n", $query_slice ), FILE_APPEND ) ) $log_added = true;
				
		}
		elseif ( $this->type == "stdout" ) {
		
			$query_slice = $this->get_format_logs( $log_array );
			
			if ( file_put_contents( 'php://stdout', implode( "\n", $query_slice ), FILE_APPEND ) ) $log_added = true;
			
		}

		
		if ( $log_added ) return true; //при единичном добавлении лога, возвращаем true в случае удачи
		else return false;
		
	}
	
	function __destruct() {
	
		if ( $this->compile ) {
		
			if ( count( $this->compile_arr ) > 0 ) {
					
					$this->add( $this->compile_arr, false ); //добавление всех отложенных логов, при compile = true в настройках
				
			}
			
		}
		
		if ( DB::get_db() ) @mysqli_close( DB::get_db() );
		
	}

}

class DB extends SimpleLog {

	var $db = false;
	
	function get_db() {
	
		return $this->db;
		
	}

	function connect( $db_host, $db_name, $db_user, $db_pass ) {
	
		$db_host_exp = explode( ":", $this->db_host );

		if ( isset( $db_location[1] ) ) {

			$this->db = @mysqli_connect( $db_host_exp[0], $this->db_user, $this->db_pass, $this->db_name, $db_host_exp[1] );

		} else {

			$this->db = @mysqli_connect( $db_host_exp[0], $this->db_user, $this->db_pass, $this->db_name );

		}

		if( !$this->db ) {
		
			return false;
		
		}

		mysqli_query( $this->db, "SET NAMES 'cp1251'" );

		return true;
		
	}
	
	public function query( $query ) {

		if( ! $this->db ) self::connect( $this->db_host, $this->db_name, $this->db_user, $this->db_pass );

		if ( mysqli_query( $this->db, $query ) ) return true;
		else return false;
		
		}
	
	public function addsafe( $text ) {
	
		if( !$this->db ) self::connect( $this->db_host, $this->db_name, $this->db_user, $this->db_pass );
		
		if ( $this->db ) return mysqli_real_escape_string( $this->db, $text );
		else return addslashes( $text );
		
	}

}


?>