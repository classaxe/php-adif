<?php
use Adif\adif;
use PHPUnit\Framework\TestCase;
class adifTest extends TestCase {
	public function testSimpleData() {

$data = <<<FOO
test test test
<EOH>
<TAG1:6>value1<TAG2:7>value23<TAG3:8>value456<EOR>
FOO;
		$adif = new adif($data);
		$data = $adif->parser();
		$expected = array(
				array(
						'TAG1' => 'value1',
						'TAG2' => 'value23',
						'TAG3' => 'value456',
				),
		);
		$this->assertEquals($adif->parser(), $expected);
	}
	
	public function testUpperLowerTag() {

$data = <<<FOO
test test test
<EOH>
#test
<TAG1:6>value1<TAG2:6>value2<tag3:6>value3<Eor>
#test
<TAG_1:6>value4<TAG-2:6>value5<TAG3:6>value6<EOR>
FOO;
		$adif = new adif($data);
		$data = $adif->parser();
		$expected = array(
				array(
						'TAG1' => 'value1',
						'TAG2' => 'value2',
						'TAG3' => 'value3',
				),
				array(
						'TAG_1' => 'value4',
						'TAG-2' => 'value5',
						'TAG3' => 'value6',
				)
		);
		$this->assertEquals($adif->parser(), $expected);
	}
	
	public function testComment() {

$data = <<<FOO
test test test
<EOH>
#test
<TAG1:7>value#1<TAG2:6>value2<tag3:6>value3<Eor>
#test
#<TAG_1:6>value4<TAG-2:6>value5<TAG3:6>value6<EOR>
FOO;
		$adif = new adif($data);
		$expected = array(
				array(
						'TAG1' => 'value#1',
						'TAG2' => 'value2',
						'TAG3' => 'value3',
				),
		);
		$this->assertEquals($adif->parser(), $expected);
	}
		
	public function testJapaneseData() {

$data = <<<FOO
<EOH>
<TAG1:3>値1<TAG2:3>値2値<TAG3:3>値3<EOR>
<TAG1:3>値4<TAG2:3>値5<TAG3:3>値6<EOR>
FOO;
		$adif = new adif(mb_convert_encoding($data, 'sjis-win', 'utf-8'));
		$data = $adif->parser();
		$expected = array(
				array(
						'TAG1' => '値1',
						'TAG2' => '値2',
						'TAG3' => '値3',
				),
				array(
						'TAG1' => '値4',
						'TAG2' => '値5',
						'TAG3' => '値6',
				)
		);
		$this->assertEquals($adif->parser(), $expected);
	}
	
	public function testJapaneseData2() {
	
		$data = <<<FOO
<EOH>
<TAG1:2>値1<TAG2:2>値2<TAG3:2>値3<EOR>
<TAG1:2>値4<TAG2:2>値5<TAG3:2>値6値<EOR>
FOO;
		$adif = new adif($data, array('code' => 'utf-8'));
		$data = $adif->parser();
		$expected = array(
				array(
						'TAG1' => '値1',
						'TAG2' => '値2',
						'TAG3' => '値3',
				),
				array(
						'TAG1' => '値4',
						'TAG2' => '値5',
						'TAG3' => '値6',
				)
		);
		$this->assertEquals($adif->parser(), $expected);
	}
	
	
 	public function testRealData() {

$data = <<<FOO
Generated on 2011-11-22 at 02:15:23Z for WN4AZY
<adif_ver:6>3.0.0
<programid:7>monolog
<USERDEF1:14:N>EPC
<USERDEF2:19:S>SweaterSize,{S,M,L}
<USERDEF3:15:N>ShoeSize,{5:20}

<EOH>
 
<qso_date:8>19900620
<time_on:4>1523
<call:5>VK9NS
<band:3>20M
<mode:4>RTTY
<sweatersize:1>M
<shoesize:2>11
<app_monolog_compression:3>off
<eor>
<qso_date:8>20101022
<time_on:4>0111
<call:5>ON4UN
<band:3>40M
<mode:5>PSK63
<epc:5>32123
<app_monolog_compression:3>off
<eor>
FOO;
		$adif = new adif($data);
		$data = $adif->parser();
		$expected = array(
				array(
						'QSO_DATE' => '19900620',
						'TIME_ON' => '1523',
						'CALL' => 'VK9NS',
						'BAND' => '20M',
						'MODE' => 'RTTY',
						'SWEATERSIZE' => 'M',
						'SHOESIZE' => '11',
						'APP_MONOLOG_COMPRESSION' => 'off',
						),
				array(
						'QSO_DATE' => '20101022',
						'TIME_ON' => '0111',
						'CALL' => 'ON4UN',
						'BAND' => '40M',
						'MODE' => 'PSK63',
						'EPC' => '32123',
						'APP_MONOLOG_COMPRESSION' => 'off',
						)
				);
		$this->assertEquals($adif->parser(), $expected);
	}
	
