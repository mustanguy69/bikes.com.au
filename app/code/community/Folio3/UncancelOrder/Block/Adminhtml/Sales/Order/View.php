<?php

/**
 * Filename: View.php.
 * Author: Muhammad Shahab Hameed
 * Date: 10/10/2016
 */
class Folio3_UncancelOrder_Block_Adminhtml_Sales_Order_View extends Mage_Adminhtml_Block_Sales_Order_View {

	public function __construct() {

		parent::__construct();

		$order = $this->getOrder();

		if ( $order->isCanceled() ) {
			$this->_addButton( 'uncancel', array(
				'label'   => __( 'Uncancel Order' ),
				'onclick' => 'deleteConfirm(\'' . __( 'Do you really want to uncancel this order?' ) . '\', \'' . $this->getUncancelOrderUrl() . '\')',
				'class'   => 'go'
			), 0, 100, 'header', 'header' );
		}
	}

	private function getUncancelOrderUrl() {
		return $this->getUrl( '*/*/uncancelorder' );
	}

}
