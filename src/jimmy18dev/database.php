<?php
namespace jimmy18dev\PdoConnect;

use PDO;

class PdoConnect{
    public $dbh;
    private $error;
    private $stmt;

    public function __construct($config){
        // Set DSN
        $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'];
        // Set options
        $options = array(
            PDO::ATTR_PERSISTENT    => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        );
        // Create a new PDO instanace
        try{
            $this->dbh = new PDO($dsn, $config['user'], $config['pass'], $options);
        }
        // Catch any errors
        catch(PDOException $e){
            $this->error = $e->getMessage();
        }
    }

    public function query($query){
    	$this->stmt = $this->dbh->prepare($query);
	}

	public function bind($param, $value, $type = null){
    	if (is_null($type)) {
        	switch (true) {
            	case is_int($value):
                	$type = PDO::PARAM_INT;
                	break;
            	case is_bool($value):
                	$type = PDO::PARAM_BOOL;
                	break;
            	case is_null($value):
                	$type = PDO::PARAM_NULL;
                	break;
            	default:
                	$type = PDO::PARAM_STR;
        	}
    	}
    	$this->stmt->bindValue($param, $value, $type);
	}
	public function execute(){
    	return $this->stmt->execute();
	}
	public function resultset(){
    	$this->execute();
    	return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	public function single(){
    	$this->execute();
    	return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}
	public function rowCount(){
    	return $this->stmt->rowCount();
	}
	public function lastInsertId(){
    	return $this->dbh->lastInsertId();
	}
	public function beginTransaction(){
    	return $this->dbh->beginTransaction();
	}
	public function endTransaction(){
    	return $this->dbh->commit();
	}
	public function cancelTransaction(){
    	return $this->dbh->rollBack();
	}
	public function debugDumpParams(){
    	return $this->stmt->debugDumpParams();
	}

    //Find Real IP address.
    public function GetIpAddress(){
        //check ip from share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //to check ip is pass from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    public function string_cleaner($string,$option = NULL){
        if($option == 'body'){
            $string = trim(preg_replace('/\h+/',' ',$string));
        }else{
            $string = str_replace("\n","", $string);
            $string = trim(preg_replace('/\s\s+/',' ',$string));
        }

        $string = filter_var($string, FILTER_SANITIZE_STRING);
        return $string;
    }

    // Text, Message to URL Friendly
    public function urlFriendly($data){
        $data = preg_replace('#[^-ก-๙a-zA-Z0-9]#u','-', $data);

        if(substr($data,0,1) == '-')
            $data = substr($data,1);
        if(substr($data,-1) == '-')
            $data = substr($data,0,-1);

        $data = urldecode($data);
        $data = str_replace(array('   ','  ',' '),array('-','-','-'),$data);
        $data = str_replace(array('---','--'),array('-','-'),$data);
        
        //return rawurlencode($data);
        return ($data);
    }

    public function formatBytes($size,$precision = 1){
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');   
        return round(pow(1024, $base - floor($base)), $precision).''.$suffixes[floor($base)];
    }

    public function datetimeformat($datetime,$option = 'shortdate'){

        if(empty($datetime)) return null;

        $timestamp  = strtotime($datetime);
        $diff       = time() - $timestamp;
        $hour       = date('H',strtotime($datetime));
        $minute     = date("i",strtotime($datetime));
        $year       = date('Y',strtotime($datetime));
        $month      = date('n',strtotime($datetime));
        $date       = date('j',strtotime($datetime));

        $monthText = array('ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.');
        $monthFullText = array('มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม');

        switch ($option) {
            case 'timestamp':
                $str = $timestamp;
                break;
            case 'fulldatetime':
                $str = $date.' '.$monthFullText[$month-1].' '.($year+543).' เวลา '.$hour.':'.$minute.' น.';
                break;
            case 'fulldate':
                $str = $date.' '.$monthFullText[$month-1].' '.($year+543);
                break;
            case 'shortdatetime':
                $str = $date.' '.$monthText[$month-1].' '.($year+543).' เวลา '.$hour.':'.$minute.' น.';
                break;
            case 'shortdate':
                $str = $date.' '.$monthText[$month-1].' '.($year+543);
                break;
            case 'topicdate':
                $diff = time() - $timestamp;
                if($diff < 86400){
                    $str = 'วันนี้';
                }else if($diff < (86400*2)){
                    $str = 'เมื่อวานนี้';
                }else{
                    if($year == date('Y')){
                        $str = $date.' '.$monthFullText[$month-1];
                    }else{
                        $str = $date.' '.$monthFullText[$month-1].' '.($year+543);
                    }
                }
                break;
            case 'facebook':
                $diff       = time() - $timestamp;
                $periods    = array('วินาที','นาที','ชั่วโมง');
                $words      = 'ที่แล้ว';

                if($diff < 10){
                    $text   = "เมื่อสักครู่";
                }
                else if($diff < 60){
                    $i      = 0;
                    $diff   = ($diff == 1)?"":$diff;
                    $text   = "$diff $periods[$i]$words";
                }
                else if($diff < 3600){
                    $i      = 1;
                    $diff   = round($diff/60);
                    // $diff   = ($diff == 3 || $diff == 4)?"":$diff;
                    $text   = "$diff $periods[$i]$words";
                }
                else if($diff < 86400){
                    // 1 Day
                    $i      = 2;
                    $diff   = round($diff/3600);
                    $diff   = ($diff != 1)?$diff:"" . $diff ;
                    $text   = "$diff $periods[$i]$words";
                }
                else if($diff < 432000){
                    // 5 Day
                    $diff   = round($diff/86400);
                    $text   = $diff.' วันที่แล้ว';
                }
                else{
                    $monthText = array('ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.');

                    $date   = date("j", $timestamp);
                    $month  = $monthText[date("m", $timestamp)-1];
                    $y      = (date("Y", $timestamp)+543)-2500;
                    $t1     = "$date  $month";
                    $t2     = "$date  $month  $y";

                    // if($timestamp < strtotime(date("Y-01-01 00:00:00"))){
                    //     $text = $t2;
                    // }
                    // else{
                    //     $text = $t1;
                    // }

                    $text = $t2;
                }
                $str = $text;

                break;
            default:
                break;
        }

        return $str;
    }
}
?>
