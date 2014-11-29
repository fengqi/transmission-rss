<?php
/**
 * Transmission simple RPC/0.1
 *
 * @author  fengqi <lyf362345@gmail.com>
 * @version $Id: $
 */
class Transmission
{
    private $server;
    private $user;
    private $password;
    protected $session_id;

    /**
     * 构造函数, 初始化配置
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
        $this->server = $server . ':' . $port . $rpcPath;
        $this->user = $user;
        $this->password = $password;
        $this->session_id = $this->getSessionId();
    }

    /**
     * 添加种子, 也可添加种子的二进制文件, 但是 isEncoded 需要设置为 true
     *
     * @param $url
     * @param bool $isEncoded
     * @param array $options
     * @return mixed
     */
    public function add($url, $options = array())
    {
        return $this->request('torrent-add', array_merge($options, array(
            'metainfo' => is_file($url) ? file_get_contents(base64_encode($url)) : $url,
        )));
    }

    /**
     * 获取 Transmission 服务器状态
     *
     * @return mixed
     */
    public function status()
    {
        return $this->request("session-stats");
    }

    /**
     * 获取 Transmission session-id, 每次 rpc 请求都需要带上 session-id
     *
     * @return string
     */
    public function getSessionId()
    {
        $data = array(
            'method' => 'session-get',
        );

        $ch = curl_init($this->server);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->password);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $content = curl_exec($ch);
        curl_close($ch);

        preg_match("/X-Transmission-Session-Id: (.*)/", $content, $content);
        $this->session_id = $content[1];

        return $this->session_id;
    }

    /**
     * 执行 rpc 请求
     *
     * @param $method 请求类型/方法, 详见 $this->allowMethods
     * @param array $arguments 附加参数, 可选
     * @return mixed
     */
    private function request($method, $arguments = array())
    {
        $data = array(
            'method' => $method,
            'arguments' => $arguments
        );

        $header = array(
            'X-Transmission-Session-Id: '.$this->session_id,
        );

        $ch = curl_init($this->server);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }

    /**
     * 获取 chd, cmct, ttg 等的 rss 列表
     *
     * @param $rss
     * @return array
     */
    function getRssItems($rss, $tempDir = '/tmp/rss')
    {
        $rss = file_get_contents($rss);
        $xml = new DOMDocument();
        $xml->loadXML($rss);
        $elements = $xml->getElementsByTagName('item');

        $items = array();
        foreach ($elements as $item) {
            $title = $item->getElementsByTagName('title')->item(0)->nodeValue;
            $link = $item->getElementsByTagName('enclosure')->item(0)->getAttribute('url');

            $data = file_get_contents($link);
            !file_exists($tempDir) && mkdir($tempDir);
            $file = sprintf("%s/%s.torrent", $tempDir, $title);
            file_put_contents($file, $data);

            $items[] = $file;
        }

        unset($title, $link, $rss, $xml, $elements, $data, $file);
        return $items;
    }
}

$rssLink = 'http://chdbits.org/torrentrss.php?myrss=1&linktype=dl&uid=111&passkey=111';
$server = 'http://127.0.0.1';
$port = '9091';
$rpcPath = '/transmission/rpc';
$user = '';
$password = '';
$tempDir = '/tmp/rss';

$trans = new Transmission($server, $port, $rpcPath, $user, $password);


// 获取 rss 种子
$torrents = glob($tempDir.'/*.torrent');
if (empty($torrents)) {
    $torrents = $trans->getRssItems($rssLink, $tempDir);
}

// 执行添加
foreach ($torrents as $torrent) {
    $response = $trans->add($torrent);
    $response = json_decode($response);
    if ($response->result == 'success') {
        printf("success add torrent: %s\n", $torrent);
        unlink($torrent);
    }
}
