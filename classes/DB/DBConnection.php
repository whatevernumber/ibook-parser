<?php

namespace IbookParser\DB;

class DBConnection
{
    /**
     * Creates connection to the DB
     * @return \PDO
     * @throws \Exception
     */
    public function createConnection(): \PDO
    {
        $params = parse_ini_file('database.ini');

        if ($params === false) {
            throw new \Exception("Error reading database configuration file");
        }

        $dns = $params['driver'] . ':host=' . $params['host'] . ';dbname=' .
            $params['db'] . ';user=' . $params['user'] . ';password=' . $params['password'];

        try {
            return new \PDO($dns);
        } catch (\PDOException $e) {
            die($e->getMessage());
        }
    }
}
