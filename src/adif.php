<?php
namespace Adif;
class adif {
/**
 * ADIFインポートクラス
 *
 * ADIFデータを解析して、配列に展開する。
 *
 * PHP versions 5-8
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package       php-adif
 * @version       0.1
 * @since         0.1
 * @author        Mune Ando
 * @author        Martin Francis, James Fraser - refinements, added toAdif()
 * @copyright     Copyright 2012, Mune Ando (http://wwww.5cho-me.com/)
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @repo          https://github.com/classaxe/php-adif
 */
    private $data;
    private $filename;
    private $records = [];
    private $options = [
        'code'	=> 'sjis-win',
    ];

    public function __construct($data, $options=[]) {
        $this->options = array_merge($this->options, $options);
        if (in_array(pathinfo($data, PATHINFO_EXTENSION), array('adi', 'adif'))) {
            $this->loadFile($data);
            $this->filename = pathinfo($data, PATHINFO_FILENAME);
        } else {
            $this->loadData($data);
        }
        $this->initialize();
    }

    protected function initialize() {
        $pos = strripos($this->data, '<EOH>');
        if ($pos === false) {
            $this->data = "<EOH>" . $this->data;
            $pos = 0;
        };
        $data = substr($this->data, $pos + 5, strlen($this->data) - $pos - 5);
        $data = str_replace(array("\r\n", "\r"), "\n", $data);
        $lines = explode("\n", $data);
        $data = '';
        foreach ($lines as $line) {
            if(substr(ltrim($line), 0, 1) != '#') {
                $data = $data . $line;
            }
        }
        $records = str_ireplace('<eor>', '<EOR>', $data);
        $tmp = explode('<EOR>', $records);
        $this->records = array_filter($tmp, function($record) {
            return $record != '';
        });
    }

    protected function loadData($data) {
        $this->data = $data;
    }

    protected function loadFile($fname) {
        $this->data = file_get_contents($fname);
    }

    public function parser() {
        $datas = [];
        foreach ($this->records as $record) {
            if(empty($record)) {
                continue;
            }
            $data = [];
            $tag = '';
            $valueLen = '';
            $value = '';
            $status = '';
            $i = 0;
            while($i < $this->strlen($record)) {
                $ch = $this->substr($record, $i, 1);
                $delimiter = FALSE;
                switch ($ch) {
                    case '\n':
                    case '\r':
                        continue 2;
                    case '<':
                        $tag = '';
                        $value = '';
                        $status = 'TAG';
                        $delimiter = TRUE;
                        break;
                    case ':':
                        if($status == 'TAG') {
                            $valueLen = '';
                            $status = 'VALUELEN';
                            $delimiter = TRUE;
                        }
                        break;
                    case '>':
                        if($status == 'VALUELEN') {
                            $value = $this->substr($record, $i+1, (int)$valueLen);
                            $data[strtoupper($tag)] = $this->convert_encoding($value);
                            $i = $i + (int)$valueLen;
                            $status = 'VALUE';
                            $delimiter = TRUE;
                        }
                        break;
                    default:
                }
                if($delimiter === FALSE) {
                    switch ($status) {
                        case 'TAG':
                            $tag .= $ch;
                            break;
                        case 'VALUELEN':
                            $valueLen .= $ch;
                            break;
                    }
                }
                $i = $i + 1;
            }
            $datas[] = $data;
        }
        return $datas;
    }

    public static function toAdif(
        $data, $name, $version, $raw = false, $allFields = false, $url=false, $copyright=false
    ) {
        $output = ($raw ? "" : "ADIF Export from $name\n"
            . ($url ? "$url\n" : "")
            . ($copyright ? "$copyright\n" : "")
            . "File generated on " . date('Y-m-d \a\t H:m:s') ."\n"
            . "<ADIF_VER:5>3.1.4\n"
            . "<PROGRAMID:" . strlen($name) . ">$name\n"
            . "<PROGRAMVERSION:" . strlen($version) . ">" . $version ."\n"
            . "<EOH>\n"
            . "\n"
        );
        foreach($data as $row) {
            foreach ($row as $key => $value) {
                if (!$value && !$allFields) {
                    continue;
                }
                $output .=  "<" . $key . ":" . mb_strlen($value) . ">" . $value . " ";
            }
            $output .= "<EOR>" . ($raw ? "" : "\r\n");
        }

        return $output;
    }

    protected function strlen($string) {
        if ($this->options['code'] == 'sjis-win') {
            return strlen($string);
        } else {
            return mb_strlen($string);
        }
    }

    protected function convert_encoding($string) {
        if ($this->options['code'] == 'sjis-win') {
            return mb_convert_encoding($string, 'utf-8', 'sjis-win');
        } else {
            return $string;
        }
    }

    protected function substr($string, $start, $length) {
        if ($this->options['code'] == 'sjis-win') {
            return substr($string, $start, $length);
        }
        return mb_substr($string, $start, $length, 'utf-8');
    }
}

