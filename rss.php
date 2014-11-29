<?php
/**
 * Transmission simple RPC/0.1
 *
 * @author  fengqi <lyf362345@gmail.com>
 * @link    https://github.com/fengqi/transmission-rss
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
     * 添加种子, 如果是种子的原始二进制, 需要先进行 base64 编码
     *
     * @param $url
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
        $context = stream_context_create(array(
            'http' => array(
                'header' =>"Authorization: Basic ".base64_encode(sprintf("%s:%s", $this->user, $this->password)),
                'method' => 'GET',
                'timeout' => 5,
                'ignore_errors' => true
            ),
        ));
        file_get_contents($this->server, null, $context);

        foreach ($http_response_header as $header) {
            if (is_int(stripos($header, 'X-Transmission-Session-Id'))) {
                $this->session_id = $header; break;
            }
        }

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

        $context = stream_context_create(array(
            'http' => array(
                'header' => "Content-Type: application/json\r\n".
                            "Authorization: Basic ".base64_encode(sprintf("%s:%s", $this->user, $this->password))."\r\n".
                            $this->session_id,
                'method' => 'POST',
                'content' => json_encode($data),
                'timeout' => 5,
                'ignore_errors' => true
            ),
        ));

        return file_get_contents($this->server, null, $context);
    }

    /**
     * 获取 rss 的种子列表
     *
     * @param $rss
     * @return array
     */
    function getRssItems($rss)
    {
        $rss = file_get_contents($rss);
        $xml = new DOMDocument();
        $xml->loadXML($rss);
        $elements = $xml->getElementsByTagName('item');

        $items = array();
        foreach ($elements as $item) {
            $items[] = array(
                'title' => $item->getElementsByTagName('title')->item(0)->nodeValue,
                'link' => $item->getElementsByTagName('enclosure')->item(0)->getAttribute('url')
            );
        }

        return $items;
    }
}

// 配置
$rssLink = 'http://chdbits.org/torrentrss.php?myrss=1&linktype=dl&uid=111&passkey=111';
$server = 'http://127.0.0.1';
$port = 9091;
$rpcPath = '/transmission/rpc';
$user = '';
$password = '';

// 获取 rss 种子, 执行添加
$trans = new Transmission($server, $port, $rpcPath, $user, $password);
$torrents = $trans->getRssItems($rssLink);
foreach ($torrents as $torrent) {
    $response = json_decode($trans->add($torrent['link']));
    if ($response->result == 'success') {
        printf("success add torrent: %s\n", $torrent['title']);
    }
}

