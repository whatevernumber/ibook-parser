<?php

namespace IbookParser\DB;

trait DBTrait {

    protected \PDO $database;

    /**
     * Creates PDO instance
     * @throws \Exception
     */
    protected function connect(): void
    {
        $this->database = (new DBConnection())->createConnection();
    }

}