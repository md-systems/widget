<?php
/**
 * @file
 * Contains \Drupal\widget\Plugin\Block\WidgetBlock.
 */

namespace Drupal\widget\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\BlockPluginBag;

/**
 * Provides a 'widget' block.
 *
 * @Block(
 *   id = "widget_block",
 *   admin_label = @Translation("Widget"),
 *   category = @Translation("Block")
 * )
 */

class WidgetBlock extends BlockBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public function build() {

    if (isset($this->configuration['block_settings']) && !empty($this->configuration['block_settings'])) {
      $block_options = $this->configuration['block_settings'];

      $block_plugin_bag = new BlockPluginBag(\Drupal::service('plugin.manager.block'), $block_options['id'], $block_options, $block_options['id']);
      $block = $block_plugin_bag->get($block_options['id']);

      if ($block->access(\Drupal::currentUser())) {
        $row = $block->build();
        $block_name = drupal_html_class("block-{$block_options['id']}");
        $row['#prefix'] = '<div class="' . $block_name . '">';
        $row['#suffix'] = '</div>';
        return $row;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {

    $available_plugins = \Drupal::service('plugin.manager.block')->getDefinitionsForContexts(array());

    $block_options = array();

    foreach ($available_plugins as $k => $v) {
      foreach ($v as $display => $params) {
        $block_options[(string) $v['category']][$k] = (string) $v['admin_label'];
      }
    }

    $block_to_display = $this->configuration['block_to_display'];

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['block_to_display'] = array(
      '#tree' => TRUE,
      '#type' => 'select',
      '#title' => t('Block to Display'),
      '#options' => $block_options,
      '#default_value' => $block_to_display,
      '#empty_option' => t('--None--'),
    );

    if (!empty($form_state['block_id'])) {
      if (empty($form_state['block_id'])) {
        $form_state['block_id'] = $block_to_display;
      }

      if (!empty($this->configuration['block_settings'])) {
        $block_form = \Drupal::service('plugin.manager.block')->createInstance($form_state['block_id'], $this->configuration['block_settings']);
      }
      else {
        $block_form = \Drupal::service('plugin.manager.block')->createInstance($form_state['block_id']);
      }
      $form['block_settings'] = $block_form->buildConfigurationForm(array(), $form_state);
      $form['block_settings']['id'] = array(
        '#type' => 'value',
        '#value' => $form_state['block_id'],
      );
    }

    $form['block_to_display_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Configure'),
      '#submit' => array(array($this, 'submitBlockSelect')),
    );

    return $form;
  }

  /**
   * @{@inheritdoc}
   */
  public function submitBlockSelect(array $form, array &$form_state) {
    $block_to_display = $form_state['values']['settings']['block_to_display'];

    if ($block_to_display != $this->configuration['block_to_display']) {
      unset($this->configuration['block_settings']);
      unset($form_state['values']['settings']['block_settings']);
    }

    $form_state['block_id'] = $block_to_display;
    $form_state['rebuild'] = 'TRUE';
  }

}
