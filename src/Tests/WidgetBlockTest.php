<?php
/**
 * @file
 * Contains \Drupal\widget\Tests\WidgetBlockTest.
 */

namespace Drupal\widget\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Generic widget block tests.
 *
 * @group widget
 */
class WidgetBlockTest extends WebTestBase {

  public static $modules = [
    'block',
    'layout',
    'ctools',
    'page_manager',
    'page_layout',
    'widget',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('widget_block');
    $admin = $this->drupalCreateUser([
      'administer blocks',
      'administer pages',
    ], $this->randomMachineName(), TRUE);
    $this->drupalLogin($admin);
  }

  /**
   * Tests a form without submit handler.
   */
  public function testFormNoSubmitHandler() {
    $edit = [
      'settings[layout]' => 'widget_1col',
    ];
    $this->drupalPostAjaxForm('admin/structure/block/add/widget_block/classy?region=content', $edit, 'settings[layout]');
    $edit = [
      'settings[layout]' => 'widget_1col',
      'settings[blocks][links][id]' => 'system_menu_block:main',
      'settings[blocks][main][id]' => 'system_main_block',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save block');
    $this->assertText(t('The block configuration has been saved.'));
    $this->drupalPlaceBlock('widget_block');
    $this->drupalGet('<front>');
  }

}
