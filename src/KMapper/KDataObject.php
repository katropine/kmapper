<?php

/**
 * @package    KMapper - katropine
 * @author     Kristian Beres <kristian@katropine.com>
 * @copyright  Katropine (c) 2013, katropine.com
 * @since      Sep 24, 2013
 * @licence    MIT
 *
 * Copyright (c) 2013 Katropine, http://www.katropine.com/
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace KMapper;

class MySqlResult {
    
    private $_dataArray;
    
    public function __construct(array $array) {
        $this->_dataArray = $array;
    }
    /**
     * Get Data as Matrix (Asoc. Array, 2D)
     * 
     * @return array $array[$i]['id'] Data Matrix [on error, return false]
     */
    public function toArray() {
        if (!$this->error) {
            return $this->_dataArray;
        }
        return false;
    }

    /**
     * Get Data as Json
     * 
     * @return string Json.success Json.data [on error, return false] 
     */
    public function toJson() {
       
        $data = $this->_dataArray;
        $rez = (count($data) > 0) ? true : false;
        $result = array(
            'success' => $rez,
            'data' => $data
        );
        return json_encode($result);
        
    }

    /**
     * 
     * Will return first row, use for COUNT(), MAX() ..... or single row extraction [on error, return false]
     * @return array $array[0]
     */
    public function toSingleRow() {
        
        $data = $this->toArray();
        return $data[0];

    }

    /**
     * Insert a new row $array[$iterator]['nickname'] = 'Joe'
     * This will alter the ArraySet  
     * 
     * @param string $key
     * @param mixed $value
     * @param int $iterator 
     */
    public function push($key, $value, $iterator = null) {
        
        if ($this->numrows > 0) {
            $i = 0;
            if ($iterator != null) {
                foreach ($this->_dataArray as $row) {
                    if ($i == $iterator) {
                        $arrVals[$i] = array_merge($row, array($key => $value));
                    } else {
                        $arrVals[$i] = array_merge($row, array($key => null));
                    }
                }
            } else {
                foreach ($this->_dataArray as $row) {
                    $arrVals[] = array_merge($row, array($key => $value));
                }
            }
            $this->_dataArray = $arrVals;
        }
        
    }

    /**
     * Returns values with matching key
     * 
     * @param string $key
     * @return array 
     */
    public function getValuesByKey($key = null) {
        $arrVals = array();
        if ($key) {
            foreach ($this->_dataArray as $row) {
                if (array_key_exists($key, $row)) {
                    $arrVals[] = $row[$key];
                }
            }
        }
        return $arrVals;
    }

    /**
     * Replaces NULL values with empty string 
     * This will alter the ArraySet 
     * 
     */
    public function clearNullValues() {
        $i = 0;
        foreach ($this->_dataArray as $row) {
            foreach ($row as $k => $v) {
                if ($v == null) {
                    $newArray[$k] = '';
                } else {
                    $newArray[$k] = $v;
                }
            }
            $cleanArray[$i] = $newArray;
            $i++;
        }
        $this->_dataArray = $cleanArray;
    }

    /**
     * Will replace the key with new keyname, for example: data['id'] with data['replacement']
     * <p style="red">!important: this will change the Object state </p>
     * @param mixed $oldKey
     * @param mixed $newKey
     * @return array dataArray 
     */
    public function replaceKey($oldKey, $newKey) {
        $i = 0;
        foreach ($this->_dataArray as $row) {
            foreach ($row as $k => $v) {
                $key = $k;
                if ($oldKey == $k) {
                    $key = $newKey;
                }
                $arrVals[$key] = $v;
            }
            $tmp[$i] = $arrVals;
            $i++;
        }
        return $this->_dataArray = $tmp;
    }

    /**
     * Search the resoult array by KEY and VALUE
     * 
     * @param string $key
     * @param string $value
     * @return array 
     */
    public function searchByKeyValue($key, $value) {

            $ret = $this->search($this->_dataArray, $key, $value);

            if (count($ret) == 0) {
                return null;
            }
            return $ret;        
    }

    /**
     * Recursive
     *
     * @param string $key
     * @param string $value
     * @return array 
     */
    protected function search($key, $value) {
        $results = array();
        $array = $this->_dataArray;
        if (is_array($array)) {
            if (array_key_exists($key, $array)) {
                if ($array[$key] == $value) {
                    $results = $array;
                }
            }
            foreach ($array as $subarray) {
                $goDeep = $this->search($subarray, $key, $value);
                $results = array_merge($results, $goDeep);
            }
        }

        return $results;
    }

    /**
     * Sort dataArray if it is to complicated for query, like: 1AB,2AB,3AC...
     * 
     * @param string $index key name 
     * @param string $order asc|desc
     * @param boolean $natSort
     * @param boolean $caseSensitive 
     */
    public function sortResult($index, $order = 'asc', $natSort = false, $caseSensitive = false) {
        if (is_array($this->_dataArray) && count($this->_dataArray) > 0) {

            foreach (array_keys($this->_dataArray) as $key) {
                $tmp[$key] = $this->_dataArray[$key][$index];
            }
            if (!$natSort) {
                ($order == 'asc') ? asort($tmp) : arsort($tmp);
            } else {
                ($caseSensitive) ? natsort($tmp) : natcasesort($tmp);
                if ($order != 'asc') {
                    $tmp = array_reverse($tmp, true);
                }
            }
            foreach (array_keys($tmp) as $key) {
                (is_numeric($key)) ? $sorted[] = $this->_dataArray[$key] : $sorted[$key] = $this->_dataArray[$key];
            }
            $this->_dataArray = $sorted;
        }
    }
}
