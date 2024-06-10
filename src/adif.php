<?php
namespace Adif;
/**
 * ADIFインポートクラス
 * 
 * ADIFデータを解析して、配列に展開する。
 *
 * PHP versions 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package       php-adif
 * @version       0.1
 * @since         0.1
 * @author        Mune Ando
 * @copyright     Copyright 2012, Mune Ando (http://wwww.5cho-me.com/)
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class adif {
    private $data;
	private $records = [];
    private $options = [
        'code'	=> 'sjis-win',
    ];

    public function __construct($data, $options=[]) {
		$this->options = array_merge($this->options, $options);
		if (in_array(pathinfo($data, PATHINFO_EXTENSION), array('adi', 'adif'))) {
			$this->loadFile($data);
		} else {
			$this->loadData($data);
		}
		$this->initialize();
	}

	protected function initialize() {
		$pos = strripos($this->data, '<EOH>');
		if($pos === false) {
			throw new Exception('<EOH> is not present in the ADFI file');
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
		$this->records = explode('<EOR>', $records);
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
							$i = $i + $valueLen;
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
