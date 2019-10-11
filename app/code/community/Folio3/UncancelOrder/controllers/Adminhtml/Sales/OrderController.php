<?php

/**
 * Filename: OrderController.php.
 * Author: Muhammad Shahab Hameed
 * Date: 10/10/2016
 */
class Folio3_UncancelOrder_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Controller_Action {

	public function uncancelorderAction() {
		$orderId       = $this->getRequest()->getParam( 'order_id' );
		$uncancel = Mage::getModel( 'Folio3_UncancelOrder_Model_Uncancel' );

		if ( $uncancel->uncancelOrder( $orderId ) ) {
			$this->_getSession()->addSuccess( $this->__( 'Order was successfully uncancelled.' ) );
		} else {
			$this->_getSession()->addError( $this->__( 'Order was not uncancelled.' ) );
		}

		$this->_redirect( '*/sales_order/view', array( 'order_id' => $orderId ) );
	}


	public function massUncancelOrderAction() {
	$orderIds              = $this->getRequest()->getPost( 'order_ids', array() );
	$countUnCancelOrder    = 0;
	$countNonUnCancelOrder = 0;
	$uncancel              = Mage::getModel( 'Folio3_UncancelOrder_Model_Uncancel' );

	foreach ( $orderIds as $orderId ) {
		if ( $uncancel->uncancelOrder( $orderId ) ) {
			$countUnCancelOrder ++;
		} else {
			$countNonUnCancelOrder ++;
		}
	}
	if ( $countNonUnCancelOrder ) {
		if ( $countUnCancelOrder ) {
			$this->_getSession()->addError( $this->__( '%s order(s) cannot be uncanceled', $countNonUnCancelOrder ) );
		} else {
			$this->_getSession()->addError( $this->__( 'The order(s) cannot be uncanceled' ) );
		}
	}
	if ( $countUnCancelOrder ) {
		$this->_getSession()->addSuccess( $this->__( '%s order(s) have been uncanceled.', $countUnCancelOrder ) );
	}
	$this->_redirect( '*/*/' );
}
}