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
        $url = "https://graph.facebook.com/$version/$pixelId/events?access_token=$token";
        $result = $this->execCurl($url, $payload);

        return $result;
    }

    /**
     * Function responsible to format conversion json
     * 
     * @return json
     */
    public function formatJson($request) {

        $version = 'v12.0';
        $eventType = $request->getParam('event_type');
        $eventData = $request->getParam('event_data');
        $token = Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_token');
        $pixelId = Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_store_id');
        $eventId = $request->getParam('event_id');
        $sourceUrl = $_SERVER['HTTP_REFERER'];
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR']; // TODO $_SERVER['REMOTE_ADDR'] 
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $fbc = $request->getParam('fbc');
        $fbp = $request->getParam('fbp');

        $data = array(
            'data' => [
                array(
                    'event_name' => $eventType,
                    'event_time' => time(), 
                    'event_id' => $eventId,
                    'event_source_url' => $sourceUrl,
                    'user_data' => array(
                        'client_ip_address' => $ipAddress,
                        'client_user_agent' => $userAgent,
                        /*
                        'em' => array(
                           '309a0a5c3e211326ae75ca18196d301a9bdbd1a882a4d2569511033da23f0abd' // hashed
                        ),
                        'ph' => array(
                           '254aa248acb47dd654ca3ea53f48c2c26d641d23d7e2e93a1ec56258df7674c4' // hashed
                        ),
                        */
                        'fbc' => $fbc,
                        'fbp' => $fbp
                    ),
                    'custom_data' => json_decode($eventData)
                )
            ]
        );

        return json_encode($data);
    }

    /**
     * Function responsible to send json using curl
     * 
     * @return json
     */
    public function execCurl($url, $payload) {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

}