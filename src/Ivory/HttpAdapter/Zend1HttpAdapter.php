<?php

/*
 * This file is part of the Ivory Http Adapter package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\HttpAdapter;

use Ivory\HttpAdapter\Message\InternalRequestInterface;
use Ivory\HttpAdapter\Normalizer\BodyNormalizer;

/**
 * Zend 1 http adapter.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class Zend1HttpAdapter extends AbstractHttpAdapter
{
    /** @var \Zend_Http_Client */
    protected $client;

    /**
     * Creates a zend 1 http adapter.
     *
     * @param \Zend_Http_Client $client The zend 1 client.
     */
    public function __construct(\Zend_Http_Client $client = null)
    {
        parent::__construct();

        $this->client = $client ?: new \Zend_Http_Client();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'zend1';
    }

    /**
     * {@inheritdoc}
     */
    protected function doSend(InternalRequestInterface $internalRequest)
    {
        $this->client
            ->resetParameters(true)
            ->setConfig(array(
                'httpversion'  => $internalRequest->getProtocolVersion(),
                'timeout'      => $this->timeout,
                'maxredirects' => 0,
            ))
            ->setUri($internalRequest->getUrl())
            ->setMethod($internalRequest->getMethod())
            ->setHeaders($this->prepareHeaders($internalRequest))
            ->setRawData($this->prepareBody($internalRequest));

        try {
            $response = $this->client->request();
        } catch (\Exception $e) {
            throw HttpAdapterException::cannotFetchUrl($internalRequest->getUrl(), $this->getName(), $e->getMessage());
        }

        return $this->createResponse(
            $response->getVersion(),
            $response->getStatus(),
            $response->getMessage(),
            $response->getHeaders(),
            BodyNormalizer::normalize(
                $response instanceof \Zend_Http_Client_Adapter_Stream ? $response->getStream() : $response->getBody(),
                $internalRequest->getMethod()
            )
        );
    }
}
