#!/usr/bin/php
<?php
/**
 * Transmission simple RPC/0.1
 *
 * @author  fengqi <lyf362345@gmail.com>
 * @Modified_by ankit nigam @ankit__nigam
 * @link    https://github.com/fengqi/transmission-rss
 */
class Transmission
{
    private $server;
    private $user;
    private $password;
    private $session_id;

    /**
     * æž„é€ å‡½æ•°, åˆå§‹åŒ–é…ç½®
     *
     * @param $server
     * @param string $port
     * @param string $rpcPath
     * @param string $user
     * @param string $password
     *
     * @return \Transmission
     */
    public function __construct($server, $port = '9091', $rpcPath = '/transmission/rpc', $user = '', $password = '')
    {
        $this->server = $server.':'.$port.$rpcPath;
        $this->user = $user;
        $this->password = $password;
        $this->session_id = $this->getSessionId();
    }

    /**
     * æ·»åŠ ç§å­, å¦‚æžœæ˜¯ç§å­çš„åŽŸå§‹äºŒè¿›åˆ¶, éœ€è¦å…ˆè¿›è¡Œ base64 ç¼–ç 
     *
     * @param $url
     * @param bool $isEncode
     * @param array $options
     * @return mixed
     */
    public function add($url, $isEncode = false, $options = array())
    {
        return $this->request('torrent-add', array_merge($options, array(
            $isEncode ? 'metainfo' : 'filename' => $url,
        )));
    }

    /**
     * èŽ·å– Transmission æœåŠ¡å™¨çŠ¶æ€
     *
     * @return mixed
     */
    public function status()
    {
        return $this->request("session-stats");
    }

    /**
     * èŽ·å– Transmission session-id, æ¯æ¬¡ rpc è¯·æ±‚éƒ½éœ€è¦å¸¦ä¸Š session-id
     *
     * @return string
     */
    public function getSessionId()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->server);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->password);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $content = curl_exec($ch);
        curl_close($ch);
        preg_match("/<code>(X-Transmission-Session-Id: .*)<\/code>/", $content, $content);
        $this->session_id = $content[1];

        return $this->session_id;
    }

    /**
     * æ‰§è¡Œ rpc è¯·æ±‚
     *
     * @param $method è¯·æ±‚ç±»åž‹/æ–¹æ³•, è¯¦è§ $this->allowMethods
     * @param array $arguments é™„åŠ å‚æ•°, å¯é€‰
     * @return mixed
     */
    private function request($method, $arguments = array())
    {
        $data = array(
            'method' => $method,
            'arguments' => $arguments
        );

        $header = array(
            'Content-Type: application/json',
            'Authorization: Basic '.base64_encode(sprintf("%s:%s", $this->user, $this->password)),
            $this->session_id
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->server);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->password);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $content = curl_exec($ch);
        curl_close($ch);

        if (!$content)  $content = json_encode(array('result' => 'failed'));
        return $content;

    }

    /**
     * èŽ·å– rss çš„ç§å­åˆ—è¡¨
     *
     * @param $rss
     * @return array
     */
    function getRssItems($rss)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $items = array();
        foreach ($rss as $link) {
            curl_setopt($ch, CURLOPT_URL, $link);
            $content = curl_exec($ch);
            if (!$content) continue;

            $xml = new DOMDocument();
            $xml->loadXML($content);
            $elements = $xml->getElementsByTagName('item');

            foreach ($elements as $item) {
                $link = $item->getElementsByTagName('enclosure')->item(0) != null ?
                        $item->getElementsByTagName('enclosure')->item(0)->getAttribute('url') :
                        $item->getElementsByTagName('link')->item(0)->nodeValue;

                $items[] = array(
                    'title' => $item->getElementsByTagName('title')->item(0)->nodeValue,
                    'link' => $link,
                );
            }
        }
        curl_close($ch);

        return $items;
    }
}

// é…ç½®
$rss = array(
    'http://chdbits.org/torrentrss.php...',
    'http://totheglory.im/putrssmc.php...',
    'https://hdcmct.org/torrentrss.php...',
    'https://open.cd/torrentrss.php?.....',
    'https://mv.open.cd/torrentrss.php?..',
    'http://hdwing.com/rss.php?..........',
    'http://hdtime.org/torrentrss.php?...'
);
$server = 'http://192.168.2.0';
$port = 9091;
$rpcPath = '/transmission/rpc';
$user = '';
$password = '';
$file = '';
$pushbullet_script = '';
$trans = new Transmission($server, $port, $rpcPath, $user, $password);
$torrents = $trans->getRssItems($rss);
foreach ($torrents as $torrent) {
    $exists = 0;
    $search = $torrent['title'];
    $lines = file($file);
    foreach($lines as $line){
      if(strpos($line, $search) !== false){
      $exists = 1;
      printf("%s: Torrent Already Downloaded / or in queue: %s\n", date('Y-m-d H:i:s'), $torrent['title']);
      }
    }
    if($exists == 0){
      $response = json_decode($trans->add($torrent['link']));
      if ($response->result == 'success') {
          printf("%s: success add torrent: %s\n", date('Y-m-d H:i:s'), $torrent['title']);
          $message = $torrent['title'].PHP_EOL;
          file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
          $mystring = system("python $pushbullet_script $message");
          echo $mystring;
      }
    }
}

