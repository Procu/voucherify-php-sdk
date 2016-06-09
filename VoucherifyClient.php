<?php

namespace Voucherify;

class VoucherifyClient
{
    /**
     * @var string
     */
    private static $apiURL = 'https://api.voucherify.io/v1';

    /**
     * @var string
     */
    private $apiID;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param string $apiID
     * @param string $apiKey
     */
    public function __construct($apiID, $apiKey)
    {
        $this->setApiID($apiID);
        $this->setApiKey($apiKey);
    }

    private function getHeaders()
    {
        return [
            'Content-Type: application/json',
            'X-App-Id: ' . $this->apiID,
            'X-App-Token: ' . $this->apiKey,
            'X-Voucherify-Channel: PHP-SDK',
        ];
    }

    private function encodeParams($params)
    {
        if (!is_array($params) && !is_object($params)) {
            return $params;
        }

        $result = [];
        foreach ($params as $key => $value) {
            if (is_null($value)) {
                continue;
            }
            $result[] = urlencode($key) . '=' . urlencode($value);
        }

        return implode('&', $result);
    }

    /**
     * @param string                   $method
     * @param string                   $endpoint
     * @param array|null               $params
     * @param string|array|object|null $data
     *
     * @return mixed
     * @throws ClientException
     */
    private function apiRequest($method, $endpoint, $params, $data)
    {

        $setParams = $params && in_array($method, ['GET', 'POST']);
        $setData = $data && in_array($method, ['POST', 'PUT', 'DELETE']);

        $method = strtoupper($method);
        $url = self::$apiURL . $endpoint . ($setParams ? '?' . $this->encodeParams($params) : "");

        $options = [];
        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_HTTPHEADER] = $this->getHeaders();
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLOPT_CUSTOMREQUEST] = $method;
        $options[CURLOPT_POSTFIELDS] = $setData ? json_encode($data) : null;

        $curl = curl_init();

        curl_setopt_array($curl, $options);

        $result = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        // Connection errors
        if ($result === false) {
            throw new ClientException($error);
        } // Invalid status code
        else {
            if ($statusCode >= 400) {
                throw new ClientException('Unexpected status code: ' . $statusCode . ' - Details: ' . $result);
            }
        }

        return json_decode($result);
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $apiID
     */
    public function setApiID($apiID)
    {
        $this->apiID = $apiID;
    }

    /**
     * Get voucher details
     *
     * @param string $code
     *
     * @return mixed
     * @throws ClientException
     */
    public function get($code)
    {
        return $this->apiRequest('GET', '/vouchers/' . urlencode($code), null, null);
    }

    /**
     * Create voucher.
     *
     * @param \stdClass $voucher
     *
     * @return mixed
     * @throws ClientException
     */
    public function create($voucher)
    {
        if (isset($voucher->code)) {
            return $this->apiRequest('POST', '/vouchers/' . urlencode($voucher->code), null, $voucher);
        } else {
            return $this->apiRequest('POST', '/vouchers/', null, $voucher);
        }
    }

    /**
     * Enable voucher with given code.
     *
     * @param string $code
     *
     * @return mixed
     * @throws ClientException
     */
    public function enable($code)
    {
        return $this->apiRequest('POST', '/vouchers/' . urlencode($code) . '/enable', null, null);
    }

    /**
     * Disable voucher with given code.
     *
     * @param string $code
     *
     * @return mixed
     * @throws ClientException
     */
    public function disable($code)
    {
        return $this->apiRequest('POST', '/vouchers/' . urlencode($code) . '/disable', null, null);
    }

    /**
     * Get voucher redemption
     *
     * @param string $code
     *
     * @return mixed
     * @throws ClientException
     */
    public function redemption($code)
    {
        return $this->apiRequest('GET', '/vouchers/' . urlencode($code) . '/redemption/', null, null);
    }

    /**
     * Redeem voucher
     *
     * @param string|array $code       Voucher code or array with voucher and customer items
     * @param string|null  $trackingId Provided tracking id
     *
     * @return mixed
     * @throws ClientException
     */
    public function redeem($code, $trackingId)
    {
        $context = [];
        if (is_array($code)) {
            $context = $code;
            $code = $context['voucher'];
            unset($context['voucher']);
        }

        return $this->apiRequest(
            'POST',
            '/vouchers/' . urlencode($code) . '/redemption/',
            ['tracking_id' => $trackingId],
            $context
        );
    }

    /**
     * Rollback redemption. This operation creates a rollback entry in voucher's redemption history
     * (`redemption.redemption_entries`) and gives 1 redemption back to the pool (decreases `redeemed_quantity` by 1).
     *
     * @param string      $redemptionId
     * @param string|null $trackingId
     * @param string|null $reason
     *
     * @return mixed
     * @throws ClientException
     */
    public function rollback($redemptionId, $trackingId = null, $reason = null)
    {
        return $this->apiRequest(
            'POST',
            '/redemptions/' . urlencode($redemptionId) . '/rollback/',
            ['tracking_id' => $trackingId,
             'reason' => $reason],
            null
        );
    }

    /**
     * Get a filtered list of vouchers. The filter can include following properties:
     * - code_query - string
     * - limit      - number (default 10)
     * - skip       - number (default 0)
     * - campaign   - string
     * - category   - string
     * - customer   - string
     *
     * @param array|\stdClass $filter
     *
     * @return mixed
     * @throws ClientException
     */
    public function vouchers($filter)
    {
        return $this->apiRequest('GET', '/vouchers/', $filter, null);
    }

    /**
     * Get a filtered list of redemptions. The filter can include following properties:
     * - limit      - number (default 100)
     * - page       - number (default 0)
     * - start_date - string (ISO8601 format, default is the beginning of current month)
     * - end_date   - string (ISO8601 format, default is the end of current month)
     * - result     - string (Success|Failure-NotExist|Failure-Inactive)
     * - customer   - string
     *
     * @param array|\stdClass $filter
     *
     * @return mixed
     * @throws ClientException
     */
    public function redemptions($filter)
    {
        return $this->apiRequest('GET', '/redemptions/', $filter, null);
    }
}
