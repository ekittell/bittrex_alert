<?php

$BittrexAlerts = new BittrexAlerts();
$BittrexAlerts->run();

Class BittrexAlerts {
	private $sms = "2125551212@txt.att.net"; //check with your mobile carrier
	private $email = "your@email.com";
	private $identitifier = "BITTREX-ALERT"; // use to identify messages for smart mail boxes
	private $headers;

	private $lt_alerts = array(
		"BTC-RZR" 		=> .00033000,
		"BTC-VIA"	 	=> .00032000
	);

	private $gt_alerts = array(
		"BTC-JBS" 		=> .00010000,
		"BTC-VIA" 		=> .00040000,
		"BTC-RZR" 		=> .00040000
	);

	private $log = array();
	private $log_file;

	public function __construct() {
		$this->log_file = dirname(__FILE__) . "/alerts.log";

		// create log file with empty array
		if(!file_exists($this->log_file))
			file_put_contents($this->log_file, serialize(array())) or die("can't create file");

		$this->log = unserialize(file_get_contents($this->log_file));

		$this->headers = 'From: alerts@yourdomain.com' . "\r\n" .
	    'X-Mailer: PHP/' . phpversion();

	}

	private function send_alert($subject, $message) {
		$subject = $this->identitifier . " : " . $subject;
		mail($this->sms, $subject, $message, $this->headers) or die('cannot send sms');
		mail($this->email, $subject, $message, $this->headers) or die('cannot send mail');
	}

	private function log_alert($alert_name, $repeat = true) {
		// if last alert was over an hour ago then log alert and return true 
		$limit = time() - (60 * 60);
		if(!isset($this->log[$alert_name]) || ($this->log[$alert_name] < $limit && $repeat)) {
			$this->log[$alert_name] = time();
			file_put_contents($this->log_file, serialize($this->log));
			return true;
		}

		return false;
	}

	public function run() {
		// to avoid calling array_keys too many times in the loop
		$lt_markets = array_keys($this->lt_alerts); 
		$gt_markets = array_keys($this->gt_alerts); 

		$summaries = json_decode(file_get_contents('https://bittrex.com/api/v1.1/public/getmarketsummaries')); // BITTREX API call

		foreach($summaries->result as $summary) {
			// Check for new Markets
			$limit = time() - (60 * 60);
			if(strtotime($summary->Created) >= $limit) {
				$subject = "NEW market!";
				$message = $summary->MarketName;
				if($this->log_alert("NEW-".$summary->MarketName, false))
					$this->send_alert($subject, $message);
			}

			// Check if markets have dropped below alert points
			if(in_array($summary->MarketName, $lt_markets) && $summary->Last <= $this->lt_alerts[$summary->MarketName]) {
				$subject = "market LOW!";
				$message = $summary->MarketName ." at " . number_format($summary->Last, 8);
				if($this->log_alert("LOW-".$summary->MarketName))
					$this->send_alert($subject, $message);
			}

			// Check if markets have gone above alert points
			if(in_array($summary->MarketName, $gt_markets) && $summary->Last >= $this->gt_alerts[$summary->MarketName]) {
				$subject = "market HI!";
				$message = $summary->MarketName ." at " . number_format($summary->Last, 8);
				if($this->log_alert("HI-".$summary->MarketName))
					$this->send_alert($subject, $message);
			}
		}
	}

}