	public function testDilimitter() {
	
		$data = <<<FOO
test test test
<EOH>
<TAG1:7>valu:e1<TAG2:7>va>lue2<TAG3:8>va<>lue3<EOR>
FOO;
		$adif = new adif($data);
		$data = $adif->parser();
		$expected = array(
				array(
						'TAG1' => 'valu:e1',
						'TAG2' => 'va>lue2',
						'TAG3' => 'va<>lue3',
				),
		);
		$this->assertEquals($adif->parser(), $expected);
	}
	
	public function testADIFFile() {
		$adif = new adif(__DIR__ . DIRECTORY_SEPARATOR . 'LOGLIST.adi');
		$data = $adif->parser();
		$expected = [
            [
                "CALL" => "JA1AAA",
                "QSO_DATE" => "20080619",
                "TIME_ON" => "1205",
                "FREQ" => "432.94",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "51",
                "QSL_SENT" => "*",
                "QSL_RCVD" => "N",
                "NAME" => "すぎうら",
                "QTH" => "東京都目黒区",
                "COMMENT" => "Rig#9<あ>",
            ],
            [
                "CALL" => "JA1BBB",
                "QSO_DATE" => "20080621",
                "TIME_ON" => "0450",
                "FREQ" => "432.98",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "53",
                "RST_RCVD" => "57",
                "QSL_SENT" => "*",
                "NAME" => "みぞぐち",
                "QTH" => "神奈川県横須賀市",
                "COMMENT" => "Rig#9",
            ],
            [
                "CALL" => "JA1CCC",
                "QSO_DATE" => "20080621",
                "TIME_ON" => "2327",
                "FREQ" => "433.44",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "59",
                "QSL_SENT" => "E",
                "QSL_RCVD" => "E",
                "NAME" => "こみや",
                "QTH" => "横浜市中区",
                "COMMENT" => "Rig#9",
            ],
            [
                "CALL" => "JA1DDD",
                "QSO_DATE" =>  "20080622",
                "TIME_ON" => "1540",
                "FREQ" => "433.40",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "51",
                "QSL_SENT" => "E",
                "QSL_RCVD" => "E",
                "NAME" => "小野",
                "QTH" => "東京都大田区",
                "COMMENT" => "Rig#9",
            ],
            [
                "CALL" => "JA1EEE",
                "QSO_DATE" => "20080630",
                "TIME_ON" => "1426",
                "FREQ" => "433.06",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "59",
                "QSL_SENT" => "*",
                "NAME" => "小林",
                "QTH" => "川崎市幸区",
                "COMMENT" => "Rig#9",
            ],
            [
                "CALL" => "JA1FFF",
                "QSO_DATE" => "20080701",
                "TIME_ON" => "1315",
                "FREQ" => "433.140",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "59",
                "QSL_SENT" => "*",
                "QSL_RCVD" => "*",
                "NAME" => "島崎",
                "QTH" => "横浜市港北区",
                "COMMENT" => "Rig#9",
            ],
            [
                "CALL" => "JA1GGG",
                "QSO_DATE" => "20080704",
                "TIME_ON" => "1159",
                "FREQ" => "433.040",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "59",
                "QSL_SENT" => "*",
                "QSL_RCVD" => "E",
                "NAME" => "小坂",
                "QTH" => "千葉県木更津市",
                "COMMENT" => "Rig#9",
            ],
            [
                "CALL" => "JA1HHH",
                "QSO_DATE" => "20080705",
                "TIME_ON" => "1002",
                "FREQ" => "432.840",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "55",
                "QSL_SENT" => "*",
                "QSL_RCVD" => "*",
                "QTH" => "静岡県伊豆市",
                "COMMENT" => "Rig#13",
            ],
            [
                "CALL" => "JA1III/1",
                "QSO_DATE" => "20080705",
                "TIME_ON" => "1009",
                "FREQ" => "432.96",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "59",
                "QSL_SENT" => "*",
                "QSL_RCVD" => "*",
                "NAME" => "くらた",
                "QTH" => "神奈川県横浜市",
                "COMMENT" => "Rig#13",
            ],
            [
                "CALL" => "JA1JJJ",
                "QSO_DATE" =>  "20080705",
                "TIME_ON" => "1032",
                "FREQ" => "433.200",
                "BAND" => "70cm",
                "MODE" => "FM",
                "RST_SENT" => "59",
                "RST_RCVD" => "59",
                "QSL_SENT" => "*",
                "QSL_RCVD" => "*",
                "NAME" => "かみしろ",
                "QTH" => "神奈川県横須賀市",
                "COMMENT" => "Rig#13",
            ],
        ];
		$this->assertEquals($data, $expected);
	}

}
?>