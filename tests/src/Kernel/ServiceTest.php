<?php

namespace Drupal\Tests\psu_form_error_handler\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\psu_form_error_handler\FormErrorHandler;

/**
 * Test cases for service container operations.
 *
 * @group psu_form_error_handler
 */
class ServiceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'inline_form_errors',
    'psu_form_error_handler',
  ];

  /**
   * Test case that ensures the service is effectively swapped out.
   */
  public function testService() {
    static::assertSame(
      FormErrorHandler::class,
      get_class(\Drupal::service('form_error_handler'))
    );
  }

}
