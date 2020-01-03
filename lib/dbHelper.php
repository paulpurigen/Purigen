<?php
class dbHelper
{
    private $dbCon;

    private function throwException($query = null)
    {
        $msg = mysql_error().". Query :".$query." Error number: ".mysql_errno();
        throw new Exception($msg);
    }

    public function dbOpen()
    {
        try
        {
            $this->dbCon = mysqli_connect(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')) or die($this->throwException("dbOpen"));
            mysqli_select_db($this->dbCon, getenv('DB_NAME'));
        } catch (Exception $ex) {
            throw new Exception( 'db Open Error' );
        }
    }

    public function dbClose()
    {
        try
        {
            if( !mysqli_close($this->dbCon) )
               $this->throwException("dbClose");
        } catch (Exception $ex) {
            throw new Exception( 'db Close Error' );
        }
    }

    public function RunSQLReturnRows($sql)
    {
        $rowList = array();
        try {
            $result = mysqli_query($this->dbCon, $sql);

            if (!$result)
            {
                $this->throwException($sql);
            }
            $rowcnt = 0;

            while ($row = $result->fetch_assoc())
            {
                $rowList[$rowcnt++] = $row;
            }
            $result->free();
        } catch (Exception $ex) {
            throw new Exception( 'RunSQLReturnRows Query Error : '.$ex->getMessage() );
        }

        return $rowList;
    }

    public function RunSQLReturnOneRow($sql)
    {
        $rowList = null;
        try {
            $result = mysqli_query($this->dbCon, $sql);
            if (!$result)
            {
                $this->throwException($sql);
            }

            $rowList = $result->fetch_assoc();
            
            $result->free();
        } catch (Exception $ex) {
                throw new Exception( 'RunSQLReturnOneRow Query Error'.$ex->getMessage() );
        }

        return $rowList;
    }

    public function RunSQL($sql)
    {
        try {
            $result = mysqli_query($this->dbCon, $sql);
            if (!$result)
            {
                $this->throwException($sql);
            }
        } catch (Exception $ex) {
            throw new Exception( 'RunSQL Query Error'.$ex->getMessage() );
        }
    }

    public function RunSQLReturnRowsSub($sql, $RequestPage, $PageSize, &$TotalCount)
    {
        $limit = ($RequestPage-1) * $PageSize;

        $sql = preg_replace('/select/i', 'select SQL_CALC_FOUND_ROWS ', $sql, 1);

        $sql = $sql." limit ".$limit.", ".$PageSize;
        $rowList = array();
        try {
            $result = mysqli_query($this->dbCon, $sql);
            if (!$result)
            {
                $this->throwException($sql);
            }
            
            $rowcnt = 0;
            while ($row = $result->fetch_assoc())
            {
                $rowList[$rowcnt++] = $row;
            }
            $result->free();

            //Get Total Record Count
            $countList = mysqli_query($this->dbCon, "select FOUND_ROWS() as cnt;");
            $countRow = $countList->fetch_assoc();
            $TotalCount = $countRow["cnt"];
            
            $countList->free();
        } catch (Exception $ex) {
            throw new Exception( 'RunSQLReturnRowsSub Query Error'.$ex->getMessage() );
        }

        return $rowList;
    }
    
    public function RunSQLReturnID($sql)
    {
        $rowList = null;
        
        try {
            $result = mysqli_query($this->dbCon, $sql);
            if (!$result)
            {
                $this->throwException($sql);
            }
            
            $sql = "select LAST_INSERT_ID() as pkid";
            $result = mysqli_query($this->dbCon, $sql);
            if (!$result)
            {
                    $this->throwException($sql);
            }

            $rowList = $result->fetch_assoc();
            $result->free();
        } catch (Exception $ex) {
            throw new Exception( 'RunSQLReturnID Query Error'.$ex->getMessage() );
        }
        
        return $rowList["pkid"];
    }
}
?>
