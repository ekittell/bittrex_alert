<?php

$BittrexAlerts = new BittrexAlerts();
$BittrexAlerts->run();

Class BittrexAlerts {
	private $sms = "2125551212@txt.att.net"; //check with your mobile carrier
	private $email = "your@email.com";
	private $identitifier = "BITTREX-ALERT"; // use to identify messages for smart mail boxes
	private $headers;

	private $alerts = array(
		//bitrrex
		"BTC-AERO" 		=> array(.00004200,.00005000),
		// c-cex
		"dcn-btc"		=> array(.00000200, .00000200)
	); 



	private $log = array();
	private $log_file;

	public function __construct() {
		// create log file with empty array
		$this->log_file = dirname(__FILE__) . "/alerts.log";

		if(!file_exists($this->log_file))
			file_put_contents($this->log_file, serialize(array())) or die("can't create file");

		$this->log = unserialize(file_get_contents($this->log_file));

		$this->headers = 'From: your@server.com' . "\r\n" .
	    		'X-Mailer: PHP/' . phpversion();



	}

	private function send_alert($subject, $message) {
		$subject = $this->identitifier . " : " . $subject;
		mail($this->sms, $subject, $message, $this->headers) or die('cannot send sms');
		mail($this->email, $subject, $message, $this->headers) or die('cannot send mail');
	}

	private function log_alert($alert_name, $repeat = true) {
		// log alert and return true if last alert was over an hour ago
		$limit = time() - (60 * 60);
		if(!isset($this->log[$alert_name]) || ($this->log[$alert_name] < $limit && $repeat)) {
			$this->log[$alert_name] = time();
			file_put_contents($this->log_file, serialize($this->log));
			return true;
		}

		return false;
	}

	public function run() {
		$markets = array();
		$lt_alerts = array();
		$gt_alerts = array();

		foreach($this->alerts as $market => $alert) {
			$markets[] = $market;
	    	$lt_alerts[$market] = $alert[0];
	    	$gt_alerts[$market] = $alert[1];
	    }


		$bittrex_summaries = json_decode(file_get_contents('https://bittrex.com/api/v1.1/public/getmarketsummaries')); // BITTREX API call

		foreach($bittrex_summaries->result as $summary) {
			// Check for new Markets
			$limit = time() - (60 * 60);
			if(strtotime($summary->Created) >= $limit) {
				echo $summary->Created . "<br>";
				$subject = "NEW market!";
				$message = $summary->MarketName;
				if($this->log_alert("NEW-".$summary->MarketName, false))
					$this->send_alert($subject, $message);
			}

			if(in_array($summary->MarketName, $markets)) {
				// Check if markets have droppped below alert points
				if($summary->Last <= $lt_alerts[$summary->MarketName]) {
					$subject = "market LOW!";
					$message = $summary->MarketName ." at " . number_format($summary->Last, 8);
					if($this->log_alert("LOW-".$summary->MarketName))
						$this->send_alert($subject, $message);
				}

				// Check if markets have gone above alert points
				if($summary->Last >= $gt_alerts[$summary->MarketName]) {
					$subject = "market HI!";
					$message = $summary->MarketName ." at " . number_format($summary->Last, 8);
					if($this->log_alert("HI-".$summary->MarketName))
						$this->send_alert($subject, $message);
				}

			}


		}

		$ccex_summaries = json_decode(file_get_contents('https://c-cex.com/t/prices.json')); // C-CEX API call


		foreach($ccex_summaries as $name => $summary) {

			if(in_array($name, $markets)) {
				// Check if markets have droppped below alert points
				if($summary->lastprice <= $lt_alerts[$name]) {
					$subject = "market LOW!";
					$message = $name ." at " . number_format($summary->lastprice, 8);
					if($this->log_alert("LOW-".$name))
						$this->send_alert($subject, $message);
				}

				// Check if markets have gone above alert points
				if($summary->lastprice >= $gt_alerts[$name]) {
					$subject = "market HI!";
					$message = $name ." at " . number_format($summary->lastprice, 8);
					if($this->log_alert("HI-".$name))
						$this->send_alert($subject, $message);
				}
			}

		}
	}

}
