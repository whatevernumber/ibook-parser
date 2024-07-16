<?php

namespace IbookParser;

trait DBTrait {

    protected \PDO $database;

    /**
     * Creates PDO instance
     * @throws \Exception
     */
    function connect(): void
    {
        $this->database = (new DBConnection())->createConnection();
    }

}