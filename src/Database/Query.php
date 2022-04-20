<?php
	
	namespace Reborn\Phoenix\Database;
	
	use PDO;
	
	class Query
	{
		
		private \PDOStatement $statement;
		
		/**
		 * @throws Exception
		 */
		public function __construct(string $query, array $parameters = null, bool $execute = true, Connection $connection = null)
		{
			$connection ??= Connection::getFirstInstance();
			
			$this->statement = $connection->prepare($query);
			
			if ($execute) {
				$this->execute($parameters);
			}
		}
		
		public function execute(array $parameters): bool
		{
			return $this->statement->execute($parameters);
		}
		
		public static function getRecord(string $query, array $parameters = null, Connection $connection = null): array|bool
		{
			return (new Query($query, $parameters, true, $connection))->getNextRecord();
		}
		
		public function getNextRecord(): array|bool
		{
			return $this->statement->fetch(PDO::FETCH_ASSOC);
		}
		
		public static function getRecords(string $query, array $parameters = null, Connection $connection = null): array
		{
			return (new Query($query, $parameters, true, $connection))->getAllRecords();
		}
		
		public function getAllRecords(): array
		{
			return $this->statement->fetchAll(PDO::FETCH_ASSOC);
		}
		
		public static function getField(string $query, array $parameters = null, Connection $connection = null): array|bool
		{
			return (new Query($query, $parameters, true, $connection))->getNextField();
		}
		
		public function getNextField(): mixed
		{
			return $this->statement->fetch(PDO::FETCH_COLUMN);
		}
		
		public static function getFields(string $query, array $parameters = null, Connection $connection = null): array
		{
			return (new Query($query, $parameters, true, $connection))->getAllFields();
		}
		
		public function getAllFields(): mixed
		{
			return $this->statement->fetchAll(PDO::FETCH_COLUMN);
		}
	}