<?php
/**
 * Conversion.php
 * 
 * @category Cammino
 * @package  Cammino_Fbpixel
 * @author   Cammino Digital <suporte@cammino.com.br>
 * @license  http://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * @link     https://github.com/cammino/magento-fbpixel
 */

class Cammino_Fbpixel_Model_Conversion
{

    /**
     * Function responsible to process conversion
     * 
     * @return json
     */
    public function sendEvent($request) {

        $payload = $this->formatJson($request);
        $result = $this->execCurl($payload);

        return $result;
    }

    /**
     * Function responsible to format conversion json
     * 
     * @return json
     */
    public function formatJson($request) {

        
        $eventType = $request->getParam('event_type');
        $eventData = $request->getParam('event_data');
        $eventId = $request->getParam('event_id');
        $sourceUrl = $_SERVER['HTTP_REFERER'];
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR']; // TODO $_SERVER['REMOTE_ADDR'] 
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $fbc = $request->getParam('fbc');
        $fbp = $request->getParam('fbp');

        $data = array(
            array(
                'event_name' => $eventType,
                'event_time' => time(), 
                'event_id' => $eventId,
                'event_source_url' => $sourceUrl,
                'action_source' => 'website',
                'user_data' => array(
                    'client_ip_address' => $ipAddress,
                    'client_user_agent' => $userAgent,
                    'fbc' => $fbc,
                    'fbp' => $fbp
                ),
                'custom_data' => json_decode($eventData)
            )
        );

        $customer = Mage::getSingleton('customer/session')->getCustomer();

        if ($customer) {
            $data[0]['user_data']['em'] = array(hash('sha256', $customer->getEmail()));

            $address = $customer->getDefaultBillingAddress();

            if ($address) {
                $phone = preg_replace( '/[^0-9]/', '', $address->getTelephone());
                $data[0]['user_data']['ph'] = array(hash('sha256', $phone));                
            }
        }

        return json_encode($data);
    }

    /**
     * Function responsible to send json using curl
     * 
     * @return json
     */
    public function execCurl($payload) {

        $version = 'v12.0';
        $token = Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_token');
        $pixelId = Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_store_id');
        $testId = Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_test_id');
        $url = "https://graph.facebook.com/$version/$pixelId/events";

        $post = array(
            "access_token" => $token,
            "data" => $payload,
            "test_event_code" => $testId
        );

        Mage::log($url, null, 'fbpixel_api.log');
        Mage::log($post, null, 'fbpixel_api.log');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        $result = curl_exec($ch);
        curl_close($ch);

        Mage::log($result, null, 'fbpixel_api.log');

        return $result;
    }

}