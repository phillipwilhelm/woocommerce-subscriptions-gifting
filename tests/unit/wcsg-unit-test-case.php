<?php
/**
 *
 * @see WCG_Unit_Test_Case::setUp()
 * @since 2.0
 */
class WCSG_Unit_Test_Case extends WC_Unit_Test_Case {
	public function test() {
		$this->assertTrue( class_exists( 'WCS_Gifting' ) );
	}
}
