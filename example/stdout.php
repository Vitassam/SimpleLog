<?php

//Пример вывода логов в stdout

require_once( "../simplelog.class.php" );

//Настройки
$simplelog_config = array(
"type" => "stdout", //db OR file OR stdout (в зависимости от типа вывода)

"db_host" => "localhost",
"db_name" => "simplelog",
"db_user" => "simplelog",
"db_pass" => "123456",

"file" => dirname( __FILE__ ) . "/simple_log.txt", //файл для записи логов

"compile" => false, //если true, то все логи добавляются одним запросом, в конце выполнения
);

$simple_log = new SimpleLog( $simplelog_config );

$simple_log->add( "string" );

$simple_log->add( array( "1", "2" ) );

$simple_log->add( (object) 'abcd' );

try {
	throw new Exception( "Exception!" );
}
catch( Exception $e ) {
    $simple_log->add( $e );
}

?>
