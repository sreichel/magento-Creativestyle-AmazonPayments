<?php

/**
 * This file is part of the official Amazon Payments Advanced extension
 * for Magento (c) creativestyle GmbH <amazon@creativestyle.de>
 * All rights reserved
 *
 * Reuse or modification of this source code is not allowed
 * without written permission from creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  Copyright (c) 2014 - 2016 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Block_Adminhtml_Info extends Mage_Adminhtml_Block_System_Config_Form_Fieldset {

    /**
     * Store views collection
     */
    protected $_storeCollection = null;

    protected function _getInfo() {
        if (!$this->getChild('amazonpayments_register')) {
            $this->setChild('amazonpayments_register', $this->getLayout()->createBlock('amazonpayments/adminhtml_register')->setHtmlId('simple-path-registration'));
        }
        if (!$this->getChild('seller_central_config')) {
            $this->setChild('seller_central_config', $this->getLayout()->createBlock('amazonpayments/adminhtml_sellerCentral')->setHtmlId('simple-path-seller-central'));
        }
        $this->setTemplate('creativestyle/amazonpayments/info.phtml');
        $output = $this->toHtml();
        return $output;
    }

    /**
     * Returns store views collection
     *
     * @return Mage_Core_Model_Resource_Store_Collection
     */
    protected function _getStoreCollection() {
        if (null === $this->_storeCollection) {
            $this->_storeCollection = Mage::getModel('core/store')->getCollection()->load();
        }
        return $this->_storeCollection;
    }

    public function getRegisterHtml() {
        if ($this->getChild('amazonpayments_register')) {
            return $this->getChild('amazonpayments_register')->toHtml();
        }
        return '';
    }

    public function getSellerCentralConfigHtml() {
        if ($this->getChild('seller_central_config')) {
            return $this->getChild('seller_central_config')->toHtml();
        }
        return '';
    }

    public function getExtensionVersion() {
        return (string)Mage::getConfig()->getNode('modules/Creativestyle_AmazonPayments/version');
    }

    public function render(Varien_Data_Form_Element_Abstract $element) {
        return $this->_getInfo();
    }

    public function getUniqueId() {
        return 'AIOVPYYF70KB5.' . sha1('Creativestyle Amazon Payments Advanced Magento Extension' . Mage::getBaseUrl());
    }

    public function getValidationUrl() {
        return Mage::helper('adminhtml')->getUrl('adminhtml/amazonpayments_system/validate');
    }

    public function getInvalidJsonMsg() {
        return Mage::helper('amazonpayments')->__('This is not valid Seller Central configuration JSON');
    }

    public function getIpnUrl($sandbox = false) {
        $storeId = Mage::app()->getDefaultStoreView();
        $urlParams = array(
            '_current' => false,
            '_nosid' => true,
            '_store' => $storeId,
            '_forced_secure' => !$sandbox
        );
        $url = Mage::getModel('core/url')->setStore($storeId)->getUrl('amazonpayments/advanced_ipn/', $urlParams);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!$sandbox && $scheme != 'https') {
            return null;
        }
        return $url;
    }

    public function getJsOrigins() {
        $result = array();
        foreach ($this->_getStoreCollection() as $store) {
            $url = Mage::getModel('core/url')
                ->setStore($store->getId())
                ->getUrl('/', array(
                    '_current' => false, '_secure' => true, '_nosid' => true, '_store' => $store->getId()
                ));
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if ($scheme == 'https') {
                $host = parse_url($url, PHP_URL_HOST);
                $origin = 'https://' . $host;
                $port = parse_url($url, PHP_URL_PORT);
                if ($port) {
                    $origin .= ':' . $port;
                }
                $result[] = $origin;
            }
        }
        $result = array_unique($result);
        return Mage::helper('core')->jsonEncode($result);
    }

    public function getReturnUrls() {
        $result = array();
        foreach ($this->_getStoreCollection() as $store) {
            $urlModel = Mage::getModel('core/url');
            $urls = array(
                $urlModel->setStore($store->getId())->getUrl('amazonpayments/advanced_login/redirect', array(
                    '_current' => false, '_secure' => true, '_nosid' => true, '_store' => $store->getId()
                )),
                $urlModel->setStore($store->getId())->getUrl('amazonpayments/advanced_login/redirect', array(
                    'target' => 'checkout', '_current' => false, '_secure' => true, '_nosid' => true, '_store' => $store->getId()
                ))
            );
            foreach ($urls as $url) {
                $scheme = parse_url($url, PHP_URL_SCHEME);
                if ($scheme == 'https') {
                    $result[] = $url;
                }
            }
        }
        $result = array_unique($result);
        return Mage::helper('core')->jsonEncode($result);
    }

}
