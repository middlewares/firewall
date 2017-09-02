<?php

namespace Middlewares;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use M6Web\Component\Firewall\Firewall as IpFirewall;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Firewall implements MiddlewareInterface
{
    /**
     * @var array|null
     */
    private $whitelist;

    /**
     * @var array|null
     */
    private $blacklist;

    /**
     * @var string|null
     */
    private $ipAttribute;

    /**
     * Constructor. Set the whitelist.
     *
     * @param array $whitelist
     */
    public function __construct(array $whitelist = null)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * Set ips not allowed.
     *
     * @param array $blacklist
     *
     * @return self
     */
    public function blacklist(array $blacklist)
    {
        $this->blacklist = $blacklist;

        return $this;
    }

    /**
     * Set the attribute name to get the client ip.
     *
     * @param string $ipAttribute
     *
     * @return self
     */
    public function ipAttribute($ipAttribute)
    {
        $this->ipAttribute = $ipAttribute;

        return $this;
    }

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $ip = $this->getIp($request);

        if (empty($ip)) {
            return Utils\Factory::createResponse(403);
        }

        $firewall = new IpFirewall();

        if (!empty($this->whitelist)) {
            $firewall->addList($this->whitelist, 'whitelist', true);
        }

        if (!empty($this->blacklist)) {
            $firewall->addList($this->blacklist, 'blacklist', false);
        }

        $firewall->setIpAddress($ip);

        if (!$firewall->handle()) {
            return Utils\Factory::createResponse(403);
        }

        return $delegate->process($request);
    }

    /**
     * Get the client ip.
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    private function getIp(ServerRequestInterface $request)
    {
        $server = $request->getServerParams();

        if ($this->ipAttribute !== null) {
            return $request->getAttribute($this->ipAttribute);
        }

        return isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '';
    }
}
