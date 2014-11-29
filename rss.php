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
     * 添加种子, 默认是发送种子的原始二进制
     * todo 后期扩展成可添加远程种子, 本地文件
     *
     * @param $url
     * @param array $options
     * @return mixed
     */
    public function add($url, $options = array())
    {
        return $this->request('torrent-add', array_merge($options, array(
            'metainfo' => is_file($url) ? base64_encode(file_get_contents($url)) : $url,
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
        $ch = curl_init($this->server);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->password);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

        /*$header = array(
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
        curl_close($ch);*/

        $context = stream_context_create(array(
            'http' => array(
                'header' => "Content-Type: application/json\r\n".
                    "Authorization: Basic ".base64_encode(sprintf("%s:%s", $this->user, $this->password))."\r\n".
                    'X-Transmission-Session-Id: '.$this->session_id,
                'method' => 'POST',
                'content' => json_encode($data),
            ),
        ));
        $content = file_get_contents($this->server, null, $context);

        return $content;
    }

    /**
     * 获取 rss 的种子列表
     *
     * @param $rss
     * @return array
     */
    function getRssItems($rss, $tempDir = '/tmp/rss')
    {
        $torrents = glob($tempDir.'/*.torrent');
        if (!empty($torrents)) return $torrents;

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

        return $items;
    }
}

// 配置
$rssLink = 'http://chdbits.org/torrentrss.php?myrss=1&linktype=dl&uid=111&passkey=111';
$server = 'http://127.0.0.1';
$port = '9091';
$rpcPath = '/transmission/rpc';
$user = '';
$password = '';
$tempDir = '/tmp/rss';

// 获取 rss 种子, 执行添加
$trans = new Transmission($server, $port, $rpcPath, $user, $password);
$torrents = $trans->getRssItems($rssLink, $tempDir);
foreach ($torrents as $torrent) {
    $response = $trans->add($torrent);
    $response = json_decode($response);
    if ($response->result == 'success') {
        printf("success add torrent: %s\n", $torrent);
        unlink($torrent);
    }
}

