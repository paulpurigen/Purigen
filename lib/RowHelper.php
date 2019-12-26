<?php
class RowHelper implements ArrayAccess
{
    //Data Base Row information Class
    private $data = [];

    public function &__get ($key)
    {
        return $this->data[$key];
    }

    public function __set($key,$value)
    {
        $this->data[$key] = $value;
    }

    public function __isset ($key)
    {
        return isset($this->data[$key]);
    }

    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset))
        {
            $this->data[] = $value;
        } else
        {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset))
        {
            unset($this->data[$offset]);
        }
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->data[$offset] : null;
    }
}
?>
