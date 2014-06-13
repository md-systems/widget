<?php
/**
 * @file
 * Contains \Drupal\widget\Plugin\Block\WidgetBlock.
 */

namespace Drupal\widget\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\block\BlockPluginBag;
use Drupal\layout\Layout;

/**
 * Provides a 'widget' block.
 *
 * @Block(
 *   id = "widget_block",
 *   admin_label = @Translation("Widget"),
 *   category = @Translation("Widget")
 * )
 */

class WidgetBlock extends BlockBase {

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  public function defaultConfiguration() {
    return array(
      'widget_blocks_config' => array(),
      'widget_layout' => NULL,
    );
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    if (isset($this->configuration['widget_layout']) && !empty($this->configuration['widget_blocks_config'])) {
      $output = array();

      $layout = \Drupal::service('plugin.manager.layout')->createInstance($this->configuration['widget_layout']);
      // @todo: use the layout.
      foreach ($layout->getRegionNames() as $region_id => $region_name) {
        $block_config = $this->configuration['widget_blocks_config'][$region_id]['block_settings'];
        $block_id = $this->configuration['widget_blocks_config'][$region_id]['block_id'];
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

    $block_plugins = \Drupal::service('plugin.manager.block')->getDefinitionsForContexts(array());

    $block_options = array();

    foreach ($block_plugins as $plugin_id => $block_definition) {
      $block_options[(string) $block_definition['category']][$plugin_id] = (string) $block_definition['admin_label'];
    }

    $widget_blocks = !empty($form_state['values']['settings']['blocks']) ? $form_state['values']['settings']['blocks'] : $this->configuration['widget_blocks_config'];
    $widget_layout = !empty($form_state['values']['settings']['widget_layout']) ? $form_state['values']['settings']['widget_layout'] : $this->configuration['widget_layout'];

    $ajax_properties =  array(
      '#ajax' => array(
        'callback' => array($this, 'widgetBlockAJAXCallback'),
        'wrapper' => 'widget-block-wrapper',
        'effect' => 'fade',
      ),
    );

    $form = parent::buildConfigurationForm($form, $form_state);

    $layouts = Layout::getLayoutOptions();

    $form['widget_layout'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Widget layout'),
      '#options' => $layouts,
      '#default_value' => $widget_layout,
    ) + $ajax_properties;

    $form['blocks'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="widget-block-wrapper">',
      '#suffix' => '</div>',
    );

    if (!$widget_layout) {
      return $form;
    }

    /* @var \Drupal\layout\Plugin\Layout\LayoutInterface $layout */
    $layout = \Drupal::service('plugin.manager.layout')->createInstance($widget_layout);
    foreach ($layout->getRegionNames() as $region_id => $region_name) {
      $block_config = $widget_blocks[$region_id];
      $form['blocks'][$region_id] = array(
        '#type' => 'details',
        '#title' => $region_name,
        '#open' => TRUE,
      );

      $form['blocks'][$region_id]['block_id'] = array(
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

        $form['blocks'][$region_id]['block_settings'] = array(
          '#type' => 'details',
          '#title' => t('Block settings'),
          '#open' => FALSE,
          'settings' => $block_plugin->buildConfigurationForm(array(), $form_state),
        );
      }
      else {
        unset($form['blocks'][$region_id]['block_settings']);
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
