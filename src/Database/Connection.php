<?php
	
	namespace Reborn\Phoenix\Database;
	
	use PDO;
	
	class Connection extends PDO
	{
		
		private static Connection $firstInstance;
		
		public function __construct(string $dsn, string $username, string $password)
		{
			parent::__construct($dsn, $username, $password);
			
			self::$firstInstance ??= $this;
		}
		
		/**
		 * @throws Exception
		 */
		public static function getFirstInstance(): Connection
		{
			if(!isset(self::$firstInstance)) {
				throw new Exception("No connection established yet");
			}
			
			return self::$firstInstance;
		}
		
	}