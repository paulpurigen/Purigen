<?php
//Class for Pagging Information
class PageHelper
{
    public $RecordCount;
    public $PageCount;
    public $prev;
    public $next;
    public $PreTen;
    public $NextTen;
    public $PageRtn;

    public function PageHelper()
    {
        $this->RecordCount = 0;
        $this->PageCount = 0;
        $this->PageRtn = array();
    }
}
?>
