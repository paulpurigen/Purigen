<?php

    $common_dir = "$include_dir/common/";   //subfolder of .../include/ where all the common files are stored

    //  Returns the result of an SQL query as an array
    function sqli_fetch_all($query) {
        global $db_con, $db_con, $debug;

        $data = array();
        $result = $db_con->query($query);
        if ($debug > 0 && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-4;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        } else {
            if ($result->num_rows) {
                while($row = $result->fetch_array(MYSQLI_BOTH)) {
                    $data[]=$row;
                }
            }
        }
        return $data;
    }

    //  Removes duplicate elements from an array
    function distinct_array($arr) {
        rsort($arr);
        reset($arr);
        $newarr = array();
        $i = 0;
        $element = current($arr);

        for ($n = 0; $n < sizeof($arr); $n++) {
            if (next($arr) != $element) {
                $newarr[$i] = $element;
                $element = current($arr);
                $i++;
            }
        }

        return $newarr;
    }

    function get_cats($parent) {
        global $db_con, $db_con, $mysql_table_prefix, $debug;

        $sql_query = "SELECT * FROM ".$mysql_table_prefix."categories WHERE parent_num=$parent";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        $arr[] = $parent;
        if ($result->num_rows) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['category_id'];
                $arr = add_arrays($arr, get_cats($id));
            }
        }
        return $arr;
    }

    function add_arrays($arr1, $arr2) {
        foreach ($arr2 as $elem) {
            $arr1[] = $elem;
        }
        return $arr1;
    }

    function parse_all_url($url){   //  this will parse also IDN coded URLs, independent from local server configuration
        $url_parts = array();
        preg_match("@^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?@", $url, $regs);

        if ($regs[2]) $url_parts['scheme']    = $regs[2];
        if ($regs[4]) $url_parts['host']      = $regs[4];
        if ($regs[5]) $url_parts['path']      = $regs[5];
        if ($regs[7]) $url_parts['query']     = $regs[7];
        if ($regs[9]) $url_parts['fragment']  = $regs[9];

        return $url_parts;
    }

    class Segmentation {
        var $options = array('lowercase' => TRUE);
        var $dict_name = 'Unknown';
        var $dict_words = array();

        function setLowercase($value) {
            if ($value) {
                $this->options['lowercase'] = TRUE;
            } else {
                $this->options['lowercase'] = FALSE;
            }
            return TRUE;
        }

        function load($dict_file) {
            if (!file_exists($dict_file)) {
                return FALSE;
            }
            $fp = fopen($dict_file, 'r');
            $temp = fgets($fp, 1024);
            if ($temp === FALSE) {
                return FALSE;
            } else {
                if (strpos($temp, "\t") !== FALSE) {
                    list ($dict_type, $dict_name) = explode("\t", trim($temp));
                } else {
                    $dict_type = trim($temp);
                    $dict_name = 'Unknown';
                }
                $this->dict_name = $dict_name;
                if ($dict_type !== 'DICT_WORD_W') {
                    return FALSE;
                }
            }
            while (!feof($fp)) {
                $this->dict_words[rtrim(fgets($fp, 32))] = 1;
            }
            fclose($fp);
            return TRUE;
        }

        function getDictName() {
            return $this->dict_name;
        }

        function segmentString($str) {
            if (count($this->dict_words) === 0) {
                return FALSE;
            }
            $lines = explode("\n", $str);
            return $this->_segmentLines($lines);
        }

        function segmentFile($filename) {
            if (count($this->dict_words) === 0) {
                return FALSE;
            }
            $lines = file($filename);
            return $this->_segmentLines($lines);
        }

        function _segmentLines($lines) {
            $contents_segmented = '';
            foreach ($lines as $line) {
                $contents_segmented .= $this->_segmentLine(rtrim($line)) . " \n";
            }
            do {
                $contents_segmented = str_replace('  ', ' ', $contents_segmented);
            } while (strpos($contents_segmented, '  ') !== FALSE);
            return $contents_segmented;
        }

        function _segmentLine($str) {
            $str_final = '';
            $str_array = array();
            $str_length = strlen($str);
            if ($str_length > 0) {
                if (ord($str{$str_length-1}) >= 129) {
                    $str .= ' ';
                }
            }
            for ($i=0; $i<$str_length; $i++) {
                if (ord($str{$i}) >= 129) {
                    $str_array[] = $str{$i} . $str{$i+1};
                    $i++;
                } else {
                    $str_tmp = $str{$i};
                    for ($j=$i+1; $j<$str_length; $j++) {
                        if (ord($str{$j}) < 129) {
                            $str_tmp .= $str{$j};
                        } else {
                            break;
                        }
                    }
                    $str_array[] = array($str_tmp);
                    $i = $j - 1;
                }
            }
            $pos = count($str_array);
            while ($pos > 0) {
                $char = $str_array[$pos-1];
                if (is_array($char)) {
                    $str_final_tmp = $char[0];

                    if ($this->options['lowercase']) {
                        $str_final_tmp = strtolower($str_final_tmp);
                    }
                    $str_final = " $str_final_tmp$str_final";
                    $pos--;
                } else {
                    $word_found = 0;
                    $word_array = array(0 => '');
                    if ($pos < 4) {
                        $word_temp = $pos + 1;
                    } else {
                        $word_temp = 5;
                    }
                    for ($i=1; $i<$word_temp; $i++) {
                        $word_array[$i] = $str_array[$pos-$i] . $word_array[$i-1];
                    }
                    for ($i=($word_temp-1); $i>1; $i--) {
                        if (array_key_exists($word_array[$i], $this->dict_words)) {
                            $word_found = $i;
                            break;
                        }
                    }
                    if ($word_found) {
                        $str_final = " $word_array[$word_found]$str_final";
                        $pos = $pos - $word_found;
                    } else {
                        $str_final = " $char$str_final";
                        $pos--;
                    }
                }
            }
            return $str_final;
        }
    }

    $entities = array(
        "&amp" => "&",
        "&apos" => "'",
        "&THORN;"  => "Ãž",
        "&szlig;"  => "ÃŸ",
        "&agrave;" => "Ã ",
        "&aacute;" => "Ã¡",
        "&acirc;"  => "Ã¢",
        "&atilde;" => "Ã£",
        "&auml;"   => "Ã¤",
        "&aring;"  => "Ã¥",
        "&aelig;"  => "Ã¦",
        "&ccedil;" => "Ã§",
        "&egrave;" => "Ã¨",
        "&eacute;" => "Ã©",
        "&ecirc;"  => "Ãª",
        "&euml;"   => "Ã«",
        "&igrave;" => "Ã¬",
        "&iacute;" => "Ã­",
        "&icirc;"  => "Ã®",
        "&iuml;"   => "Ã¯",
        "&eth;"    => "Ã°",
        "&ntilde;" => "Ã±",
        "&ograve;" => "Ã²",
        "&oacute;" => "Ã³",
        "&ocirc;"  => "Ã´",
        "&otilde;" => "Ãµ",
        "&ouml;"   => "Ã¶",
        "&oslash;" => "Ã¸",
        "&ugrave;" => "Ã¹",
        "&uacute;" => "Ãº",
        "&ucirc;"  => "Ã»",
        "&uuml;"   => "Ã¼",
        "&yacute;" => "Ã½",
        "&thorn;"  => "Ã¾",
        "&yuml;"   => "Ã¿",
        "&THORN;"  => "Ãž",
        "&szlig;"  => "ÃŸ",
        "&Agrave;" => "Ã ",
        "&Aacute;" => "Ã¡",
        "&Acirc;"  => "Ã¢",
        "&Atilde;" => "Ã£",
        "&Auml;"   => "Ã¤",
        "&Aring;"  => "Ã¥",
        "&Aelig;"  => "Ã¦",
        "&Ccedil;" => "Ã§",
        "&Egrave;" => "Ã¨",
        "&Eacute;" => "Ã©",
        "&Ecirc;"  => "Ãª",
        "&Euml;"   => "Ã«",
        "&Igrave;" => "Ã¬",
        "&Iacute;" => "Ã­",
        "&Icirc;"  => "Ã®",
        "&Iuml;"   => "Ã¯",
        "&ETH;"    => "Ã°",
        "&Ntilde;" => "Ã±",
        "&Ograve;" => "Ã²",
        "&Oacute;" => "Ã³",
        "&Ocirc;"  => "Ã´",
        "&Otilde;" => "Ãµ",
        "&Ouml;"   => "Ã¶",
        "&Oslash;" => "Ã¸",
        "&Ugrave;" => "Ã¹",
        "&Uacute;" => "Ãº",
        "&Ucirc;"  => "Ã»",
        "&Uuml;"   => "Ã¼",
        "&Yacute;" => "Ã½",
        "&Yhorn;"  => "Ã¾",
        "&Yuml;"   => "Ã¿"
	);

    //Apache multi indexes parameters
    $apache_indexes = array (
        "N=A" => 1,
        "N=D" => 1,
        "M=A" => 1,
        "M=D" => 1,
        "S=A" => 1,
        "S=D" => 1,
        "D=A" => 1,
        "D=D" => 1,
        "C=N;O=A" => 1,
        "C=M;O=A" => 1,
        "C=S;O=A" => 1,
        "C=D;O=A" => 1,
        "C=N;O=D" => 1,
        "C=M;O=D" => 1,
        "C=S;O=D" => 1,
        "C=D;O=D" => 1
    );

    //  Extract of ligatures in Unicode (Latin-derived alphabets).  Not supporting medieval ligatures
    $latin_ligatures = array (
        "AE"    => "&#198;",
        "ae"    => "&#230;",
        "OE"    => "&#338;",
        "oe"    => "&#339;",
        "IJ"    => "&#306;",
        "ij"    => "&#307;",
        "ue"    => "&#7531;",   //phonetic, small only
        "TZ"    => "&#42792;",
        "tz"    => "&#42793;",
        "AA"    => "&#42802;",
        "aa"    => "&#42803;;",
        "AO"    => "&#42804;",
        "ao"    => "&#42805;",
        "AU"    => "&#42806;",
        "au"    => "&#42807;",
        "AV"    => "&#42808;",
        "av"    => "&#42809;",
        "AY"    => "&#42812;",
        "ay"    => "&#42813;",
        "OO"    => "&#42830;",
        "oo"    => "&#42831;",
        "et"    => "&amp;",     //  &
        "ss"    => "&#223;",    //  German ß
        "f‌f"    => "&#64256;",
        "f‌i"    => "&#64257;",
        "f‌l"    => "&#64258;",
        "f‌f‌i"   => "&#64259;",
        "f‌f‌l"   => "&#64260;"
        //"ſt"    => "&#64261;",
        //"st"    => "&#64262;",
        //"ſs"    => "&#223;",
        //"ſz"    => "&#223;"
    );

    //  Ligatures used only in phonetic transcription
    $phon_trans = array (
        "db"    => "&#568;",
        "op"    => "&#569;",
        "cp"    => "&#569;",
        "lʒ"    => "&#622;",
        "lezh"  => "&#622;",
        "dz"    => "&#675;",
        "dʒ"    => "&#676;",
        "dezh"  => "&#676;",
        "dʑ"    => "&#677;",
        "ts"    => "&#678;",
        "tʃ"    => "&#679;",
        "tesh"  => "&#679;",
        "tɕ"    => "&#680;",
        "fŋ"    => "&#681;",
        "ls"    => "&#682;",
        "lz"    => "&#683;"
    );

    function remove_accents($string, $wild) {
            $acc    = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
            $vow    = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
            $string = str_replace($acc, $vow, $string);
/*
            //  now some special accents, to be met in UTF-8
			//	might cause conflicts with Polish language.
            $string = (strtr($string, "Ã€Ã?Ã‚ÃƒÃ„Ã…Ã†Ã Ã¡Ã¢Ã£Ã¤Ã¥Ã¦Ã’Ã“Ã”Ã•Ã•Ã–Ã˜Ã²Ã³Ã´ÃµÃ¶Ã¸ÃˆÃ‰ÃŠÃ‹Ã¨Ã©ÃªÃ«Ã°Ã‡Ã§Ã?ÃŒÃ?ÃŽÃ?Ã¬Ã­Ã®Ã¯Ã™ÃšÃ›ÃœÃ¹ÃºÃ»Ã¼Ã‘Ã±ÃžÃŸÃ¿Ã½",
                      "aaaaaaaaaaaaaaoooooooooooooeeeeeeeeecceiiiiiiiiuuuuuuuunntsyy"));

*/
        if ($type == "tol" && !$wild){    //  make tolerant by replacing vowels with %
            $string = rep_latvowels($string);
        }
        return ($string);
    }

    //  convert ISO-8859-x entities into their lower case equivalents
    function lower_ent($string) {
        $ent = array
        (
        "Č" => "č",
        "Ď" => "ď",
        "Ě" => "ě",
        "Ľ" => "ľ",
        "Ň" => "ň",
        "Ř" => "ř",
        "Š" => "š",
        "Ť" => "ť",
        "Ž" => "ž",

        "Ä" => "ä",
        "Ö" => "ö",
        "Ü" => "ü",
        "&Auml;" => "ä",
        "&#196;" => "ä",
        "&Ouml;" => "ö",
        "&#214;" => "ö",
        "&Uuml;" => "ü",
        "&#220;" => "ü",

        "À" => "à",
        "È" => "è",
        "Ì" => "ì",
        "Ò" => "ò",
        "Ù" => "ù",

        "É" => "é",
        "Í" => "í",
        "Ó" => "ó",
        "Ú" => "ú",

        "Ã" => "ã",
        "Ñ" => "ñ",
        "Õ" => "õ",
        "Ũ" => "ũ",

        "Â" => "â",
        "Ê" => "ê",
        "Î" => "î",
        "Ô" => "ô",
        "Û" => "û",

        "Å" => "å",
        "Ů" => "ů",

        "Æ" => "æ",
        "Ç" => "ç",
        "Ø" => "ø",
        "Ë" => "ë",
        "Ï" => "ï",

        "Ğ" => "ğ",
        //"İ" => "ı",
        "İ" => "i",
        "Ş" => "ş",

        "Ħ" => "ħ",
        "Ĥ" => "ĥ",
        "Ĵ" => "ĵ",
        "Ż" => "ż",
        "Ċ" => "ċ",
        "Ĉ" => "ĉ",
        "Ŭ" => "ŭ",
        "Ŝ" => "ŝ",
        "Ă" => "ă",
        "Ő" => "ő",
        "Ĺ" => "ĺ",
        "Ć" => "ć",
        "Ű" => "ű",
        "Ţ" => "ţ",
        "Ń" => "ń",
        "Đ" => "đ",
        "Ŕ" => "ŕ",
        "Á" => "á",
        "Ś" => "ś",
        "Ź" => "ź",
        "Ł" => "ł",
        "˘" => "˛",

        "ĸ" => "˛",
        "Ŗ" => "ŗ",

        "Į" => "į",
        "Ę" => "ę",
        "Ė" => "ė",
        "Ð" => "ð",
        "Ņ" => "ņ",
        "Ō" => "ō",
        "Ų" => "ų",
        "Ý" => "ý",
        "Þ" => "þ",
        "Ą" => "ą",
        "Ē" => "ē",
        "Ģ" => "ģ",
        "Ī" => "ī",
        "Ĩ" => "ĩ",
        "Ķ" => "ķ",
        "Ļ" => "ļ",
        "Ŧ" => "ŧ",
        "Ū" => "ū",
        "Ŋ" => "ŋ",

        "Ā" => "ā",

        "Ḃ" => "ḃ",
        "Ḋ" => "ḋ",
        "Ẁ" => "ẁ",
        "Ẃ" => "ẃ",
        "Ṡ" => "ṡ",
        "Ḟ" => "ḟ",
        "Ṁ" => "ṁ",
        "Ṗ" => "ṗ",
        "Ẅ" => "ẅ",
        "Ŵ" => "ŵ",
        "Ṫ" => "ṫ",
        "Ŷ" => "ŷ"

        );

		foreach ($ent as $key => $value){
			$string = preg_replace("/".$key."/i", "$value", $string);
		}

        return ($string);
    }

    //  convert characters into lower case
    function lower_case($string) {
        global $charSet, $home_charset, $greek, $cyrillic, $liga;

        if ($charSet =='') {
            $charSet = $home_charset;
        }
        $charSet = strtoupper($charSet);

        //      if required, convert Greek charset into lower case
        if ($greek == '1') {

            $lower = array
            (
            "Α" => "α",
            "Β" => "β",
            "Γ" => "γ",
            "Δ" => "δ",
            "Ε" => "ε",
            "Ζ" => "ζ",
            "Η" => "η",
            "Θ" => "θ",
            "Ι" => "ι",
            "Κ" => "κ",
            "Λ" => "λ",
            "Μ" => "μ",
            "Ν" => "ν",
            "Ξ" => "ξ",
            "Ο" => "ο",
            "Π" => "π",
            "Ρ" => "ρ",
            "Σ" => "σ",
            "Τ" => "τ",
            "Υ" => "υ",
            "Φ" => "φ",
            "Χ" => "χ",
            "Ψ" => "ψ",
            "Ω" => "ω"
            );

			foreach ($lower as $key => $value){
				$string = preg_replace("/".$key."/i", "$value", $string);
			}
        }

        //      if required, convert Cyrillic charset into lower case
        if ($cyrillic == '1') {

            $lower = array
            (
            "А" => "а",     //      basic Cyrillic alphabet
            "Б" => "б",
            "В" => "в",
            "Г" => "г",
            "Ґ" => "ґ",
            "Ѓ" => "ѓ",
            "Д" => "д",
            "Ђ" => "ђ",
            "Е" => "е",
            "Ё" => "ё",
            "Є" => "є",
            "Ж" => "ж",
            "З" => "з",
            "Ѕ" => "ѕ",
            "И" => "и",
            "І" => "і",
            "Ї" => "ї",
            "Й" => "й",
            "Ј" => "ј",
            "К" => "к",
            "Ќ" => "ќ",
            "Л" => "л",
            "Љ" => "љ",
            "М" => "м",
            "Н" => "н",

            "Њ" => "њ",
            "О" => "о",
            "П" => "п",
            "Р" => "р",
            "С" => "с",
            "Т" => "т",
            "Ћ" => "ћ",
            "У" => "у",
            "Ў" => "ў",
            "Ф" => "ф",
            "Х" => "х",
    "Ѡ" => "ѡ",          //     ex Greek 'OMEGA'
            "Ц" => "ц",
            "Ч" => "ч",
            "Џ" => "џ",
            "Ш" => "ш",
            "Щ" => "щ",
            "Ъ" => "ъ",
            "Ы" => "ы",
            "Ь" => "ь",
            "Ы" => "ы",
            "Э" => "э",
            "Ю" => "ю",
            "Я" => "я",

    "Ѐ" => "ѐ",
    "Ђ" => "ђ",
    "Ї" => "ї",
    "Ѝ" => "ѝ",

    "Ѥ" => "ѥ",         //      extended Cyrillic
    "Ѧ" => "ѧ",
            "Ѫ" => "ѫ",
            "Ѩ" => "ѩ",
            "Ѭ" => "ѭ",
            "Ѯ" => "ѯ",
            "Ѱ" => "ѱ",
            "Ѳ" => "ѳ",
            "Ѵ" => "ѵ",

            "Đ" => "đ",
            "Ǵ" => "ǵ",
            "Ê" => "ê",
            "Ẑ" => "ẑ",
            "Ì" => "ì",
            "Ï" => "ï",
            "Jˇ" => "ǰ",
            "L̂" => "l̂",
            "N̂" => "n̂",
            "Ć" => "ć",
            "Ḱ" => "ḱ",
            "Ŭ" => "ŭ",
            "D̂" => "d̂",
            "Ŝ" => "ŝ",
            "Û" => "û",
            "Â" => "â",
            "G̀" => "g",

            "Ě" => "ě",
            "G̀" => "g",
            "Ġ" => "ġ",
            "Ğ" => "ğ",
            "Ž̦" => "ž",
            "Ķ" => "ķ",
            "K̄" => "k̄",
            "Ṇ" => "ṇ",
            "Ṅ" => "ṅ",
            "Ṕ" => "ṕ",
            "Ò" => "ò",
            "Ç" => "ç",
            "Ţ" => "ţ",
            "Ù" => "ù",
            "U" => "u",
            "Ḩ" => "ḩ",
            "C̄" => "c̄",
            "Ḥ" => "ḥ",
            "C̆" => "c̆",
            "Ç̆" => "ç̆",
            "Z̆" => "z̆",
            "Ă" => "ă",
            "Ä" => "ä",
            "Ĕ" => "ĕ",
            "Z̄" => "z̄",
            "Z̈" => "z̈",
            "Ź" => "ź",
            "Î" => "î",
            "Ö" => "ö",
            "Ô" => "ô",
            "Ü" => "ü",
            "Ű" => "ű",
            "C̈" => "c̈",
            "Ÿ" => "ÿ",

    "Ҋ" => "ҋ",
    "Ҍ" => "ҍ",
    "Ҏ" => "ҏ",
    "Ґ" => "ґ",
    "Ғ" => "ғ",
    "Ҕ" => "ҕ",
    "Җ" => "җ",
    "Ҙ" => "ҙ",
    "Қ" => "қ",
    "Ҝ" => "ҝ",
    "Ҟ" => "ҟ",
    "Ҡ" => "ҡ",
    "Ң" => "ң",
    "Ҥ" => "ҥ",
    "Ҧ" => "ҧ",
    "Ҩ" => "ҩ",
    "Ҫ" => "ҫ",
    "Ҭ" => "ҭ",
    "Ү" => "ү",
    "Ұ" => "ұ",
    "Ҳ" => "ҳ",
    "Ҵ" => "ҵ",
    "Ҷ" => "ҷ",
    "Ҹ" => "ҹ",
    "Һ" => "һ",
    "Ҽ" => "ҽ",
    "Ҿ" => "ҿ",
    "Ӂ" => "ӂ",
    "Ӄ" => "ӄ",
    "Ӆ" => "ӆ",
    "Ӈ" => "ӈ",
    "Ӊ" => "ӊ",
    "Ӌ" => "ӌ",
    "Ӎ" => "ӎ",
    "Ӑ" => "ӑ",
    "Ӓ" => "ӓ",
    "Ӕ" => "ӕ",
    "Ӗ" => "ӗ",
    "Ә" => "ә",
    "Ӛ" => "ӛ",
    "Ӝ" => "ӝ",
    "Ӟ" => "ӟ",
    "Ӡ" => "ӡ",
    "Ӣ" => "ӣ",
    "Ӥ" => "ӥ",
    "Ӧ" => "ӧ",
    "Ө" => "ө",
    "Ӫ" => "ӫ",
    "Ӭ" => "ӭ",
    "Ӯ" => "ӯ",
    "Ӱ" => "ӱ",
    "Ӳ" => "ӳ",
    "Ӵ" => "ӵ",
    "Ӷ" => "ӷ",
    "Ӹ" => "ӹ",
    "Ӽ" => "ӽ",
    "Ӿ" => "ӿ",

    "Ѡ" => "ѡ",         //      historical Cyrillic
    "Ѣ" => "ѣ",
    "Ѥ" => "ѥ",
    "Ѧ" => "ѧ",
    "Ѩ" => "ѩ",
    "Ѫ" => "ѫ",
    "Ѭ" => "ѭ",
    "Ѯ" => "ѯ",
    "Ѱ" => "ѱ",
    "Ѳ" => "ѳ",
    "Ѵ" => "ѵ",
    "Ѷ" => "ѷ",
    "Ѹ" => "ѹ",
    "Ѻ" => "ѻ",
    "Ѽ" => "ѽ",
    "Ѿ" => "ѿ",
    "Ҁ" => "ҁ",
    "Ǎ" => "ǎ",
    "F̀" => "f̀",
    "Ỳ" => "ỳ",

            "Ð?" => "Ð°",
            "Ð‘" => "Ð±",
            "Ð’" => "Ð²",
            "Ð“" => "Ð³",
            "Ð”" => "Ð´",
            "Ð•" => "Ðµ",
            "Ð–" => "Ð¶",
            "Ð—" => "Ð·",
            "Ð˜" => "Ð¸",
            "Ð™" => "Ð¹",
            "Ðš" => "Ðº",
            "Ð›" => "Ð»",
            "Ðœ" => "Ð½",
            "Ðž" => "Ð¾",
            "ÐŸ" => "Ð¿",
            "Ð " => "Ñ€",
            "Ð¡" => "Ñ?",
            "Ð¢" => "Ñ‚",
            "Ð£" => "Ñƒ",
            "Ð¤" => "Ñ„",
            "Ð¥" => "Ñ…",
            "Ð¦" => "Ñ†",
            "Ð§" => "Ñ‡",
            "Ð¨" => "Ñˆ",
            "Ð©" => "Ñ‰",
            "Ðª" => "ÑŠ",
            "Ð«" => "Ñ‹",
            "Ð¬" => "ÑŒ",
            "Ð­" => "Ñ?",
            "Ð®" => "ÑŽ",
            "Ð¯" => "Ñ?",

            "Ð?" => "Ñ‘",
            "Ð‚" => "Ñ’",
            "Ðƒ" => "Ñ“",
            "Ð„" => "Ñ”",
            "Ð…" => "Ñ•",
            "Ð†" => "Ñ–",
            "Ð‡" => "Ñ—",
            "Ðˆ" => "Ñ˜",
            "Ð‰" => "Ñ™",
            "ÐŠ" => "Ñš",
            "Ð‹" => "Ñ›",
            "ÐŒ" => "Ñœ",
            "ÐŽ" => "Ñž",
            "Ð?" => "ÑŸ"
            );

			foreach ($lower as $key => $value){
				$string = preg_replace("/".$key."/i", "$value", $string);
			}
        }

        if ($liga) {  //  convert upper case ligatures into lower case

            //  encode  the string to HTML entities
            $string = superentities($string);

            $upper_liga = array (
                "&#198;"   => "&#230;",     //      AE
                "&#338;"   => "&#339;",     //      OE
                "&#306;"    => "&#307;",    //      IJ
                "&#42792;"  => "&#42793;",  //      TZ
                "&#42802;"  => "&#42803;",  //      AA
                "&#42804;"  => "&#42805;",  //      AO
                "&#42806;"  => "&#42807;",  //      AU
                "&#42808;"  => "&#42809;",  //      AV
                "&#42812;"  => "&#42813;",  //      AY
                "&#42830;"  => "&#42831;"   //      OO
            );

			foreach ($char as $key => $value){
				$string = preg_replace("/".$key."/i", "$value", $string);
			}
            //  make it readable as plain UTF-8 again
            $string = html_entity_decode($string, ENT_QUOTES, "UTF-8");  //  to be used on 'Shared Hosting' server
            //$string = preg_replace_callback("/(&#[0-9]+;)/", function($m) { return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES"); }, $string); //  to be used on advanced server
        }

        return (strtr($string,  "ABCDEFGHIJKLMNOPQRSTUVWXYZ",
                            "abcdefghijklmnopqrstuvwxyz"));
    }

    function superentities($str){
        $str2  = '';
        // get rid of existing entities else double-escape
        $str = html_entity_decode(stripslashes($str),ENT_QUOTES,'UTF-8');
        $ar = preg_split('/(?<!^)(?!$)/u', $str );  // return array of every multi-byte character
        foreach ($ar as $c){
            $o = ord($c);
            if ( (strlen($c) > 1) || /* multi-byte [unicode] */
                ($o <32 || $o > 126) || /* <- control / latin weirdos -> */
                ($o >33 && $o < 40) ||/* quotes + ambersand */
                ($o >59 && $o < 63) /* html */
            ) {
                // convert to numeric entity
                $c = mb_encode_numericentity($c, array(0x0, 0xffff, 0, 0xffff), 'UTF-8');
            }
            $str2 .= $c;
        }
        return $str2;
    }

    //  check whether ZIP function is available
    if (!function_exists('gzopen') && !function_exists('gzopen64')) {
        // display error message
        echo "<!DOCTYPE HTML>\n";
        echo "  <head>\n";
        echo "      <title>Sphider-plus administrator warning</title>\n";
        // meta data
        echo "      <meta charset='UTF-8'>\n";
        echo "      <meta name='public' content='all'>\n";
        echo "      <meta http-equiv='expires' content='0'>\n";
        echo "      <meta http-equiv='pragma' content='no-cache'>\n";
        echo "      <meta http-equiv='X-UA-Compatible' content='IE=9' />\n";
        echo "      <link rel='stylesheet' type='text/css' href='../templates/Sphider-plus/adminstyle.css' />\n";
        echo "  </head>\n";
        echo "  <body>
    <h1>Sphider-plus administrator warning</h1>
    <div class='cntr warnadmin sml'>
        <br />
        <br />
        <strong>Attention:</strong>  Sphider-plus does not work with your current PHP installation.
        <br />
        <br />
        An installed ZIP library (zlib) as part of the PHP environment is obligatory required.
        <br /><br />
        Currently gzopen() does not work. Alternately also gzopen64() is not available on your server.<br />
        <br /><br />
    </div>
  </body>
</html>";
        die ;
    }

    //  known bug in PHP 5.3+
    //  wrapper to bypass the PHP fault  by replacing gzopen() with gzopen64()
    if(!function_exists('gzopen') && function_exists('gzopen64')){
        function gzopen($filename, $mode, $use_include_path = 0){
            return gzopen64($filename, $mode, $use_include_path);
        }
    }

    if(!function_exists('gzread') && function_exists('gzread64')){
        function gzread($filename, $length = 10000){
            return gzread64($filename, $length);
        }
    }

    if(!function_exists('gzdecode') && function_exists('gzdecode64')){
        function gzdecode($filename, $length = 10000){
            return gzdecode64($filename, $length);
        }
    }

    if(!function_exists('gzinflate') && function_exists('gzinflate64')){
        function gzinflate($filename, $length = 10000){
            return gzinflate64($filename, $length);
        }
    }

    if(!function_exists('gzclose') && function_exists('gzclose64')){
        function gzclose($filename){
            return gzclose64($filename);
        }
    }

    //  process all common lists
    include "commons.php";

    function is_num($var) {
        for ($i=0;$i<strlen($var);$i++) {
            $ascii_code=ord($var[$i]);
            if ($ascii_code >=49 && $ascii_code <=57){
                continue;
            } else {
                return false;
            }
        }
        return true;
    }

    function getHttpVars() {
        $superglobs = array(
        '_POST',
        '_GET',
        'HTTP_POST_VARS',
        'HTTP_GET_VARS');

        $httpvars = array();

        // extract the right array
        foreach ($superglobs as $glob) {
            global $$glob;
            if (isset($$glob) && is_array($$glob)) {
                $httpvars = $$glob;
         }
         if (count($httpvars) > 0)
            break;
        }
        //echo "<br>http Array:<br><pre>";print_r($httpvars);echo "</pre>";
        return $httpvars;

    }

    function countSubstrs($haystack, $needle) {
        $count = 0;
        while(strpos($haystack,$needle) !== false) {
            $haystack = substr($haystack, (strpos($haystack,$needle) + 1));
            $count++;
        }
        return $count;
    }

    function quote_replace($str) {

        $str = str_replace("\"", "&quot;", $str);
        return str_replace("'","&apos;", $str);
    }


    function fst_lt_snd($version1, $version2) {

        $list1 = explode(".", $version1);
        $list2 = explode(".", $version2);

        $length = count($list1);
        $i = 0;
        while ($i < $length) {
            if ($list1[$i] < $list2[$i])
            return true;
            if ($list1[$i] > $list2[$i])
            return false;
            $i++;
        }

        if ($length < count($list2)) {
            return true;
        }
        return false;

    }

    function get_dir_contents($dir) {
        $contents = Array();
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != "..") {
                    $contents[] = $file;
                }
            }
            closedir($handle);
        }
        return $contents;
    }

    function replace_ampersand($str) {
        return str_replace("&", "&amp;", $str);
    }

    function list_cats($parent, $lev, $color, $message) {
        global $db_con, $mysql_table_prefix, $debug, $dba_act;

        if ($lev == 0) {
            echo "<div class='submenu cntr y3'>|&nbsp;&nbsp;&nbsp;Database $dba_act&nbsp;&nbsp;&nbsp;Table prefix '$mysql_table_prefix'&nbsp;&nbsp;&nbsp;|<br />
        <ul>
            <li><a href='admin.php?f=add_cat'>Add category</a></li>
        </ul>
        </div>
";
            echo $message;
            echo "<div class='panel'>
    <table class='w100'>
    <tr>
        <td class='tblhead' colspan='3'>Categories</td>
    </tr>
    ";
        }
        $space = "";
        for ($x = 0; $x < $lev; $x++) {
            $space .= "<span class='tree'>&raquo;</span>&nbsp;";
        }

        $sql_query = "SELECT * FROM ".$mysql_table_prefix."categories WHERE parent_num=$parent ORDER BY category";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($result->num_rows) {
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                if ($color =="odrow") {
                    $color = "evrow";
                } else {
                    $color = "odrow";
                }
                $id = $row['category_id'];
                $cat = $row['category'];
                echo "<tr class='$color'>
        ";
                if (!$space=="") {
                    echo "<td class='w85'>
        <div>$space<a class='options' href='admin.php?f=edit_cat&amp;cat_id=$id'
            title='Edit this Sub-Category'>".stripslashes($cat)."</a></div></td>
        <td class='options'><a href='admin.php?f=edit_cat&amp;cat_id=$id' class='options' title='Edit this Sub-Category'>Edit</a></td>
        <td class='options'><a href='admin.php?f=11&amp;cat_id=$id' title='Delete this Sub-Category'
            onclick=\"return confirm('Are you sure you want to delete? Subcategories will be lost.')\" class='options'>Delete</a></td>
    </tr>
    ";
                } else {
                    echo"<td class='85%'><a class='options' href='admin.php?f=edit_cat&amp;cat_id=$id'
            title='Edit this Category'>".stripslashes($cat)."</a></td>
        <td class='options'><a href='admin.php?f=edit_cat&amp;cat_id=$id' class='options' title='Edit this Category'>Edit</a></td>
        <td class='options'><a href='admin.php?f=11&amp;cat_id=$id' title='Delete this Category'
            onclick=\"return confirm('Are you sure you want to delete? Subcategories will be lost.')\" class='options'>Delete</a></td>
    </tr>
";
                }
                $color = list_cats($id, $lev + 1, $color, "");
            }
        }
        if ($lev == 0) {
            echo "</table>
</div>
";
        }
        return $color;
    }

    function list_catsform($parent, $lev, $color, $message, $category_id) {
        global $db_con, $mysql_table_prefix, $debug;

        if ($lev == 0) {
            print "\n";
        }
        $space = "";
        for ($x = 0; $x < $lev; $x++)
        $space .= "&nbsp;&nbsp;&nbsp;-&nbsp;";

        $sql_query = "SELECT * FROM ".$mysql_table_prefix."categories WHERE parent_num=$parent ORDER BY category LIMIT 0 , 300";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

        if ($result->num_rows){
            print "<option ".$selected." value=\"0\">&nbsp;&nbsp;none</option>\n";  //select no category
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $id = $row['category_id'];
                $cat = $row['category'];
                $selected = " selected=\"selected\" ";
                if ($category_id != $id) { $selected = ""; }
                print "<option ".$selected." value=\"".$id."\">".$space.stripslashes($cat)."</option>\n";

                $color = list_catsform($id, $lev + 1, $color, "", $category_id);
            }
        }
        return $color;
    }

    function getmicrotime(){
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
    }

    function saveToLog($query, $time, $results, $ip, $media) {
        global $debug;
        global $dbu_act, $db1_slv, $db2_slv, $db3_slv, $db4_slv, $db5_slv;
        global $database1, $database2, $database3, $database4, $database5;
        global $mysql_table_prefix1, $mysql_table_prefix2, $mysql_table_prefix3, $mysql_table_prefix4, $mysql_table_prefix5;
        global $mysql_host1, $mysql_host2, $mysql_host3, $mysql_host4, $mysql_host5;
        global $mysql_user1, $mysql_user2, $mysql_user3, $mysql_user4, $mysql_user5;
        global $mysql_password1, $mysql_password2, $mysql_password3, $mysql_password4, $mysql_password5;

        //      re-active default db for 'Search User'

        if ($dbu_act == '1') {
            $db_con     = db_connect($mysql_host1, $mysql_user1, $mysql_password1, $database1);
            if ($prefix > '0' ) {
                $mysql_table_prefix = $prefix;
            } else {
                $mysql_table_prefix = $mysql_table_prefix1;
            }
        }

        if ($dbu_act == '2') {
            $db_con = db_connect($mysql_host2, $mysql_user2, $mysql_password2, $database2);
            if ($prefix > '0' ) {
                $mysql_table_prefix = $prefix;
            } else {
                $mysql_table_prefix = $mysql_table_prefix2;
            }
        }

        if ($dbu_act == '3') {
            $db_con = db_connect($mysql_host3, $mysql_user3, $mysql_password3, $database3);
            if ($prefix > '0' ) {
                $mysql_table_prefix = $prefix;
            } else {
                $mysql_table_prefix = $mysql_table_prefix3;
            }
        }

        if ($dbu_act == '4') {
            $db_con = db_connect($mysql_host4, $mysql_user4, $mysql_password4, $database4);
            if ($prefix > '0' ) {
                $mysql_table_prefix = $prefix;
            } else {
                $mysql_table_prefix = $mysql_table_prefix4;
            }
        }

        if ($dbu_act == '5') {
            $db_con = db_connect($mysql_host5, $mysql_user5, $mysql_password5, $database5);
            if ($prefix > '0' ) {
                $mysql_table_prefix = $prefix;
            } else {
                $mysql_table_prefix = $mysql_table_prefix5;
            }
        }

        if ($results =="") {
            $results = 0;
        }
        $sql_query =  "INSERT into ".$mysql_table_prefix."query_log (query, time, elapsed, results, ip, media) values ('$query', NOW(), '$time', '$results', '$ip', '$media')";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }

    }

    function parse_addr($url) {     //  function like parse_url, but working also for non ASCII URLs
        $urlparts = array();
        $sch = '';
        $h = '';
        $o = '';
        $p = '';
        $q = '';
        $f = '';
        $url = str_replace("\\", "/", $url);
        $url2 = $url."/";       //      might be missing at some 301 relocated addresses

        $sch = strpos($url, "://");                     //    end of [scheme] = begin of [host]
        $urlparts[scheme] = substr($url, 0, $sch);
        $h = strpos(substr($url2, $sch+3), "/");        //    endpos of [host]port] = begin of [path]

        $host_port = substr($url, $sch+3, $h);
        $o = strpos(substr($url, $sch+3, $h), ":");     //  find [port] delimiter

        if (!$o) {
            $urlparts[host] = substr($url, $sch+3, $h);
        } else {                                                //  if [port] available
            $urlparts[host] = substr($url, $sch+3, $o);         //  only [host]
            $urlparts[port] = substr($url, $sch+$o+4, $h-$o-1); //  additionally [port]
        }

        $p = strpos(substr($url, $h+1), "/");           //    begin position [path]
        $q = strpos(substr($url, $h+$p+1), "?");        //    find begin of [query]

        if (!$q) {  //  if no query found
            $urlparts[path] = substr($url, $h+$p+1);
        } else {
            $urlparts[path] = substr($url, $h+$p+1, $q);
            $f = strpos(substr($url, $h+$p+$q+2), "#");         //   find beginn of [fragment]
        }

        if ($q && !$f) {  //  if no fragment found
            $urlparts[query] = substr($url, $h+$p+$q+2);        //   only [query]
        }
        if ($f) {
            $urlparts[query] = substr($url, $h+$p+$q+2, $f);
            $urlparts[fragment] = substr($url, $h+$p+$q+$f+3);
        }

        return ($urlparts); //  [user] and [pass] are currently not parsed
    }

    function convert_url($url) {    //  storable for MySQL
        $url = str_replace("&amp;", "&", $url);
        $url = str_replace(" ", "%20", $url);
        return $url;
    }

    function reconvert_url($url) {  //  readable for messages
        $url = str_replace("&amp;","&", $url);
        $url = str_replace("%20", " ", $url);
        return $url;
    }

    function cleanup_text($input='', $preserve='', $allowed_tags='') {
        if (empty($preserve)){
            $input = strip_tags($input, $allowed_tags);
        }
        $input = htmlspecialchars($input, ENT_QUOTES);
        return $input;
    }

    function cleaninput($input) {
        global $db_con, $block_attacks, $no_email;

		$dir = str_replace('\\', '/', getcwd());

        //  left from the good old days
        if (get_magic_quotes_gpc()) {
            $input = stripslashes($input);  //      delete quotes
        }

        //  some known corrupts
        if (preg_match("/\'0\=A|pdf\'A\=0/i",$input)) {
            $input = '';
        }

        //  this is the     tr/vb.hpq trojan
        if (preg_match("/%FF%FE%3C%73%63%72%69%70%74%3E/i",$input)) {
            $input = '';
        }


        //  this is a simple SQL injection
        if (preg_match("/\<WBLGXV\>J2QWH\[\!\%2b\!\]\<\/WBLGXV\>/i",$input)) {
            $input = '';
        }

        if (stripos($input, "OR 1=1;--")) {     //  tries to start a comment in SQL query
            $input = '';                        // might offer username and password of db
        }

        if ($block_attacks == "1" && !stripos($dir, "/admin")) {

            //  prevent SQL-injection
			$res 	= $input;
            $input 	= @mysqli_real_escape_string($db_con, $input);
			//	mysqli will not work, before first database is defined correctly
			if (strlen($input) <  3) {
				$input = $res;
			}

            //	prevent XSS-attack and Shell-execute
            if (preg_match("/cmd|CREATE|DELETE|DROP|eval|EXEC|File|INSERT|printf/i",$input)) {
                $input = '';
            }

            //if (preg_match("/LOCK|PROCESSLIST|SELECT|shell|SHOW|SHUTDOWN/i",$input)) {
            if (preg_match("/PROCESSLIST|SELECT|shell|SHOW|SHUTDOWN/i",$input)) {
                $input = '';
            }
            if (preg_match("/SQL|SYSTEM|TRUNCATE|UNION|UPDATE|DUMP/i",$input)) {
                $input = '';
            }

            //  basic XSRF prevention  (    more effective would be the pattern  /<(.*?)/i     )
            if (preg_match("/<img/i",$input)) {
                $input = '';
            }

            //      prevent directory traversal attacks
            if(preg_match('/\.\.\/|\.\.\\\/i', $input)) {
                $input = '';
            }

            //  suppress JavaScript execution and tag inclusions
            $input = unsafe($input);
        }

        return $input;
    }

    $UNSAFE_IN = array();
    $UNSAFE_IN[] = "/script/i";
    $UNSAFE_IN[] = "/alert/i";
    $UNSAFE_IN[] = "/javascript\s*:/i";
    $UNSAFE_IN[] = "/vbscri?pt\s*:/i";
    $UNSAFE_IN[] = "/<\s*embed.*swf/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onabort\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onblur\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onchange\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onfocus\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onmouseout\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onmouseover\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onload\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onreset\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onselect\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onsubmit\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onunload\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onerror\s*=/i";
    $UNSAFE_IN[] = "/<[^>]*[^a-z]onclick\s*=/i";
    $UNSAFE_IN[] = "/onabort\s*=/i";
    $UNSAFE_IN[] = "/onblur\s*=/i";
    $UNSAFE_IN[] = "/onchange\s*=/i";
    $UNSAFE_IN[] = "/onfocus\s*=/i";
    $UNSAFE_IN[] = "/onmouseout\s*=/i";
    $UNSAFE_IN[] = "/onmouseover\s*=/i";
    $UNSAFE_IN[] = "/onload\s*=/i";
    $UNSAFE_IN[] = "/onreset\s*=/i";
    $UNSAFE_IN[] = "/onselect\s*=/i";
    $UNSAFE_IN[] = "/onsubmit\s*=/i";
    $UNSAFE_IN[] = "/onunload\s*=/i";
    $UNSAFE_IN[] = "/onerror\s*=/i";
    $UNSAFE_IN[] = "/onclick\s*=/i";

    function unsafe($input) {
        global $UNSAFE_IN;

        foreach ($UNSAFE_IN as $match) {
            if( preg_match($match, $input)) {
                $input = '';
                return $input;
            }
        }
        return $input;
    }

    function ip2bin($ip) {
        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false)
        return base_convert(ip2long($ip),10,2);
        if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false)
        return false;
        if(($ip_n = inet_pton($ip)) === false) return false;
        $bits = 15; // 16 x 8 bit = 128bit (ipv6)
        while ($bits >= 0) {
            $bin = sprintf("%08b",(ord($ip_n[$bits])));
            $ipbin = $bin.$ipbin;
            $bits--;
        }
        return $ipbin;
    }

    function bin2ip($bin) {
        if(strlen($bin) <= 32) // 32bits (ipv4)
        return long2ip(base_convert($bin,2,10));
        if(strlen($bin) != 128)
        return false;
        $pad = 128 - strlen($bin);
        for ($i = 1; $i <= $pad; $i++) {
            $bin = "0".$bin;
        }
        $bits = 0;
        while ($bits <= 7) {
            $bin_part = substr($bin,($bits*16),16);
            $ipv6 .= dechex(bindec($bin_part)).":";
            $bits++;
        }
        return inet_ntop(inet_pton(substr($ipv6,0,-1)));
    }

    function IPv4To6($Ip) {
        //  Convert an IPv4 address to IPv6
        //   @param string IP Address in dot notation (192.168.1.100)
        //   @return string IPv6 formatted address or false if invalid input

        static $Mask = '::ffff:'; // This tells IPv6 it has an IPv4 address
        $IPv6 = (strpos($Ip, '::') === 0);
        $IPv4 = (strpos($Ip, '.') > 0);

        if (!$IPv4 && !$IPv6) return false;
        if ($IPv6 && $IPv4) $Ip = substr($Ip, strrpos($Ip, ':')+1); // Strip IPv4 Compatibility notation
        elseif (!$IPv4) return $Ip; // Seems to be IPv6 already?
        $Ip = array_pad(explode('.', $Ip), 4, 0);
        if (count($Ip) > 4) return false;
        for ($i = 0; $i < 4; $i++) if ($Ip[$i] > 255) return false;

        $Part7 = base_convert(($Ip[0] * 256) + $Ip[1], 10, 16);
        $Part8 = base_convert(($Ip[2] * 256) + $Ip[3], 10, 16);
        return $Mask.$Part7.':'.$Part8;
    }

    function ExpandIPv6Notation($Ip) {
        //  replace '::' with appropriate number of ':0'
        if (strpos($Ip, '::') !== false)
            $Ip = str_replace('::', str_repeat(':0', 8 - substr_count($Ip, ':')).':', $Ip);
        if (strpos($Ip, ':') === 0) $Ip = '0'.$Ip;
        return $Ip;
    }

    function IPv6ToLong($Ip, $DatabaseParts= 2) {
        //  Convert IPv6 address to an integer
        //  Optionally split in to two parts.
        //  @see http://stackoverflow.com/questions/420680/
        $Ip = ExpandIPv6Notation($Ip);
        $Parts = explode(':', $Ip);
        $Ip = array('', '');
        for ($i = 0; $i < 4; $i++) $Ip[0] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
        for ($i = 4; $i < 8; $i++) $Ip[1] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);

        if ($DatabaseParts == 2)
                return array(base_convert($Ip[0], 2, 10), base_convert($Ip[1], 2, 10));
        else    return base_convert($Ip[0], 2, 10) + base_convert($Ip[1], 2, 10);
    }

    function to_utf8( $string ) {
        // From http://w3.org/International/questions/qa-forms-utf-8.html
        if ( preg_match('%^(?:
          [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
        | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
        )*$%xs', $string) ) {
            return $string;
        } else {
            return iconv( 'CP1252', 'UTF-8', $string);
        }
    }

    function mk5() {
        $handle = fopen ("./settings/database.php", "rb");
        $cont = fread ($handle, 8192);
        fclose ($handle);

        $activate = "dbu_act = \"5\";\r\n\r\n\$lock = \"1\";";
        $c2 = preg_replace ("/dbu_act = \"1\";/i", $activate, $cont);
        $c2 = preg_replace ("/db1_slv = \"1\"/i", "db1_slv = \"0\"", $c2);
        $c2 = preg_replace ("/db2_slv = \"1\"/i", "db2_slv = \"0\"", $c2);
        $c2 = preg_replace ("/db3_slv = \"1\"/i", "db3_slv = \"0\"", $c2);
        $c2 = preg_replace ("/db4_slv = \"1\"/i", "db4_slv = \"0\"", $c2);
        $c2 = preg_replace ("/db5_slv = \"0\"/i", "db5_slv = \"1\"", $c2);

        $handle = fopen ("./settings/database.php", "wb");
        fwrite ($handle, $c2);
        fclose ($handle);
        exit;
    }

    function footer() {
        global $db_con, $include_dir, $add_url, $most_pop, $mysql_table_prefix;

        echo "<p class=\"stats\"><a href=\"http://www.sphider-plus.eu\" title=\"Link: Visit Sphider-plus site in new window\" target=\"rel\">Visit&nbsp;<img class=\"mid\" src=\"$include_dir/images/sphider-plus-logo.gif\" alt=\"Visit Sphider site in new window\" height=\"39\" width=\"42\" /> Sphider-plus</a></p>";
    }

    function error_handler($errNo, $errStr, $errFile, $errLine){
        if(ob_get_length()) ob_clean();             // clear any output that has already been generated

        $error_message = 'ERRNO: ' . $errNo . chr(10) .
                    'TEXT: ' . $errStr . chr(10) .
                    'LOCATION: ' . $errFile .
                    ', line ' . $errLine;
        echo $error_message;
        exit;       // stop executing any script
    }

    function validate_url($input) {
        global $mytitle, $sph_messages;

        //	Standard URL test
		if (filter_var($input, FILTER_VALIDATE_URL) === false) {
            echo "<h1>$mytitle</h1>
            <br />
            <p class='warnadmin cntr'>
            Invalid input for 'URL'
            </p>
            <a class='bkbtn' href='addurl.php?call=set' title='Go back to Submission Form'>".$sph_messages['BackToSubForm']."</a>
            </body>
            </html>
        ";
            die ('');
        }

        //      Do we have a valid DNS ? This test is disabled for localhost application as checkdnsrr needs Internet access
        $localhost = strstr(htmlspecialchars(@$_SERVER['HTTP_REFERER']), "localhost");
        if (!$localhost) {
            if (preg_match("/www/i", $input)){
                $input1 = preg_replace ('/https?:\/\//i','',$input);
                $pos = strpos($input1,"/");
                if ($pos != '') $input1 = substr($input1,0,$pos);
                if(@checkdnsrr("www.sphider-plus.eu", "A")) {    //    pre-check for correct response of checkdnsrr() on Windows OS
                    if(!checkdnsrr($input1, "A")) {
                        echo "<h1>$mytitle</h1>
                        <br />
                        <p class='warnadmin cntr'>Invalid URL input. No DNS resource available for this url
                        <a class='bkbtn' href='addurl.php?call=set' title='Go back to Submission Form'>".$sph_messages['BackToSubForm']."</a></p>
                        </body>
                        </html>
                    ";
                        die ('');
                    }
                }
            }
        }
        return ($input);
    }

    function validate_email($input) {
        global $mytitle, $sph_messages;

        //      kill LF, CR, comma, zero-bytes and entities
        $input = preg_replace('/[\0\r\n,]|(%0\s*\w)/im', null, urldecode($input));
        if (!preg_match('/\@localhost$/', $input)) {
            //	Standard e-mail test
            if(!preg_match('/^[\w.+-]{2,}\@[\w.-]{2,}\.[a-z]{2,6}$/', $input)) {
                echo "<h1>$mytitle</h1>
                <br />
                <p class='warnadmin cntr'>
                Invalid input for 'e-mail account'
                </p>
                <a class='bkbtn' href='addurl.php' title='Go back to Submission Form'>".$sph_messages['BackToSubForm']."</a>
                </body>
                </html>
            ";
                die ('');
            }
        } else {
            //  some rudimentarily test for localhost e-mail accounts
            if(!preg_match('/^[\w.+-]{2,}\@/', $input)) {
                echo "<h1>$mytitle</h1>
                <br />
                <p class='warnadmin cntr'>
                Invalid input for 'e-mail account'
                </p>
                <a class='bkbtn' href='addurl.php' title='Go back to Submission Form'>".$sph_messages['BackToSubForm']."</a>
                </body>
                </html>
            ";
                die ('');
            }
        }

        //      Check if Mail Exchange Resource Record (MX-RR)  is valid and also is stored in Domain Name System (DNS)
        //      This test is disabled for localhost applications as getmxrr needs internet access
        $localhost = strstr(htmlspecialchars(@$_SERVER['HTTP_REFERER']), "localhost");
        if (!$localhost) {
            if(!getmxrr(substr(strstr($input, '@'), 1), $mxhosts)) {
                echo "<h1>$mytitle</h1>
                <br />
                <p class='warnadmin cntr'>
                Invald e-mail account.<br />
                There is no valid Mail Exchange Resource Record (MX-RR)<br />
                on the Domain Name System (DNS)
                </p>
                <a class='bkbtn' href='addurl.php' title='Go back to Submission Form'>Back</a>
                </body>
                </html>
            ";
                die ('');
            }
        }
        return ($input);
    }

    class resize{
        // *** Class variables
        private $image;
        private $width;
        private $height;
        private $imageResized;

        function __construct($fileName){
            // *** Open up the file
            $this->image = $this->openImage($fileName);

            // *** Get width and height
            $this->width  = imagesx($this->image);
            $this->height = imagesy($this->image);
        }

        private function openImage($file){
            // *** Get extension
            $extension = strtolower(strrchr($file, '.'));

            switch($extension)
            {
                case '.jpg':
                case '.jpeg':
                    $img = @imagecreatefromjpeg($file);
                    break;
                case '.gif':
                    $img = @imagecreatefromgif($file);
                    break;
                case '.png':
                    $img = @imagecreatefrompng($file);
                    break;
                default:
                    $img = false;
                    break;
            }
            return $img;
        }

        public function resizeImage($newWidth, $newHeight, $option="auto"){
            // *** Get optimal width and height - based on $option
            $optionArray = $this->getDimensions($newWidth, $newHeight, $option);

            $optimalWidth  = $optionArray['optimalWidth'];
            $optimalHeight = $optionArray['optimalHeight'];


            // *** Resample - create image canvas of x, y size
            $this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
            imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);


            // *** if option is 'crop', then crop too
            if ($option == 'crop') {
                $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
            }
        }

        private function getDimensions($newWidth, $newHeight, $option){

            switch ($option)
            {
                case 'exact':
                    $optimalWidth = $newWidth;
                    $optimalHeight= $newHeight;
                    break;
                case 'portrait':
                    $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                    $optimalHeight= $newHeight;
                    break;
                case 'landscape':
                    $optimalWidth = $newWidth;
                    $optimalHeight= $this->getSizeByFixedWidth($newWidth);
                    break;
                case 'auto':
                    $optionArray = $this->getSizeByAuto($newWidth, $newHeight);
                    $optimalWidth = $optionArray['optimalWidth'];
                    $optimalHeight = $optionArray['optimalHeight'];
                    break;
                case 'crop':
                    $optionArray = $this->getOptimalCrop($newWidth, $newHeight);
                    $optimalWidth = $optionArray['optimalWidth'];
                    $optimalHeight = $optionArray['optimalHeight'];
                    break;
            }
            return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
        }

        private function getSizeByFixedHeight($newHeight){
            $ratio = $this->width / $this->height;
            $newWidth = $newHeight * $ratio;
            return $newWidth;
        }

        private function getSizeByFixedWidth($newWidth){
            $ratio = $this->height / $this->width;
            $newHeight = $newWidth * $ratio;
            return $newHeight;
        }

        private function getSizeByAuto($newWidth, $newHeight){
            if ($this->height < $this->width)
            // *** Image to be resized is wider (landscape)
            {
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);
            }
            elseif ($this->height > $this->width)
            // *** Image to be resized is taller (portrait)
            {
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;
            }
            else
            // *** Image to be resizerd is a square
            {
                if ($newHeight < $newWidth) {
                    $optimalWidth = $newWidth;
                    $optimalHeight= $this->getSizeByFixedWidth($newWidth);
                } else if ($newHeight > $newWidth) {
                    $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                    $optimalHeight= $newHeight;
                } else {
                    // *** Sqaure being resized to a square
                    $optimalWidth = $newWidth;
                    $optimalHeight= $newHeight;
                }
            }

            return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
        }

        private function getOptimalCrop($newWidth, $newHeight){

            $heightRatio = $this->height / $newHeight;
            $widthRatio  = $this->width /  $newWidth;

            if ($heightRatio < $widthRatio) {
                $optimalRatio = $heightRatio;
            } else {
                $optimalRatio = $widthRatio;
            }

            $optimalHeight = $this->height / $optimalRatio;
            $optimalWidth  = $this->width  / $optimalRatio;

            return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
        }

        private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight){
            // *** Find center - this will be used for the crop
            $cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
            $cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );

            $crop = $this->imageResized;
            //imagedestroy($this->imageResized);

            // *** Now crop from center to exact requested size
            $this->imageResized = imagecreatetruecolor($newWidth , $newHeight);
            imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
        }

        public function saveImage($savePath, $imageQuality="100"){
            // *** Get extension
            $extension = strrchr($savePath, '.');
            $extension = strtolower($extension);

            switch($extension)
            {
                case '.jpg':
                case '.jpeg':
                    if (imagetypes() & IMG_JPG) {
                        imagejpeg($this->imageResized, $savePath, $imageQuality);
                    }
                    break;

                case '.gif':
                    if (imagetypes() & IMG_GIF) {
                        imagegif($this->imageResized, $savePath);
                    }
                    break;

                case '.png':
                    // *** Scale quality from 0-100 to 0-9
                    $scaleQuality = round(($imageQuality/100) * 9);

                    // *** Invert quality setting as 0 is best, not 9
                    $invertScaleQuality = 9 - $scaleQuality;

                    if (imagetypes() & IMG_PNG) {
                        imagepng($this->imageResized, $savePath, $invertScaleQuality);
                    }
                    break;

                default:
                    // *** No extension - No save.
                    break;
            }
            imagedestroy($this->imageResized);
        }
    }

    function resample($img, $width, $height){
        // Set a maximum height and width
        $width = 400;
        $height = 400;

        // Content type
        header('Content-type: image/jpeg');

        // Get new dimensions
        list($width_orig, $height_orig) = getimagesize($img);

        $ratio_orig = $width_orig/$height_orig;

        if ($width/$height > $ratio_orig) {
            $width = $height*$ratio_orig;
        } else {
            $height = $width/$ratio_orig;
        }

        // Resample
        $image_p = imagecreatetruecolor($width, $height);
        $image = imagecreatefromjpeg($img);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
        // Output
        //imagejpeg($image_p, null, 100);
        return ($image_p);
    }

    function del_secchars($file){
        global $cn_seg, $jp_seg, $del_secchars, $del_seccharin;

        if ($jp_seg == '1' || $cn_seg == '1' && $del_secchars==1 || $del_seccharin ==1) {
            //      Delete additional characters (as word separator) like dots, question marks, colons etc. (characters 1-49 in original Chinese dictionary)
            $file = preg_replace ('/。|，|〿|；|：|？|＿|…|—|·|ˉ|ˇ|¨|‘|’|“|‿|々|～|‖|∶|＂|＇|｀|｜|〃|〔|〕|〈|〉|《|》|「|〿|『|〿|．|〖|〗|〿|】|（|）|［|］|｛|ｿ/', " ", $file);
            $file = preg_replace('/ï¼›|¡£|£¬|¡¢|£»|£º|£¿|£¡|¡­|¡ª|¡¤|¡¥|¡¦|¡§|¡®|¡¯|¡°|¡±|¡©|¡«|¡¬|¡Ã|£¢|£§|£à|£ü|¡¨|¡²|¡³|¡´|¡µ|¡¶|¡·|¡¸|¡¹|¡º|¡»|£®|¡¼|¡½|¡¾|¡¿|£¨|£©|£Û|£ÿ|£û|£ý|°¢/', " ", $file);
            $file = preg_replace('/＿|＆|，|<|：|；|・|\(|\)/', " ", $file);
        }

        if ($del_secchars == '1') {
            //    kill  special characters at the end of words
            $file = preg_replace('/— |\]. |\%\? |\"\. |, |.\' |\. |\.\. |\.\.\. |! |\? |" |: |\) |\), |\)\. |】 |） |？,|？ |！ |！|。,|。 |„ |“ |” |”|”&nbsp;|» |\.»|;»|:»|,»|\.»|·»|«|« |», |»\. |\.” |,”|;” |”\. |”, |‿|、|）|·|;|\] |\} |_, |_ |”\)\. |.\"> |\"> |> |\)|&lt; |\%, |\%. |\%.\" |\% |\+\+ |\+ |\* |\# |\~ |© |® |™ /', " ", $file);
            //    kill special characters in front of words
            $file = preg_replace('/ —| \(\"| \(\$| \(\@| \@| \[| "| \(| „|„| “|（| «| 【| ‿| （| \(“|“| ©| ®| ™| –| <| \/|\/| \\"| \.| \^| &gt;| \$| \£| \"\(| \+| \*| \.| \#| \%| \~| \{| »/', " ", $file);
        }

        if ($del_seccharin == '1'){
            $file = del_secintern($file);
        }
        return $file;
    }

    function del_secintern($file) {
        //    kill separating characters inside of words
        //$file = preg_replace('/=|"|\<|\>|\_\#|\+|%|&|_|\(|\)|\.\.\.|\.\.|\//', " ", $file);       //  light version
        $file = preg_replace('/=|"|\<|\>|\]|\[|\(|\)|\_\#|\+|%|&|_|\(|\)|\.|\.\.\.|\.\.|\/|=\\":\/\|\"|・|\/\"|\@/', " ", $file);

        return $file;
    }

    function split_words($file) {
        global $div_all, $div_hyphen;

        $all = '';
		preg_match_all("@([\S]+[.|,|'|‘|‘|´|`|’|’|\-|\—|_\/])+[\S\.\,\'\—\-\_\?\!]+@si", $file, $regs, PREG_SET_ORDER); // get dot, comma and quote combined words
		$file = preg_replace ("@/-|-|—@si", " ", $file);	//  divide words into their basic parts

        foreach ($regs as $value) {
			if ($div_hyphen && !$div_all && strstr($value[1], "-")) {
				$all .= " ".$value[0]."";   // collect all words, only combined with hyphens
			} else {
				$all .= " ".$value[0]."";   // collect all combined words
			}
        }
        $file .= "".$all." ";           	//  add the combined words to $file
		return ($file);
    }

    //  try to open a file my means of cURL library
    function curl_open($url) {
        $result = '';
        $curl_handle = curl_init();
        curl_setopt($curl_handle,CURLOPT_URL,$url);
        curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
        curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
        $result = curl_exec($curl_handle);
        curl_close($curl_handle);
        return ($result);
    }

    function translit_el($string) {

        //  1. replace special blanks with " "
        $string = str_replace("&nbsp;", " ", $string);

        //  2. replace Latin 's' at the end of multiple query words with the Greek 'ς'
        if(strstr($string, " ")){
            $str_array = explode(" ", $string); //  build an intermediate array

            foreach ($str_array as $this_word) {
                if (strrpos($this_word, "s") == strlen($this_word)-1) {     //  if 's' is last letter of this word
                    $str_array2[] = substr_replace($this_word, "ς", strlen($this_word)-1);
                } else {
                    $str_array2[] = $this_word;     //  no 's' as last letter was found, just use the input word
                }
            }
            $string = implode(" ", $str_array2);    //  rebuild the string
        } else {
            //  replace Latin 's' at the end of a single query word with the Greek 'ς'
            if (strrpos($string, "s") == strlen($string)-1) {
                $string = substr_replace($string, "ς", strlen($string)-1);
            }
        }

        //  3. translit some specialities
        $string = preg_replace('/TH/', "Θ", $string);
        $string = preg_replace('/th/', "θ", $string);

        $string = preg_replace('/CH/', "Χ", $string);
        $string = preg_replace('/ch/', "χ", $string);

        $string = preg_replace('/PS/', "Ψ", $string);
        $string = preg_replace('/ps/', "ψ", $string);

        $string = preg_replace('/PH/', "Φ", $string);
        $string = preg_replace('/ph/', "φ", $string);


        //  4. translit upper case letters
        $en = array("A","V","C","G","D","E","F","Z","I","K","L","M","N","X","O","P","Q","R","S","T","W","Y");
        $el = array("Α","Β","Ξ","Γ","Δ","Ε","Φ","Ζ","Ι","Κ","Λ","Μ","Ν","Ξ","Ο","Π","Θ","Ρ","Σ","Τ","Ω","Ψ");
        //$el = array("Α","Β","Ξ","Δ","Ε","Γ","Η","Ι","Κ","Λ","Μ","Ν","Χ","Ο","Π","Θ","Ρ","Σ","Τ","Υ","Ω","Φ","Ο","Ψ","Ζ");
        $string = str_replace($en, $el, $string);

        //  5. translit lower case letters
        $en = array("a","b","v","c","g","d","e","f","h","z","i","k","l","m","n","x","o","p","q","r","s","t","y","u","w","ō","y","ī");
        $el = array("α","β","β","ξ","γ","δ","ε","φ","η","ζ","ι","κ","λ","μ","ν","ξ","ο","π","θ","ρ","σ","τ","υ","υ","ω","ω","ψ","η");
        //$el = array("α","β","ξ","δ","ε","γ","η","ι","κ","λ","μ","ν","ο","π","θ","ρ","σ","τ","υ","φ","ω","χ","ψ","ζ");
        $string = str_replace($en, $el, $string);

        $string = str_replace("<uλ>", "<ul>", $string);
        $string = str_replace("<λι>. ς ς", "<li>. . .", $string);
        $string = str_replace("<στρονγ>. ς ς", "<strong>. . .", $string);
        $string = str_replace("</στρονγ>. ς ς", "</strong>. . .", $string);
        $string = str_replace("<λι>", "<li>", $string);
        $string = str_replace("<στρονγ>", "<strong>", $string);
        $string = str_replace("ς ς .", " . . .", $string);
        $string = str_replace("ς ς ς </λι>", " . . .</li>", $string);
        $string = str_replace("</στρονγ>", "</strong>", $string);
        $string = str_replace("</λι>", "</li>", $string);
        $string = str_replace("</uλ>", "</ul>", $string);
        return $string;
    }

    //  replace UNICODE charset with MySQL equivalent
    function conv_mysqli($string) {
        $get = array(
        "utf-8",
        "big-5",
        "iso-8859-1",
        "iso-8859-2",
        "iso-8859-7",
        "ISO-8859-8",
        "ISO-8859-9",
        "iso-8859-13",
        "koi8-r",
        "koi8-u",
        "iso-646-se",
        "us-ascii",
        "euc-jp",
        "shift-jis",
        "cp-1251",
        "euc_kr",
        "gb-2312",
        "windows-1250",
        "ucs-2",
        "cp-852",
        "cp-866",
        "cp-1256",
        "cp-932",
        "euc-jp"
        );
        $out = array(
        "utf8",
        "big5",
        "latin1",
        "latin2",
        "greek",
        "hebrew",
        "latin7",
        "latin5",
        "koi8r",
        "koi8u",
        "swe7",
        "ascii",
        "ujis",
        "sjis",
        "cp1251",
        "euckr",
        "gb2312",
        "cp1250",
        "ucs2",
        "cp852",
        "cp866",
        "cp1256",
        "cp932",
        "eucjpms"
        );
        $mysqli = str_ireplace($get, $out, $string);

        if ($mysqli == $string) {
            $mysqli = "utf8";
        }
        return $mysqli;
    }

    function remove_emoji($text){
        return preg_replace('/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }

    function remove_acc($string, $wild) {
        global $type;

        if($string && $string != " " && $string != "   " && !strpos($string, "a3")){
            $replacement_a = array(
                "a;"=>"a",
                "Ã "=>"a",
                "Ã¢"=>"a",
                "å"=>"a",
                "â"=>"a",
                "ÃƒÂ¤"=>"a",
                "Ã¤"=>"a",
                "Ãƒ\"ž"=>"A",
                "Ã„"=>"A",
                "Ä"=>"A",
                "ä"=>"a",
                "Ã¡"=>"a",
                "à"=>"a",
                "&agrave;"=>"a",
                "á"=>"a",
                "&aacute;"=>"a",
                "À"=>"A",
                "&Agrave;"=>"A",
                "Á"=>"A",
                "&Aacute;"=>"A",
                "ă"=>"a",
                "Ă"=>"A",
                "Ą"=>"A",
                "ą"=>"a",
            );
            foreach($replacement_a as $i => $u) {
                $string = str_ireplace($i,$u,$string);
            }

            $replacement_c = array(
                "č"=>"c",
                "ç"=>"c",
                "Ã§"=>"c",
                "&ccedil;"=>"C",
                "&Ccedil;"=>"C",
                "Č"
            );
            foreach($replacement_c as $i => $u) {
                $string = str_ireplace($i,$u,$string);
            }

            $string = str_ireplace("ď", "d", $string);

            $replacement_e = array(
                "ě"=>"e",
                "Ãª"=>"e",
                "Ã¨"=>"e",
                "ê"=>"e",
                "Ã©"=>"e",
                "è"=>"e",
                "&egrave;"=>"e",
                "é"=>"e",
                "&eacute;"=>"e",
                "È"=>"E",
                "&Egrave;"=>"E",
                "É"=>"E",
                "&Eacute;"=>"E",
                "Ãˆ"=>"E",
                "Ã‰"=>"E",
                "Ě"=>"E",
                "Ę"=>"E",
                "ę"=>"e",
            );
            foreach($replacement_e as $i => $u) {
                $string = str_ireplace($i,$u,$string);
            }

            $replacement_i = array(
                "î"=>"i",
                "ì"=>"i",
                "&igrave;"=>"i",
                "í"=>"i",
                "&iacute;"=>"i",
                "&Igrave;"=>"I",
                "Í"=>"I",
                "&Iacute;"=>"I",
                "Ã±"=>"ñ",
                "Â¡"=>"¡",
                "Ã'"=>"Ñ",
                "Â¿"=>"¿",
            );   //   "Ì" removed, because  replaces the letter Ü => I
            foreach($replacement_i as $i => $u) {
                $string = str_ireplace($i, $u, $string);
            }

            $string = str_ireplace("Ĺ", "L", $string);

            $replacement_n = array(
                "Ñ"=>"N",
                "ñ"=>"n",
                "Ň"=>"N",
                "ň"=>"n",
            );
            foreach($replacement_n as $i => $u) {
                $string = str_ireplace($i ,$u, $string);
            }

            $replacement_o = array(
                "Ã´"=>"o",
                "ø"=>"o",
                "Ø"=>"O",
                "ô"=>"o",
                "ó"=>"o",
                "ò"=>"o",
                "õ"=>"o",
                "Ã–"=>"O",
                "ÃƒÂ¶"=>"o",
                "Ã¶"=>"o",
                "ã¶"=>"o",
                "ö"=>"o",
                "Ã³"=>"O",
                "ò"=>"o",
                "&ograve;"=>"O",
                "ó"=>"o",
                "&oacute;"=>"o",
                "Ò"=>"O",
                "&Ograve;"=>"O",
                "Ó"=>"O",
                "&Oacute;"=>"O",
                "Ö"=>"O",
            );
            foreach($replacement_o as $i => $u) {
                $string = str_ireplace($i ,$u, $string);
            }

            $replacement_r = array(
                "Ŕ"=>"R",
                "ŕ"=>"r",
                "Ř"=>"R",
                "ř"=>"r",
            );
            foreach($replacement_r as $i => $u) {
                $string = str_ireplace($i ,$u, $string);
            }

            $replacement_s = array(
                "Ș"=>"S",
                "ș"=>"s",
                "Ş"=>"S",
                "ş"=>"s",
                "Š"=>"S",
                "š"=>"s",
            );
            foreach($replacement_s as $i => $u) {
                $string = str_ireplace($i ,$u, $string);
            }

            $replacement_t = array(
                "Ț"=>"T",
                "ț"=>"t",
                "Ţ"=>"T",
                "ţ"=>"t",
                "ť"=>"t",
            );
            foreach($replacement_t as $i => $u) {
                $string = str_ireplace($i ,$u, $string);
            }

            $replacement_u = array(
                "Âœ"=>"u",
                "Ã»"=>"u",
                "ù"=>"u",
                "ú"=>"u",
                "û"=>"u",
                "ÃƒÂ¼"=>"u",
                "Ã¼"=>"u",
                "ÃƒÅ\“"=>"U",
                "Ãœ"=>"U",
                "Ü"=>"U",
                "ü"=>"u",
                "Ãº"=>"u",
                "ù"=>"u",
                "&ugrave;"=>"u",
                "ú"=>"u",
                "&uacute;"=>"u",
                "Ù"=>"U",
                "&Ugrave;"=>"U",
                "Ú"=>"U",
                "&Uacute;"=>"U",
                "Ů"=>"U",
                "ů"=>"u",
            );
            foreach($replacement_u as $i => $u) {
                $string = str_ireplace($i ,$u, $string);
            }

            $replacement_y = array(
                "Ý"=>"Y",
                "ý"=>"Y",
            );
            foreach($replacement_y as $i => $u) {
                $string = str_ireplace($i ,$u, $string);
            }

            $replacement_z = array(
                "Ž"=>"Z",
                "ž"=>"z",
            );
            foreach($replacement_z as $i => $u) {
                $string = str_ireplace($i ,$u, $string);
            }


            if ($type == "tol" && !$wild){    //  make tolerant by replacing vowels with %
                $string = rep_latvowels($string);
            }
        }
        return $string;
    }

    //  replace Greek accents with their pure vowels
    function remove_acc_el($string, $wild) {
        global $type;

        $string = preg_replace('/α|ἀ|ἁ|ἂ|ἃ|ἄ|ἅ|ἆ|ἇ|ὰ|ά|ά|ᾀ|ᾁ|ᾂ|ᾃ|ᾄ|ᾅ|ᾆ|ᾇ|ᾰ|ᾱ|ᾲ|ᾳ|ᾴ|ᾶ|ᾷ|ά|ā/', "α", $string);
        $string = preg_replace('/Α|Ἀ|Ἁ|Ἂ|Ἃ|Ἄ|Ἅ|Ἆ|Ἇ|Ὰ|Ά|Ά|ᾈ|ᾉ|ᾊ|ᾋ|ᾌ|ᾍ|ᾎ|ᾏ|Ᾰ|Ᾱ|ᾼ|Ά/', "Α", $string);

        $string = preg_replace('/ε|ἐ|ἑ|ἒ|ἓ|ἔ|ἕ|ὲ|έ|έ|έ|ē/', "ε", $string);
        $string = preg_replace('/Ε|Ἐ|Ἑ|Ἒ|Ἓ|Ἔ|Ἕ|Ὲ|Έ|Έ|Έ/', "Ε", $string);

        $string = preg_replace('/η|ή|ἠ|ἡ|ἣ|ἣ|ἤ|ἥ|ἦ|ἧ|ὴ|ή|ή|ᾐ|ᾑ|ᾒ|ᾓ|ᾔ|ᾕ|ᾖ|ᾗ|ῂ|ῃ|ῄ|ῆ|ῇ/', "η", $string);
        $string = preg_replace('/Η|Ἠ|Ἡ|Ἢ|Ἣ|Ἤ|Ἥ|Ἦ|Ἧ|Ὴ|Ή|Ή|ᾘ|ᾙ|ᾚ|ᾛ|ᾜ|ᾝ|ᾞ|ᾞ|ῌ/', "Η", $string);

        $string = preg_replace('/ι|ἰ|ἱ|ἲ|ἳ|ἴ|ἵ|ἶ|ἷ|ὶ|ί|ί|ῐ|ῑ|ῖ|ϊ|ῒ|ΐ|ΐ|ῗ|ί|ΐ/', "ι", $string);
        $string = preg_replace('/Ἰ|Ἱ|Ἲ|Ἳ|Ἴ|Ἵ|Ἶ|Ἷ|Ὶ|Ί|Ί|Ῐ|Ῑ/', "Ἰ", $string);

        $string = preg_replace('/ω|ὠ|ὡ|ὢ|ὣ|ὤ|ὥ|ὦ|ὧ|ὼ|ώ|ώ|ᾠ|ᾡ|ᾢ|ᾣ|ᾤ|ᾥ|ᾦ|ᾧ|ῲ|ῳ|ῴ|ῶ|ῷ|ώ/', "ω", $string);
        $string = preg_replace('/Ω|Ὠ|Ὡ|Ὢ|Ὣ|Ὤ|Ὥ|Ὦ|Ὧ|Ὼ|Ώ|Ώ|ᾨ|ᾩ|ᾪ|ᾫ|ᾬ|ᾭ|ᾮ|ᾯ|ῼ/', "Ω", $string);

        $string = preg_replace('/ο|ὀ|ὁ|ὂ|ὃ|ὄ|ὅ|ὸ|ό|ό|ό|ò|ô|ō/', "ο", $string);
        $string = preg_replace('/Ο|Ὀ|Ὁ|Ὂ|Ὃ|Ὄ|Ὅ|Ὸ|Ό|Ό/', "Ο", $string);

        $string = preg_replace('/υ|ὐ|ὑ|ὒ|ὓ|ὔ|ὕ|ὖ|ὗ|ὺ|ύ|ύ|ῦ|ῠ|ῡ|ϋ|ῢ|ΰ|ΰ|ῧ|ύ/', "υ", $string);
        $string = preg_replace('/Υ|Ὑ|Ὓ|Ὕ|Ὗ|Ὺ|Ύ |Ύ|Ῠ|Ῡ/', "Υ", $string);

        $string = preg_replace('/ρ|ῤ|ῥ/', "ρ", $string);
        $string = preg_replace('/Ρ|Ῥ/', "Ρ", $string);

        if ($type == "tol" && !$wild){    //  make tolerant by replacing vowels with %
            $string = rep_elvowels($string);
        }

        return $string;
    }

    //	replace Latin vowels with a (MySQL) wildcard
    function rep_latvowels($string) {
        $get = array("a", "c", "e", "i", "o", "u");
        $out = array("%", "%", "%", "%", "%", "%");
        $string = str_ireplace($get, $out, $string);
        return $string;
    }

    //  replace Greek vowels with a (MySQL) wildcard
    function rep_elvowels($string) {
        $get = array("α", "ε", "η", "ι", "ω", "ο", "υ", "υ");
        $out = array("%", "%", "%", "%", "%", "%", "%", "%");
        $string = str_ireplace($get, $out, $string);
        return $string;
    }

    class webshots {

        private $api_url;
        private $profile_secret_code;
        private $profile_secret_key;

        function __construct() {
            global $shot_code, $shot_key;

            $this->api_url              = 'http://pls-e.in/api/webshots';
            $this->profile_secret_code  = $shot_code;   // user profile secret code
            $this->profile_secret_key   = $shot_key;    // user profile secret key
        }

        function post_to_url($url, $data=array()){
            $fields = http_build_query($data);
            $c      = curl_init();
            curl_setopt($c, CURLOPT_URL, $url);
            curl_setopt($c, CURLOPT_POST, count($data));
            curl_setopt($c, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            @ob_flush();
            @flush();
            set_time_limit(60);     //  'time out' (seconds) for web shot. Don't like to wait for ever
            $result     = curl_exec($c);
            curl_close($c);
            return $result;
        }

        function url_to_image($webpage_url){
            $url = $this->api_url."/?t=".time();
            $params = array(
                                'ui' => array('sec_code'=>$this->profile_secret_code, 'key'=>$this->profile_secret_key),
                                'params' => array('url'=>$webpage_url, 'fullpage'=>'n', 'trim'=>'n', 'height'=>'130', 'width'=>'174', 'cropTop'=>'y')
                            );

            $img = $this->post_to_url($url, $params);
            return $img;
        }
    }

    function get_emails($file) {

        $emailList  = array();
        $pattern    = '/[^@\s]*@[^@\s]*\.[^@\s]*/';
        preg_match_all($pattern, $file, $matches);
        if(count($matches) != 0) {
            foreach($matches[0] as $item){
                array_push($emailList, $item);
            }
        }
        return array_unique($emailList);
    }

	function report($uri, $server_name, $server_addr, $client_ip, $client_host, $request_uri, $range, $value,
					$enc_range_low, $enc_range_high, $enc_client_ip, $message, $client_ua, $cc_co) {
		global $send_report, $plus_nr, $log_dir, $admin_email, $dispatch_email;

		$source	= "Sphider-plus v.$plus_nr";		//	presented in mail report
		$date 	= date("d.m.Y");
		$time 	= date("H:i:s");
		$query 	= "-";

		if (strlen(trim($request_uri))> 1) {
			$query = substr($request_uri, strpos($request_uri, "=")+1);
			$query = substr($query, 0, strpos($query, "&"));
			$query = rawurldecode($query);	//	make it readable
		}

//		$errorMessage = error_get_last()['message'];

		$header	= "from:Sphider-plus report\r\n";
		$header .= "Reply-To: $dispatch_email\r\n";
		$ref	= "Sphider-plus report";

		$msg  = "Message from Sphider-plus admin\nDate: $date\nTime: $time o'clock.\n";
		$msg .= "\n";
		$msg .= "message source: $source\n";
		$msg .= "message       : $message\n";
		$msg .= "\n";
		$msg .= "__file__    : $uri\n";
		$msg .= "request_uri : $request_uri\n";
		$msg .= "query       : $query\n";
//		$msg .= "last_error_message: $errorMessage\n";
		$msg .= "\n";
		$msg .= "server name : $server_name\n";
 		$msg .= "server_addr : $server_addr\n";
		$msg .= "client_ip   : $client_ip\n";
		$msg .= "client_host : $client_host\n";
		$msg .= "client_agent: $client_ua\n";
		$msg .= "client_cc   : $cc_co\n";
		$msg .= "\n";

		if(strpos($message, "(1)")) {
			$msg .= "client_ip   : $client_ip\n";
			$msg .= "blocked ip  : $value\n";
		}

		if(strpos($message, "(2)")) {
			$msg .= "client_ip   : $client_ip\n";
			$msg .= "range array : $range[0]-$range[1]\n";
			$msg .= "value string: $value\n";
			$msg .= "enc_range_low : $enc_range_low\n";
			$msg .= "enc_range_high: $enc_range_high\n";
			$msg .= "enc_client_ip : $enc_client_ip\n";
		}
		$msg .= "[EOF]\n";

		if($send_report == 1){
			//	now send report
			$success = mail($admin_email, $ref, $msg, $header);
			if (!$success) {
				//$errorMessage = error_get_last()['message'];
				$errorMessage = "No error report because of function mail() failed";
				echo "\r\n\r\n<br /> error message: '$errorMessage'<br />\r\n";
			}
		}
		//  if not exists, create a log folder and the file,
		//	which will contain all report messages
		$msg .= "\n ------------------------------------- \n\n";

		if (!is_dir("$log_dir")) {
			@mkdir("$log_dir");
		}
		//	write current report into log file at .../admin/log/
		$fp = @fopen("$log_dir/report_log.txt","a+");
		@fwrite($fp, $msg);
		@fclose($fp);

		return;
	}

    function retMktimest($dbdate) {
       return mktime(substr($dbdate, 11, 2), substr($dbdate, 14, 2), substr($dbdate, 17, 2), substr($dbdate, 5, 2), substr($dbdate, 8, 2), substr($dbdate, 0, 4));
    }

    function clear_folder($folder) {
        //  delete all thumbnails
        if ($dh = opendir("$folder/")) {
            while (($this_file = readdir($dh)) !== false) {
                @unlink("$folder/$this_file");
            }
            closedir($dh);
        }
        return;
    }

	function ipgeo($ip) {
		$all = array() ;
		$all =(unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip='.$ip)));
//$line = __LINE__;echo "\r\n<br>$line all Array:<br><pre>";print_r($all);echo "</pre>";
		return $all;
	}


    function getStatistics() {
        global $db_con, $mysql_table_prefix, $debug;

        $stats = array();
        $stats['index'] = '';

        $keywordQuery       = "SELECT count(keyword_id) from ".$mysql_table_prefix."keywords";
        $linksQuery         = "SELECT count(url) from ".$mysql_table_prefix."links";
        $siteQuery          = "SELECT count(site_id) from ".$mysql_table_prefix."sites";
        $categoriesQuery    = "SELECT count(category_id) from ".$mysql_table_prefix."categories";
        $mediaQuery         = "SELECT count(media_id) from ".$mysql_table_prefix."media";

        $result = $db_con->query($keywordQuery);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $keywordQuery, $file, $function, $err_row);
        }
        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['keywords']=$row[0];
        }
        $result = $db_con->query($linksQuery);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $linksQuery, $file, $function, $err_row);
            }
        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['links']=$row[0];
        }
        for ($i=0;$i<=15; $i++) {
            $char = dechex($i);
            $sql_query1 = "SELECT count(link_id) from ".$mysql_table_prefix."link_keyword$char";
            $result = $db_con->query ($sql_query1);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-5;
                mysql_fault($db_con, $sql_query1, $file, $function, $err_row);
            }
            if ($row = $result->fetch_array(MYSQLI_NUM)) {
                $stats['index']+=$row[0];
            }
        }
        $result = $db_con->query($siteQuery);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-4;
            mysql_fault($db_con, $siteQuery, $file, $function, $err_row);
        }
        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['sites']=$row[0];
        }
        $result = $db_con->query($categoriesQuery);
            if ($debug && $db_con->errno) {
                $file       = __FILE__ ;
                $function   = __FUNCTION__ ;
                $err_row    = __LINE__-4;
                mysql_fault($db_con, $CategoriesQuery, $file, $function, $err_row);
            }
        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['categories']=$row[0];
        }
        $result = $db_con->query($mediaQuery);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-4;
            mysql_fault($db_con, $mediaQuery, $file, $function, $err_row);
        }
        if ($row = $result->fetch_array(MYSQLI_NUM)) {
            $stats['media']=$row[0];
        }

        return $stats;
    }

    function stem_word($word, $type) {
        global $debug, $stem_words, $stem_dir, $min_word_length, $common;

        //if ($debug == '2') echo "\r\n\r\n<br /> unstemmed: $word<br />\r\n";
        //  no stemming for too short words or words containing some special characters
        if (!is_string($word) || strlen($word) < $min_word_length || preg_match("/[\*\!:]|[0-9]/si", $word)) {
            return $word;
        }

        if ($stem_words == 'bg') {
            require_once "$stem_dir/bg_stem.php";
            $word1 = bg_stemmer::stem($word);
        }

        if ($stem_words == 'cz') {
            require_once "$stem_dir/cz_stem.php";
            $word1 = cz_stemmer::stem($word);
        }

        if ($stem_words == 'de') {
            require_once "$stem_dir/de_stem.php";
            $word1 = de_stemmer::stem($word);
        }

        if ($stem_words == 'el') {
            require_once "$stem_dir/el_stem.php";
            $stemmer = new el_stemmer();
            $word1 = $stemmer->stem($word);
        }

        if ($stem_words == 'en') {
            require_once "$stem_dir/en_stem.php";
            $word1 = en_stemmer::stem($word);
        }

        if ($stem_words == 'es') {
            require_once "$stem_dir/es_stem.php";
            $word1 = es_stemmer::stem($word);
        }

        if ($stem_words == 'fi') {
            require_once "$stem_dir/fi_stem.php";
            $word1 = fi_stemmer::stem($word);
        }

        if ($stem_words == 'fr') {
            require_once "$stem_dir/fr_stem.php";
            $word1 = fr_stemmer::stem($word);
        }

        if ($stem_words == 'hu') {
            require_once "$stem_dir/hu_stem.php";
            $word1 = hu_stemmer::stem($word);
        }

        if ($stem_words == 'nl') {
            require_once "$stem_dir/nl_stem.php";
            $word1 = nl_stemmer::stem($word);
        }

        if ($stem_words == 'it') {
            require_once "$stem_dir/it_stem.php";
            $stemmer = new it_stemmer();
            $word1 = $stemmer->stem($word);
        }

        if ($stem_words == 'pt') {
            require_once "$stem_dir/pt_stem.php";
            $word1 = pt_stemmer::stem($word);
        }

        if ($stem_words == 'ru') {
            require_once "$stem_dir/ru_stem.php";
            $word1 = ru_stemmer::stem($word);
        }

        if ($stem_words == 'se') {
            require_once "$stem_dir/se_stem.php";
            $word1 = se_stemmer::stem($word);
        }

        //  Hopefully the stemmed word did not become too short
        //  and the stemming algorithm did not create a common word
        if (strlen($word1) > $min_word_length && $common[$word1] != 1) {
            $word = $word1;
        }

        //if ($debug == '2') echo "\r\n\r\n<br /> &nbsp;&nbsp;&nbsp;stemmed: $word<br />\r\n";
        return $word;

    }

    function optimize($flush) {
        global $debug, $db_con, $mysql_table_prefix, $debug, $clear;

        //	evtl. overwrite the global variable '$clear'
        if ($flush == '1') {
        	$clear = '1';
        }

        $sql_query = "SHOW TABLE STATUS LIKE '$mysql_table_prefix%'";
        $result = $db_con->query($sql_query);
        if ($debug && $db_con->errno) {
            $file       = __FILE__ ;
            $function   = __FUNCTION__ ;
            $err_row    = __LINE__-5;
            mysql_fault($db_con, $sql_query, $file, $function, $err_row);
        }
        set_time_limit(1800);   //      increase timeout
        $i      = 0;
        $res    = '';
        if (!$clear) {
            $res    = '1';
        }
        if ($result->num_rows) {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $db_con->query("CHECK TABLE $row[0]") or die("<body onload='JumpBottom()'><br /><center><span class='warn bd'>Unable to check table '$row[0]'.</span><br /><br /></center>\n</body>\n</html>");
                $db_con->query("REPAIR TABLE $row[0]") or die("<body onload='JumpBottom()'><br /><center><span class='warn bd'>Unable to repair table '$row[0]'.</span><br /><br /></center>\n</body>\n</html>");
                $db_con->query("OPTIMIZE TABLE $row[0]") or die("<body onload='JumpBottom()'><br /><center><span class='warn bd'>Unable to optimize table '$row[0]'.</span><br /><br /></center>\n</body>\n</html>");
                if ($clear == "1") {
                    $res = $db_con->query("FLUSH TABLE $row[0]");
                }
                $i++;
            }
        }
        if (!$res) {    //  if FLUSH TABLE was not accepted
            echo "
            <br /><center><span class='warnadmin cntr'><strong>Attention:</strong> Unable to flush all database tables.
            <br /><br />
            Because of missing MySQL rights, database repair could not be completed.
            <br />
            Table 'FLUSH' usually is forbidden on Shared Hosting servers.</span>
            <br /><br /><br />
        ";
        }
        return($i);
    }

    function toHours($sec){
        $hours = floor($sec / 3600);
        $min = floor(($sec - ($hours * 3600)) / 60);
        $sec = round($sec - ($hours * 3600) - ($min * 60), 0);

        if ($hours <= 9) {
            $strhours = "0" . $hours;
        } else {
            $strhours = $hours;
        }

        if ($min <= 9) {
            $strmin = "0" . $min;
        } else {
            $strmin = $min;
        }

        if ($sec <= 9) {
            $strsec = "0" . $sec;
        } else {
            $strsec = $sec;
        }

        return "$strhours:$strmin:$strsec";
    }

    function mysql_fault($db_con, $sql_query, $file, $function, $err_row) {

        printf("<p><span class='red'>&nbsp;MySQL failure:</span><strong> %s&nbsp;</strong><br />\n</p>", $db_con->error);
        printf("<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Invalid query string, which caused the SQL error:</p>\n");
        echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>$sql_query</strong></p>\n";
        printf("<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Found in script: ".$file."</p>\n");
        if ($function) {
            printf("<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;function:&nbsp;".$function."()</p>\n");
        }
        printf("<p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;row: $err_row&nbsp;<br /></p>\n");
        printf("<p><span class='red'>&nbsp;Script execution aborted.&nbsp;<br /></span>\n");

        exit;
    }

    // Database1-5 connection
    function db_connect($mysql_host, $mysql_user, $mysql_password, $database) {

        $db_con = new mysqli($mysql_host, $mysql_user, $mysql_password, $database);
        /* check connection */
        if ($db_con->connect_errno) {
            printf("<p><span class='red'>&nbsp;MySQL Connect failed: %s\n&nbsp;<br /></span></p>", $db_con->connect_error);

        }

        if (!$db_con->connect_errno) {
            /* define character set to utf8 */
            if (!$db_con->set_charset("utf8")) {
                printf("Error loading character set utf8: %s\n", $db_con->error);

                /* Print current character set */
                $charset = $db_con->character_set_name();
                printf ("<br />Current character set is: %s\n", $charset);

                $db_con->close();
                exit;
            }
        }

        return ($db_con);
    }

