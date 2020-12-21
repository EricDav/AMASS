<?php
    namespace AMASS\Models;

    define('FIND_ONE', 1);
    define('FIND', 2);
    class Model {
        const DISALLOWED_LAST_ID_TABLES = array('pin_history');
        public static function createAll($dbConnection, $params, $tableName) {
            try {
                $dbConnection->pdo->beginTransaction();
                foreach($params as $param) {
                    $clauseData = self::generateInsertClause($param);
                    $sql = 'INSERT INTO ' . $tableName . ' ' . $clauseData['attributes']  . ' VALUES (' . self::getValues($clauseData['params']) . ')';
                    $dbConnection->pdo->exec($sql);
                }
                $dbConnection->pdo->commit();
                return true;
            } catch (Exception $e) {
                var_dump($e->getMessage());
                $dbConnection->pdo->rollBack();
                return false;
            }
        }

        public static function getValues($params) {
            $values = '';
            for($i = 0; $i < sizeof($params); $i++) {
              if ($i < sizeof($params) - 1) {
                $values.=(is_int($params[$i]) ? '' : "'") . $params[$i] . (is_int($params[$i]) ? '' : "'") . ',';
              } else {
                $values.=(is_int($params[$i]) ? '' : "'") . $params[$i] . (is_int($params[$i]) ? '' : "'");
              }
            }
        
            return $values;
          }

        public static function create($dbConnection, $params, $tableName) {
            try {
                $clauseData = self::generateInsertClause($params);
                $sql = 'INSERT INTO ' . $tableName . ' ' . $clauseData['attributes']  . ' VALUES' . $clauseData['values'];

                var_dump($sql); exit;
                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute($clauseData['params']);
                if(in_array($tableName, self::DISALLOWED_LAST_ID_TABLES)) {
                    return true;
                }
                return $dbConnection->pdo->lastInsertId();
            } catch(Exception $e) {
                var_dump($e->getMessage()); exit;
                return false;
            }
        }

        public static function update($dbConnection, $updateParams, $whereParams, $tableName) {
            try {
                $clauseData = self::generateInsertClause($updateParams);
                $sql = 'UPDATE ' . $tableName . ' SET';
                for($i = 0; $i < sizeof($clauseData['attrData']); $i++) {
                    $attribute = $clauseData['attrData'][$i];
                    $value = $clauseData['params'][$i];
                    if (!is_numeric($value) || $attribute == 'phone_number') {
                        $value = '"' . $value . '"';
                    }

                    if ($i == sizeof($clauseData['attrData']) - 1) {
                        $sql = $sql . ' ' . $attribute . ' = ' . $value;
                    } else {
                        $sql = $sql . ' ' . $attribute . ' = ' . $value . ',';
                    }
                }

                $whereClause = self::generateWhereClause($tableName, $whereParams, null, null, null, null);
                $sql .= ' WHERE' . $whereClause['clause'];

                $stmt= $dbConnection->pdo->prepare($sql);
                return $stmt->execute($whereClause['params']);

            } catch (Exception $e) {
                return null;
            }
        }

        public static function generateInsertClause($params) {
            $paramsVal = [];
            $attrData = [];
            $attributes = '(';
            $values= '(';
            $counter = 0;
            $sizeofParam = sizeof($params);

            foreach($params as $prop => $val) {
                array_push($attrData, $prop);
                if ($counter != $sizeofParam - 1) {
                    $attributes .= $prop . ', ';
                    $values .= '?,';
                } else {
                    $attributes .= $prop . ')';
                    $values .= '?)';
                }

                $counter +=1;

                array_push($paramsVal, $val);
            }

            return array(
                'attributes' => $attributes,
                'params' => $paramsVal,
                'values' => $values,
                'attrData' => $attrData
            );
        }

        public static function findOne($dbConnection, $params, $tableName, $offset=null, $limit=null, $order=null, $orderBy=null) {
            return self::__find($dbConnection, $params, $tableName, FIND_ONE, $offset, $limit, $order, $orderBy);
        }

        public static function find($dbConnection, $params, $tableName, $offset=null, $limit=null, $order=null, $orderBy=null) {
            return self::__find($dbConnection, $params, $tableName, FIND, $offset, $limit, $order, $orderBy);
        }

        public static function __find($dbConnection, $params, $tableName, $callee, $offset, $limit, $order, $orderBy) {
            try {

                if (!is_array($params)) {
                    throw new \Exception('Invalid paramters parameter should be an array');
                }
                // Generate where clause

                $whereClause = self::generateWhereClause($tableName, $params, $offset, $limit, $order, $orderBy);                
                $sql = 'SELECT * FROM ' . $tableName . (!$params ? '' : ' WHERE' . $whereClause['clause']);

                $stmt= $dbConnection->pdo->prepare($sql);
                $stmt->execute($whereClause['params']);
                
                if ($callee == FIND) {
                    return $stmt->fetchAll();
                }

               // var_dump($stmt->fetch()); exit;

                return $stmt->fetch();
                
            } catch(Exception $e) {
                echo $e->getMessage();
                return 'Server error';
            }
        }

        /**
         * It generates a where claus for the find function 
         * 
         * @param $tableName the name of the table we want to find rows e.g users
         * @param $findBy the attributes we want to filter from e.g id, email
         */
        public static function generateWhereClause($tableName, $findBy, $offset, $limit, $order, $orderBy) {
            $whereClause = '';
            $paramsVal = [];
            $counter = 0;

            foreach($findBy as $prop => $val) {
                if ($counter == 0) {
                    $whereClause .=' ' . $tableName . '.' . $prop . '=?';
                } else {
                    $whereClause.= ' AND ' . $tableName . '.' . $prop . '=?';
                }

                array_push($paramsVal, $val);
                $counter+=1;
            }

            // Add order by
            if ($order) {
                $whereClause .= ' ORDER BY ' . $tableName . '.' . $order . ' ' . $orderBy;
            }

            // Add limit
            if ($limit) {
                $whereClause .= ' LIMIT ' . $limit;
            }

                        
            // Add offset
            if ($offset) {
                $whereClause .= ' OFFSET ' . $offset;
            }

            return array(
                'clause' => $whereClause,
                'params' => $paramsVal
            );
        }
    }


?>