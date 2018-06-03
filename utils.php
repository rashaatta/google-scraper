<?php

class Database
{

    public function getMySQLConnection($dbname = null)
    {
        //        $ini = parse_ini_file('/data/webapp.ini');
        //        $ini = parse_ini_file('webapp.ini');
        // $ini = parse_ini_file(Config::get('dbPath'));
        $ini = array(
            'dbhost' => '127.0.0.1',
            'dbname' => 'id6051643_gscraper',
            'dbuser' => 'devuser',
            'dbpass' => 'P@ssw0rd',
        );
        if ($dbname === null) {
            $dbname = $ini['dbname'];
        }
        try {
            $dbh = new PDO("mysql:host=" . $ini['dbhost'] . ";dbname=$dbname;charset=utf8", $ini['dbuser'], $ini['dbpass'], array(PDO::MYSQL_ATTR_LOCAL_INFILE => true));
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);
            return $dbh;
        } catch (PDOException $ex) {
            throw new Exception($ex->getMessage());
        }
    }

}

class MyError
{

    public $file;
    public $line;
    public $message;

}

class ErrorMessage
{

    public function GetError($file, $line, $message)
    {
        $error = new MyError();
        $error->line = $line;
        $error->file = $file;
        $error->message = $message;
        return json_encode($error);
    }

}
