<?php

namespace Vindi\Payment\Helper;

use Vindi\Payment\Helper\WebHookHandlers\BillCreated;
use Vindi\Payment\Helper\WebHookHandlers\BillPaid;
use Vindi\Payment\Helper\WebHookHandlers\ChargeRejected;

class WebhookHandler
{
    public function __construct(
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Psr\Log\LoggerInterface $logger,
        BillCreated $billCreated,
        BillPaid $billPaid,
        ChargeRejected $chargeRejected
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->logger = $logger;
        $this->billCreated = $billCreated;
        $this->billPaid = $billPaid;
        $this->chargeRejected = $chargeRejected;
    }

    public function getRemoteIp()
    {
        return $this->remoteAddress->getRemoteAddress();
    }

    /**
     * Handle incoming webhook.
     *
     * @param string $body
     *
     * @return bool
     */
    public function handle($body)
    {
        try {
            $jsonBody = json_decode($body, true);

            if (!$jsonBody || !isset($jsonBody['event'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Webhook event not found!')->getText());
            }

            $type = $jsonBody['event']['type'];
            $data = $jsonBody['event']['data'];
        } catch (\Exception $e) {
            $this->logger->info(__(sprintf('Fail when interpreting webhook JSON: %s', $e->getMessage())));
            return false;
        }

        switch ($type) {
            case 'test':
                $this->logger->info(__('Webhook test event.'));
                break;
            case 'bill_created':
                return $this->billCreated->billCreated($data);
            case 'bill_paid':
                return $this->billPaid->billPaid($data);
            case 'charge_rejected':
                return $this->chargeRejected->chargeRejected($data);
            default:
                $this->logger->warning(__(sprintf('Webhook event ignored by plugin: "%s".', $type)));
                break;
        }
    }
}
