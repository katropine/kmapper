<?php
namespace KMapper\Tool;
/**
 * @package    KMapper - katropine
 * @author     Kristian Beres <kristian@katropine.com>
 * @copyright  Katropine (c) 2013, katropine.com
 * @since      Sep 24, 2015
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

class Matrix {
    
    /**
     * 
     * @param array $m1 hayStack array([],[])
     * @param array $m2 newData array([],[])
     * @return array
     */
    public static function findDiff($m1, $m2) {
        $newArray = [];
        foreach ($m1 as $key => $val) {
            $hash = md5(serialize(json_encode($val, JSON_NUMERIC_CHECK)));
            $newArray[$hash] = $val;
        }

        $diff = [];
        foreach ($m2 as $key => $val) {
            $hash2 = md5(serialize(json_encode($val, JSON_NUMERIC_CHECK)));
            if (!isset($newArray[$hash2])) {
                $diff[] = $val;
            }
        }
        return $diff;
    }


    /**
     * 
     * @param array $m1 hayStack array([],[])
     * @param array $m2 newData array([],[])
     * @param array $indexName string
     * @return array
     */
    public static function findNew($m1, $m2, $indexName) {
        $newArray = [];
        foreach ($m1 as $key => $val) {
            $newArray[$indexName."-".$val[$indexName]] = $val;
        }

        $diff = [];
        foreach ($m2 as $key => $val) {
            if (!isset($newArray[$indexName."-".$val[$indexName]])) {
                $diff[] = $val;
            }
        }
        return $diff;
    }

    /**
     * 
     * @param array $m1 hayStack array([],[]) 
     * @param array $m2 newData array([],[])
     * @param string $indexName 
     * @param array $copyFieldsFromM1ToDiff copy key-value pare from hayStack to newData
     * @return array
     */
    public static function findChanged($m1, $m2, $indexName, $copyFieldsFromM1ToDiff = []) {
        $newArray = [];
        foreach ($m1 as $key => $val) {
            $hash = md5(serialize(json_encode($val, JSON_NUMERIC_CHECK)));
            $newArray[$indexName."-".$val[$indexName]]['hash'] = $hash;
            $newArray[$indexName."-".$val[$indexName]]['value'] = $val;
        }

        $diff = [];
        foreach ($m2 as $key => $val) {
            $hash2 = md5(serialize(json_encode($val, JSON_NUMERIC_CHECK)));
            if (isset($newArray[$indexName."-".$val[$indexName]]) && $newArray[$indexName."-".$val[$indexName]]['hash'] != $hash2) {
                if(count($copyFieldsFromM1ToDiff) > 0){
                    foreach($copyFieldsFromM1ToDiff as $k => $keyName){
                        $tmp = $newArray[$indexName."-".$val[$indexName]]['value'];
                        if(array_key_exists($keyName, $tmp)){
                            $val[$keyName] = $tmp[$keyName];
                        }
                    }
                }
                $diff[] = $val;
            }
        }
        return $diff;
    }
    
   