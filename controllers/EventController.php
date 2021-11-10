<?php

class Cammino_Fbpixel_EventController extends Mage_Core_Controller_Front_Action
{
    public function sendAction()
    {
        $request = Mage::app()->getRequest();
        $model = Mage::getModel('fbpixel/conversion');
        $result = $model->sendEvent($request);
        echo $result;
    }
}