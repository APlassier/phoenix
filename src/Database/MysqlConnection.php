<?php
	
	namespace Reborn\Phoenix\Database;
	
	class MysqlConnection extends Connection
	{
		public function __construct(string $host, string $database, string $username, string $password)
		{
			$dsn = "mysql:host={$host};dbname={$database}";
			
			parent::__construct($dsn, $username, $password);
		}
	}