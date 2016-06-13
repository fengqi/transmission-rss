# Transmission-RSS - add support of RSS to Transmission with Pushbullet notification
## Description
* Edit script according to your environment and requirement -  rss, server, port, rpcPath, user, password , Logfilepath , pushbulletnotifcation file path.
* Tested on my Raspberry Pi -  Create a regular cron job to execute it -  `*/10 * * * * php rss.php`

## Added following functionalites to  fengqi's repo -
* Script creates a log file and all newly added torrents' titles are recorded in log file. 
* Before adding a new torrent to transmission queue, script checks log file to verify it is not present already present in log file. Hence no torrent downloads twice. 
