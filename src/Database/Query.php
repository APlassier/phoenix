<?php
	
	namespace Reborn\Phoenix\Database;
	
	use PDO;
	use PDOStatement;
	use Reborn\Phoenix\Database\Exception\NotExecutedQueryException;
	
	class Query
	{
		private PDOStatement $statement;
		private Database $database;
		private bool $executed = false;
		
		/** The constructor prepare the query to be executed
		 *
		 * @param Database $database
		 * @param string   $query
		 */
		public function __construct(Database $database, string $query) {
			$this->database = $database;
			$this->statement = $this->database->prepare($query);
		}
		
		/** Execute the query.
		 * Has to be done before retrieving fields/records
		 *
		 * @param array $parameters array of parameter for the query. Use an indexed array if your query contain "?", an associative array otherwise
		 *
		 * @return bool true if query has been executed with success, false otherwise
		 */
		public function execute(array $parameters = []): bool
		{
			$this->executed = true;
			return $this->statement->execute($parameters);
		}
		
		/** Get the next record returned by the query
		 *
		 * @return array|false record / false if an error occurred
		 *
		 * @throws NotExecutedQueryException Thrown if the query has not been executed before this function
		 */
		public function getNextRecord(): array|false
		{
			if(!$this->executed) throw new NotExecutedQueryException("Query have to be executed before retrieving fields/records");
			
			return $this->statement->fetch(PDO::FETCH_ASSOC);
		}
		
		/** Get all the records returned by the query
		 *
		 * @return array|false records / false if an error occurred
		 *
		 * @throws NotExecutedQueryException Thrown if the query has not been executed before this function
		 */
		public function getAllRecords(): array|false
		{
			if(!$this->executed) throw new NotExecutedQueryException("Query have to be executed before retrieving fields/records");
			
			return $this->statement->fetchAll(PDO::FETCH_ASSOC);
		}
		
		/** Get the first field of the next record
		 *
		 * @return mixed field / false if an error occurred or if there is no other record
		 *
		 * @throws NotExecutedQueryException Thrown if the query has not been executed before this function
		 */
		public function getNextField(): mixed
		{
			if(!$this->executed) throw new NotExecutedQueryException("Query have to be executed before retrieving fields/records");
			
			return $this->statement->fetch(PDO::FETCH_COLUMN);
		}
		
		/** Get the first field of each record
		 *
		 * @return array|false fields / false if an error occurred
		 *
		 * @throws NotExecutedQueryException Thrown if the query has not been executed before this function
		 */
		public function getAllFields(): array|false
		{
			if(!$this->executed) throw new NotExecutedQueryException("Query have to be executed before retrieving fields/records");
			
			return $this->statement->fetchAll(PDO::FETCH_COLUMN);
		}
		
	}