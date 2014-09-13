bittrex_alert
=============

PHP Bittrex alert script


Here's a a little script I wrote to send me alerts when the prices of certain alt coins pass defined points.  I have it running on a cron job and it creates a log file instead of using sessions in order to limit repeat alerts to once an hour.  The directory will need to be writable for the log file.  Obviously you have to set the alert points manually in the PHP but if anyone is interested I can set this up as a service with a web interface.  It also sends an alert when new markets are added to Bittrex, never miss a pump and dump again!
