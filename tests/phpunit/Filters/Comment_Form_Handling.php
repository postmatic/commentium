<?php
namespace Postmatic\Commentium\Unit_Tests\Filters;

use Postmatic\Commentium\Filters;

use WP_UnitTestCase;

class Comment_Form_Handling extends WP_UnitTestCase {

   public function test_default() {
       $text = 'XXTEXTXX';
       $filtered_text = Filters\Comment_Form_Handling::tooltip( $text, 0 );

       $this->assertEquals( $text, $filtered_text, 'Expected no change to tooltip text.' );
   }

   public function test_replies_only() {
       $options = $this->getMockBuilder( 'Prompt_Options' )->disableOriginalConstructor()->getMock();

       $options->expects( $this->once() )
           ->method( 'get' )
           ->with( 'enable_replies_only' )
           ->willReturn( true );

       $text = 'XXTEXTXX';

       $filtered_text = Filters\Comment_Form_Handling::tooltip( $text, 1, $options );

       $this->assertNotEquals( $text, $filtered_text, 'Expected changed tooltip text.' );
   }
}
