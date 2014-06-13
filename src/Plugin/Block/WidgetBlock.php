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

  protected $layouts;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // @todo get the layouts and regions from the layout module instead.
    $this->layouts = array(
      'widget_two_column' => array(
        'region_left' => t('Left column'),
        'region_right' => t('Right column'),
      ),
    );
  }

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

    if (!isset($form_state['block_count'])) {
      $form_state['block_count'] = 1;
    }

    $available_plugins = \Drupal::service('plugin.manager.block')->getDefinitionsForContexts(array());

    $block_options = array();

    foreach ($available_plugins as $k => $v) {
      foreach ($v as $display => $params) {
        $block_options[(string) $v['category']][$k] = (string) $v['admin_label'];
      }
    }

    $blocks_config = empty($this->configuration['widget_blocks_config']) ? $form_state['values']['settings']['blocks'] : $this->configuration['widget_blocks_config'];
    $widget_layout = !empty($form_state['values']['settings']['widget_layout']) ? $form_state['values']['settings']['widget_layout'] : $this->configuration['widget_layout'];

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['widget_layout'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Widget layout'),
      '#options' => array_combine(array_keys($this->layouts), array_keys($this->layouts)),
      '#default_value' => $widget_layout,
      '#ajax' => array(
        'callback' => array($this, 'layoutAJAXCallback'),
        'wrapper' => 'widget-block-wrapper',
        'effect' => 'fade',
        'method' => 'replace',
      ),
    );

    $form['blocks'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="widget-block-wrapper">',
      '#suffix' => '</div>',
    );

    foreach ($this->layouts[$widget_layout] as $region_delta => $region_name) {
      $form['blocks'][$region_delta] = array(
        '#type' => 'fieldset',
        '#title' => $region_name,
        '#collapsed' => TRUE,
      );

      $block_config = $blocks_config[$region_delta];
      $form['blocks'][$region_delta]['block_id'] = array(
        '#type' => 'select',
        '#title' => t('Block'),
        '#options' => $block_options,
        '#default_value' => isset($block_config['block_id']) ? $block_config['block_id'] : NULL,
        '#empty_option' => t('--None--'),
      );

      if (!empty($block_config['block_id'])) {
        if (!empty($block_config['block_settings'])) {
          $block_plugin = \Drupal::service('plugin.manager.block')->createInstance($block_config['block_id'], $block_config['block_settings']);
        }
        else {
          $block_plugin = \Drupal::service('plugin.manager.block')->createInstance($block_config['block_id']);
        }

        $form['blocks'][$region_delta]['block_settings'] = $block_plugin->buildConfigurationForm(array(), $form_state);
      }
    }

    return $form;
  }

  /**
   * @{@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['widget_blocks_config'] = $form_state['values']['blocks'];
    $this->configuration['widget_layout'] = $form_state['values']['widget_layout'];
  }

  public function layoutAJAXCallback($form, &$form_state) {
    return $form['settings']['blocks'];
  }

}
