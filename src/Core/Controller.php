<?php

namespace PHPWebsocket\Core;

class Controller
{
    /**
     * @var string
     */
    protected $php;

    /**
     * @var string
     */
    protected $handlerPath;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var int
     */
    protected $port;

    public function __construct(string $php, string $handlerPath)
    {
        $this->php = $php;
        $this->handlerPath = $handlerPath;
    }

    public function process($socket_id, $count, $name, $data)
    {
        $message = base64_encode(json_encode([$this->address, $this->port, $socket_id, $count, $name, $data]));
        $cmd = sprintf("%s %s %s", $this->php, $this->handlerPath, $message);
        $this->asyncExec($cmd);
    }

    protected function asyncExec($cmd)
    {
        if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }
    }

    public function setAddress($address)
    {
        $this->address = $address;
    }

    public function setPort($port)
    {
        $this->port = $port;
    }
}
