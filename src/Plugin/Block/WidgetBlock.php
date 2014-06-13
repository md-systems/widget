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

    $selected_blocks = empty($this->configuration['widget_blocks']) ? NULL : $this->configuration['widget_blocks'];
    $widget_layout = empty($this->configuration['widget_layout']) ? NULL : $this->configuration['widget_layout'];

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['widget_layout'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Widget layout'),
      '#options' => array_keys($this->layouts),
      '#default_value' => $widget_layout,
      '#submit' => array($this, 'saveLayoutAJAXCallbacksaveLayout'),
      '#ajax' => array(
        'callback' => array($this, 'saveLayoutAJAXCallback'),
        'wrapper' => 'widget-block-wrapper',
        'effect' => 'fade',
      ),
    );

    $form['blocks'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="widget-block-wrapper">',
      '#suffix' => '</div>',
    );

    if ($widget_layout) {
      foreach ($this->layouts[$widget_layout] as $region_delta => $region_name) {
        $form['blocks']['widget_block_' . $region_name] = array(
          '#type' => 'select',
          '#title' => t('Block in') . ' ' . $region_name,
          '#options' => $block_options,
          '#default_value' => isset($selected_blocks[$region_delta]) ? $selected_blocks[$region_delta] : NULL,
          '#empty_option' => t('--None--'),
        );
      }
    }

    /*
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

    /*
    $form['block_to_display_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Configure'),
      '#submit' => array(array($this, 'submitBlockSelect')),
    );

    $form['add_block'] = array(
      '#type' => 'submit',
      '#value' => t('Add block'),
      '#submit' => array(array($this, 'addBlockSubmit')),
      '#ajax' => array(
        'callback' => array($this, 'addMoreCallback'),
        'wrapper' => 'foo-replace',
        'effect' => 'fade',
      ),
    );
    */

    return $form;
  }

  /**
   * @{@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['block_to_display'] = $form_state['values']['block_to_display'];
  }

  public function saveLayoutAJAXCallback($form, &$form_state) {
    return $form['blocks'];
  }

  public function saveLayoutSubmit($form, &$form_state) {
    $this->configuration['widget_layout'] = $form_state['values']['widget_layout'];
    $form_state['rebuild'] = TRUE;
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

    $this->configuration['block_to_display'] = $block_to_display;

    $form_state['block_id'] = $block_to_display;
    $form_state['rebuild'] = 'TRUE';
  }

}
