<?php
	
	namespace Reborn\Phoenix\Database;
	
	use Exception;
	use PDO;
	use Reborn\Phoenix\Database\Exception\IllegalCharacterException;
	use Reborn\Phoenix\Database\Exception\MissingPrimaryKeyIndexException;
	use Reborn\Phoenix\Database\Exception\NoPrimaryKeyException;
	use Reborn\Phoenix\Database\Exception\NotExecutedQueryException;
	
	class Database extends PDO
	{
		
		/** Establish a connection with a database (see MysqlDatabase for an easier way)
		 *
		 * @param string $dsn
		 * @param string $username
		 * @param string $password
		 */
		public function __construct(string $dsn, string $username, string $password)
		{
			parent::__construct($dsn, $username, $password);
		}
		
		/** Get the record returned by the query
		 *
		 * @param string $query      Query returning a single record
		 * @param array  $parameters array of parameter for the query
		 *
		 * @return array|false record / false if an error occurred
		 */
		public function getRecord(string $query, array $parameters = []): array|bool
		{
			$query = new Query($this, $query);
			$query->execute($parameters);
			
			try {
				$record = $query->getNextRecord();
			} catch (NotExecutedQueryException) {
				$record = false;
			}
			
			return $record;
		}
		
		/** Get all the records returned by the query
		 *
		 * @param string $query      Query returning multiple records
		 * @param array  $parameters array of parameter for the query
		 *
		 * @return array|false records / false if an error occurred
		 */
		public function getRecords(string $query, array $parameters = []): array|false
		{
			$query = new Query($this, $query);
			$query->execute($parameters);
			
			try {
				$records = $query->getAllRecords();
			} catch (NotExecutedQueryException) {
				$records = false;
			}
			
			return $records;
		}
		
		/** Get the first field of the record returned by the query
		 *
		 * @param string $query      Query returning a single record
		 * @param array  $parameters array of parameter for the query
		 *
		 * @return mixed field / false if an error occurred
		 */
		public function getField(string $query, array $parameters = []): mixed
		{
			$query = new Query($this, $query);
			$query->execute($parameters);
			
			try {
				$field = $query->getNextField();
			} catch (NotExecutedQueryException) {
				$field = false;
			}
			
			return $field;
		}
		
		/** Get the first field of each record returned by the query
		 *
		 * @param string $query      Query returning multiple records
		 * @param array  $parameters array of parameter for the query
		 *
		 * @return array|false fields (the first one for each record) / false if an error occurred
		 */
		public function getFields(string $query, array $parameters = []): array|false
		{
			$query = new Query($this, $query);
			$query->execute($parameters);
			
			try {
				$fields = $query->getAllFields();
			} catch (NotExecutedQueryException) {
				$fields = false;
			}
			
			return $fields;
		}
		
		/** Insert records in SQL table
		 *
		 * @param string $table   SQL table in which insert records
		 * @param array  $records can be a single record or a records array (in that case, each record must have the exactly same structure)
		 *
		 * @return array|int array of inserted ids in case of records array or inserted id in case of single record
		 *
		 * @throws IllegalCharacterException Thrown if an unauthorized character has been used in a table/field name
		 * @throws Exception
		 */
		public function insert(string $table, array $records): array|int
		{
			// "`" is the only character that can provoke injection
			if (str_contains($table, "`")) {
				throw new IllegalCharacterException("Not allowed character in table name: `");
			}
			
			// If user pass a single record we transform it to records array
			$records = is_array($records[0] ?? null) ? $records : [$records];
			
			$escapedFields = [];
			$placeholderValues = [];
			
			foreach (array_keys($records[0]) as $field) {
				// "`" is the only character that can provoke injection
				if (str_contains($field, "`")) {
					throw new IllegalCharacterException("Not allowed character in field name: `");
				}
				
				// Escape $field to prevent injection
				$escapedFields[] = "`$field`";
				$placeholderValues[] = "?";
			}
			
			$fieldsClause = implode(", ", $escapedFields);
			$valuesClause = implode(", ", $placeholderValues);
			
			// Escape $table to prevent injection
			$query = new Query($this, "INSERT INTO `$table` ($fieldsClause) VALUES ($valuesClause)");
			
			$insertIds = [];
			
			// We go through a transaction to ensure the consistency of the whole insert
			$this->beginTransaction();
			
			try {
				// Execute the prepared statement for each record and get inserted id
				foreach ($records as $record) {
					$query->execute(array_values($record));
					$insertIds[] = $this->lastInsertId();
				}
				
				// Everything's OK, we commit
				$this->commit();
			} catch (Exception $exception) {
				// An error (no matter what) occurred, we roll back
				$this->rollBack();
				throw $exception;
			}
			
			return (count($records) > 1) ? $insertIds : $insertIds[0];
		}
		
		/** Update records in SQL table
		 *
		 * @param string $table   SQL table in which update records
		 * @param array  $records can be a single record or a records array (in that case, each record must have the exactly same structure)
		 *
		 * @return bool true if queries succeeded, false otherwise
		 *
		 * @throws IllegalCharacterException Thrown if an unauthorized character has been used in a table/field name
		 * @throws NoPrimaryKeyException Thrown if no primary key has been found
		 * @throws MissingPrimaryKeyIndexException Thrown if the primary key is missing in a record
		 * @throws Exception
		 */
		public function update(string $table, array $records): bool
		{
			// "`" is the only character that can provoke injection
			if (str_contains($table, "`")) {
				throw new IllegalCharacterException("Not allowed character in table name: `");
			}
			
			// If user pass a single record we transform it to records array
			$records = array_is_list($records) ? $records : [$records];
			
			// Retrieve the primary key of $table
			$primaryKey = self::getRecord("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'")['Column_name'] ?? null;
			if (is_null($primaryKey)) {
				throw new NoPrimaryKeyException("No primary key in $table");
			}
			
			$setClausePart = [];
			
			foreach (array_keys($records[0]) as $field) {
				if ($field !== $primaryKey) {
					// "`" is the only character that can provoke injection
					if (str_contains($field, "`")) {
						throw new IllegalCharacterException("Not allowed character in field name: `");
					}
					
					// Escape $field to prevent injection
					$setClausePart[] = "`$field` = :param_" . count($setClausePart);
				}
			}
			
			$setClause = implode(", ", $setClausePart);
			
			// Escape $table to prevent injection
			$query = new Query($this, "UPDATE `$table` SET $setClause WHERE $primaryKey = :$primaryKey");
			
			$result = true;
			
			// We go through a transaction to ensure the consistency of the whole update
			$this->beginTransaction();
			
			try {
				// Execute the prepared statement for each record
				foreach ($records as $recordIndex => $record) {
					if (!isset($record[$primaryKey])) {
						throw new MissingPrimaryKeyIndexException("Missing key \"$primaryKey\" in records at index $recordIndex");
					}
					
					$parameters = [];
					
					$id = $record[$primaryKey];
					unset($record[$primaryKey]);
					
					foreach (array_values($record) as $fieldIndex => $fieldValue) {
						$parameters["param_$fieldIndex"] = $fieldValue;
					}
					
					$parameters[$primaryKey] = $id;
					
					// The final result is OK only if all queries went right
					$result &= $query->execute($parameters);
				}
				
				// Everything's OK, we commit
				$this->commit();
			} catch (Exception $exception) {
				// An error (no matter what) occurred, we roll back
				$this->rollBack();
				throw $exception;
			}
			
			return $result;
		}
		
		/** Delete records from SQL table
		 *
		 * @param string $table SQL table from which delete records
		 * @param array  $ids   ids (primary keys) to delete
		 *
		 * @return bool true if queries succeeded, false otherwise
		 *
		 * @throws IllegalCharacterException Thrown if an unauthorized character has been used in a table/field name
		 * @throws NoPrimaryKeyException Thrown if no primary key has been found
		 */
		public function delete(string $table, array $ids): bool
		{
			// "`" is the only character that can provoke injection
			if (str_contains($table, "`")) {
				throw new IllegalCharacterException("Not allowed character in table name: `");
			}
			
			// Retrieve the primary key of $table
			$primaryKey = self::getRecord("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'")['Column_name'] ?? null;
			$placeholder = implode(", ", array_fill(0, count($ids), "?"));
			
			if (is_null($primaryKey)) {
				throw new NoPrimaryKeyException("No primary key in $table");
			}
			
			$query = "DELETE FROM `$table` WHERE $primaryKey IN ($placeholder)";
			
			return (new Query($this, $query))->execute($ids);
		}
		
	}