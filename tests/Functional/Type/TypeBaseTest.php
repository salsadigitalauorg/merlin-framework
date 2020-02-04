<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\TypeBase;
use Merlin\Processor\ProcessorInterface;
use Merlin\Processor\Nl2br;
use Merlin\Exception\ElementNotFoundException;
use Merlin\Exception\ValidationException;

class TypeBaseTest extends CrawlerTestCase {

  /**
   * Ensure that the value can be added.
   * @group type_base_test
   */
  public function testAddingValueToRow() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '.page-title',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->getMockForAbstractClass();

    $type->addValueToRow('Test');
    $this->assertObjectHasAttribute('test', $row);
    $this->assertEquals('Test', $row->test);
  }

  /**
   * Ensure that an exception is thrown if field name is not set.
   * @group type_base_test
   */
  public function testAddingValueToRowException() {
    $row = new \stdClass();
    $config = [
      'selector' => '.page-title',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->getMockForAbstractClass();

    $this->expectException(ValidationException::class);
    $type->addValueToRow('Test');
  }

  /**
   * Test that DOM process is called if we specify a DOM selector.
   * @group type_base_test
   */
  public function testDomSelector() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '.page-title',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['processDom'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('processDom');

    $type->process();

  }

  /**
   * Test that DOM process is called if we specify a DOM selector.
   * @group type_base_test
   */
  public function testXpathSelector() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '//*[@id="wrapper"]/div/h1',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['processXpath'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('processXpath');

    $type->process();

  }

  /**
   * Test invalid selector.
   * @group type_base_test
   */
  public function testInvalidSelectorAllowNull() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '//div[contains(@class, "not-found")]',
      'options' => ['allow_null' => TRUE],
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['nullValue'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('nullValue')
      ->willReturn('Test');

    $this->expectException(ElementNotFoundException::class);
    $return = $type->process();
    $this->assertEquals('Test', $return);
  }

  /**
   * Ensure that nullValue is not called if we haven't called it out.
   * @group type_base_test
   */
  public function testInvalidSelectorNotAllowNull() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
      'selector' => '//div[contains(@class, "not-found")]',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['nullValue'])
      ->getMockForAbstractClass();

    $type->expects($this->never())->method('nullValue');

    $this->expectException(ElementNotFoundException::class);
    $return = $type->process();
    $this->assertEquals('Test', $return);
  }

  /**
   * Test that processDom is called if no selector is given.
   * @group type_base_test
   */
  public function testNoSelector() {
    $row = new \stdClass();
    $config = [
      'field' => 'test',
    ];

    $type = $this->getMockBuilder(TypeBase::class)
      ->setConstructorArgs([
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        $config,
      ])
      ->setMethods(['processDom'])
      ->getMockForAbstractClass();

    $type->expects($this->once())
      ->method('processDom')
      ->willReturn(TRUE);

    $type->process();
  }


}