/*
	//	https://stackoverflow.com/questions/1441562/detect-language-from-string-in-php
	//
	//	see also 	https://github.com/patrickschur/language-detection
	//	for 110 languages detection
	function identifyLanguage($string) {

        $ru = array("208","209","208176","208177","208178","208179","208180","208181","209145","208182","208183","208184","208185","208186","208187","208188","208189","208190","208191","209128","209129","209130","209131","209132","209133","209134","209135","209136","209137","209138","209139","209140","209141","209142","209143");
        $en = array("97","98","99","100","101","102","103","104","105","106","107","108","109","110","111","112","113","114","115","116","117","118","119","120","121","122");
        $htmlcharacters = array("<", ">", "&amp;", "&lt;", "&gt;", "&");
        $string = str_replace($htmlcharacters, "", $string);
        //Strip out the slashes
        $string = stripslashes($string);
        $badthings = array("=", "#", "~", "!", "?", ".", ",", "<", ">", "/", ";", ":", '"', "'", "[", "]", "{", "}", "@", "$", "%", "^", "&", "*", "(", ")", "-", "_", "+", "|", "`");
        $string = str_replace($badthings, "", $string);
        $string = mb_strtolower($string);
        $msgarray = explode(" ", $string);
        $words = count($msgarray);
        $letters = str_split($msgarray[0]);
        $letters = cleanEncoding($letters[0]);
        $brackets = array("[",",","]");
        $letters = str_replace($brackets,  "", $letters);
        if (in_array($letters, $ru)) {
            $result = 'rus' ; //russian
        } elseif (in_array($letters, $en)) {
            $result = 'eng'; //english
        } else {
            $result = 'else' . $letters; //error
        }
		return $result;
	}

	//	Google Cloud API
	function identifyLanguage ($str) {

		$str = str_replace(" ", "%20", $str);
		$content = file_get_contents("https://translation.googleapis.com/language/translate/v2/detect?key=Your_INDIVIDUAL_GOOGLE_API&q=".$str);
		$lang = (json_decode($content, true));

		if(isset($lang))
			return $lang["data"]["detections"][0][0]["language"];
	}
*/
?>