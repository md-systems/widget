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
    if (isset($this->configuration['widget_layout']) && !empty($this->configuration['widget_blocks_config'])) {
      $output = array();

      foreach ($this->layouts[$this->configuration['widget_layout']] as $region_delta => $region_name) {
        $block_config = $this->configuration['widget_blocks_config'][$region_delta]['block_settings'];
        $block_id = $this->configuration['widget_blocks_config'][$region_delta]['block_id'];
        $block_plugin_bag = new BlockPluginBag(\Drupal::service('plugin.manager.block'), $block_id, $block_config, $block_id);
        $block = $block_plugin_bag->get($block_id);

        if ($block->access(\Drupal::currentUser())) {
          $row = $block->build();
          $block_name = drupal_html_class("block-$block_id}");
          $row['#prefix'] = '<div class="' . $block_name . '">';
          $row['#suffix'] = '</div>';
          $output[] = $row;
        }
      }

      return $output;
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

    $widget_blocks = empty($this->configuration['widget_blocks_config']) ? $form_state['values']['settings']['blocks'] : $this->configuration['widget_blocks_config'];
    $widget_layout = !empty($form_state['values']['settings']['widget_layout']) ? $form_state['values']['settings']['widget_layout'] : $this->configuration['widget_layout'];
    $ajax_properties =  array(
      '#ajax' => array(
        'callback' => array($this, 'widgetBlockAJAXCallback'),
        'wrapper' => 'widget-block-wrapper',
        'effect' => 'fade',
      ),
    );

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['widget_layout'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Widget layout'),
      '#options' => array_combine(array_keys($this->layouts), array_keys($this->layouts)),
      '#default_value' => $widget_layout,
    ) + $ajax_properties;

    $form['blocks'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="widget-block-wrapper">',
      '#suffix' => '</div>',
    );

    foreach ($this->layouts[$widget_layout] as $region_delta => $region_name) {
      $block_config = $widget_blocks[$region_delta];
      $form['blocks'][$region_delta] = array(
        '#type' => 'details',
        '#title' => $region_name,
        '#open' => TRUE,
      );

      $form['blocks'][$region_delta]['block_id'] = array(
        '#type' => 'select',
        '#title' => t('Block'),
        '#options' => $block_options,
        '#required' => TRUE,
        '#default_value' => isset($block_config['block_id']) ? $block_config['block_id'] : NULL,
      ) + $ajax_properties;

      if (!empty($block_config['block_id'])) {
        if (!empty($block_config['block_settings'])) {
          $block_plugin = \Drupal::service('plugin.manager.block')->createInstance($block_config['block_id'], $block_config['block_settings']);
        }
        else {
          $block_plugin = \Drupal::service('plugin.manager.block')->createInstance($block_config['block_id']);
        }

        $form['blocks'][$region_delta]['block_settings'] = array(
          '#type' => 'details',
          '#title' => t('Block settings'),
          '#open' => FALSE,
          'settings' => $block_plugin->buildConfigurationForm(array(), $form_state),
        );
      }
      else {
        unset($form['blocks'][$region_delta]['block_settings']);
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

  /**
   * Used by select widgets of block configuration form.
   */
  public function widgetBlockAJAXCallback($form, &$form_state) {
    return $form['settings']['blocks'];
  }

}
