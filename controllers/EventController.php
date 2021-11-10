<?php

class Cammino_Fbpixel_EventController extends Mage_Core_Controller_Front_Action
{
    public function sendAction()
    {
        $version = 'v12.0';
        $eventType = Mage::app()->getRequest()->getParam('event_type');
        $eventData = Mage::app()->getRequest()->getParam('event_data');
        $token = Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_token');
        $pixelId = Mage::getStoreConfig('fbpixel/fbpixel_group/fbpixel_store_id');
        $eventId = Mage::app()->getRequest()->getParam('event_id');
        $sourceUrl = $_SERVER['HTTP_REFERER'];
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR']; // TODO $_SERVER['REMOTE_ADDR'] 
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $fbc = Mage::app()->getRequest()->getParam('fbc');
        $fbp = Mage::app()->getRequest()->getParam('fbp');

        $url = "https://graph.facebook.com/$version/$pixelId/events?access_token=$token";

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

        echo json_encode($data);
    }
}