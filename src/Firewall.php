<?php
declare(strict_types = 1);

namespace Middlewares;

use IPLib\Address\AddressInterface;
use IPLib\Factory as IPFactory;
use IPLib\Range\RangeInterface;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Firewall implements MiddlewareInterface
{
    /** @var string[] */
    private $whitelist;

    /** @var string[] */
    private $blacklist = [];

    /** @var string|null */
    private $ipAttribute;

    private $responseFactory;

    /**
     * Constructor. Set the whitelist.
     *
     * @param string[]|null $whitelist
     */
    public function __construct(?array $whitelist = null, ?ResponseFactoryInterface $responseFactory = null)
    {
        $this->whitelist = $whitelist ?? [];
        $this->responseFactory = $responseFactory ?: Factory::getResponseFactory();
    }

    /**
     * Set ips not allowed.
     *
     * @param string[] $blacklist
     */
    public function blacklist(array $blacklist): self
    {
        $this->blacklist = $blacklist;

        return $this;
    }

    /**
     * Set the attribute name to get the client ip.
     */
    public function ipAttribute(string $ipAttribute): self
    {
        $this->ipAttribute = $ipAttribute;

        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $this->getIp($request);

        if (empty($ip)) {
            return $this->responseFactory->createResponse(403);
        }

        if (!$this->isIpAccessible($ip)) {
            return $this->responseFactory->createResponse(403);
        }

        return $handler->handle($request);
    }

    /**
     * Get the client ip.
     */
    private function getIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        if ($this->ipAttribute !== null) {
            return $request->getAttribute($this->ipAttribute);
        }

        return isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '';
    }

    /**
     * Create range class instance from string
     */
    protected function createRangeInstance(string $range): RangeInterface
    {
        if (strpos($range, '-') !== false) {
            $parts = explode('-', $range, 2);

            /* @phpstan-ignore-next-line */
            return IPFactory::getRangesFromBoundaries($parts[0], $parts[1]);
        }

        /* @phpstan-ignore-next-line */
        return IPFactory::parseRangeString($range);
    }

    /**
     * Convert IP list to range array
     *
     * @param string[]|null $list Data that needs to be converted
     *
     * @return array<RangeInterface>
     */
    private function convertListToRangeArray(?array $list): array
    {
        if ($list === null) {
            return [];
        }

        return array_map(
            [$this, 'createRangeInstance'],
            $list
        );
    }

    /**
     * Checks if IP address is in list
     *
     * @param AddressInterface $address IP address to check
     * @param string[]         $list    List of addresses to check
     *
     * @return bool
     */
    private function isAddressInList(AddressInterface $address, array $list): bool
    {
        foreach ($this->convertListToRangeArray($list) as $ipRange) {
            if ($ipRange->contains($address)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if IP by current addresses is accessible
     *
     * @param string $ip Current IP
     *
     * @return bool
     */
    private function isIpAccessible(string $ip): bool
    {
        if (!count($this->blacklist) && !count($this->whitelist)) {
            return true;
        }

        $address = IPFactory::parseAddressString($ip);
        if ($address === null) {
            return false;
        }

        if (count($this->blacklist) > 0 && !count($this->whitelist)) {
            return $this->isAddressInList($address, $this->whitelist);
        }

        if (count($this->whitelist) > 0 && !count($this->blacklist)) {
            return !$this->isAddressInList($address, $this->blacklist);
        }

        return $this->isAddressInList($address, $this->whitelist) &&
               !$this->isAddressInList($address, $this->blacklist);
    }
}
