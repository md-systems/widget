<?php
/**
 * @file
 * Contains \Drupal\widget\Plugin\Block\WidgetBlock.
 */

namespace Drupal\widget\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\layout\Layout;
use Drupal\layout\LayoutRendererBlockAndContext;
use Drupal\layout\Plugin\Layout\LayoutBlockAndContextProviderInterface;
use Drupal\layout\Plugin\Layout\LayoutInterface;
use Drupal\layout\Plugin\LayoutRegion\LayoutRegionPluginBag;
use Drupal\page_manager\Plugin\BlockPluginBag;

/**
 * Provides a 'widget' block.
 *
 * @Block(
 *   id = "widget_block",
 *   admin_label = @Translation("Widget"),
 *   category = @Translation("Widget")
 * )
 */

class WidgetBlock extends BlockBase implements LayoutBlockAndContextProviderInterface {

  use PluginDependencyTrait;

  /**
   * The block manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * The plugin bag that holds the block plugins.
   *
   * @var \Drupal\page_manager\Plugin\BlockPluginBag
   */
  protected $blockPluginBag;

  /**
   * Layout regions.
   *
   * @var \Drupal\layout\Plugin\LayoutRegion\LayoutRegionPluginBag
   */
  public $layoutRegionBag;

  public function defaultConfiguration() {
    return array(
      'blocks' => array(),
      'layout' => NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function addLayoutRegion(array $configuration) {
    // layout regions expect to have a UUID.
    $configuration['uuid'] = $configuration['region_id'];
    $this->getLayoutRegions()->addInstanceId($configuration['region_id'], $configuration);
    return $configuration['region_id'];
  }

  /**
   * Initializes the page variant regions on the basis of given layout.
   *
   * @param \Drupal\layout\Plugin\Layout\LayoutInterface $layout
   *
   * @return \Drupal\layout\Plugin\LayoutRegion\LayoutRegionPluginBag
   */
  protected function initializeLayoutRegionsFromLayout(LayoutInterface $layout) {
    $this->configuration['regions'] = array();
    $definitions = $layout ? $layout->getRegionDefinitions() : array();
    $weight = 0;
    foreach ($definitions as $id => $regionPluginDefinition) {
      $this->addLayoutRegion(array(
        'id' => !empty($regionPluginDefinition['plugin_id']) ? $regionPluginDefinition['plugin_id'] : 'default',
        'region_id' => $id,
        'label' => $regionPluginDefinition['label'],
        'weight' => $weight,
      ));
      $weight++;
    }
    $this->configuration['regions'] = $this->getLayoutRegions()->getConfiguration();
    return $this->getLayoutRegions();
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutRegions() {
    if (!isset($this->layoutRegionBag) || !$this->layoutRegionBag) {
      if (!isset($this->configuration['regions'])) {
        return $this->initializeLayoutRegionsFromLayout($this->getLayout());
      }

      $regions_data = $this->configuration['regions'];
      $this->layoutRegionBag = new LayoutRegionPluginBag(Layout::layoutRegionPluginManager(),
        $regions_data
      );

      $this->layoutRegionBag->sort();
    }
    return $this->layoutRegionBag;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (isset($this->configuration['layout']) && !empty($this->configuration['blocks'])) {
      $renderer = new LayoutRendererBlockAndContext(\Drupal::service('context.handler'), \Drupal::currentUser());
      $build = $renderer->build($this->getLayout(), $this);

      // Add JS that builds tabs of the content if tabbed layout is selected.
      if ($this->configuration['layout'] == 'widget_main_with_quicktabs') {
        $build['#attached']['library'][] = 'widget/widget.tabs';
      }
      else {
        $build['#attached']['css'][] = drupal_get_path('module', 'widget') . '/widget.css';
      }
      return $build;
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $block_plugins = \Drupal::service('plugin.manager.block')->getDefinitionsForContexts(array());

    $block_options = array();

    foreach ($block_plugins as $plugin_id => $block_definition) {
      $block_options[(string) $block_definition['category']][$plugin_id] = (string) $block_definition['admin_label'];
    }

    $widget_blocks = !empty($form_state['values']['settings']['blocks']) ? $form_state['values']['settings']['blocks'] : $this->configuration['blocks'];
    $layout = !empty($form_state['values']['settings']['layout']) ? $form_state['values']['settings']['layout'] : $this->configuration['layout'];

    $ajax_properties =  array(
      '#ajax' => array(
        'callback' => array($this, 'widgetBlockAJAXCallback'),
        'wrapper' => 'widget-block-wrapper',
        'effect' => 'fade',
      ),
    );

    $form = parent::buildConfigurationForm($form, $form_state);

    $layouts = array();
    foreach (Layout::layoutPluginManager()->getDefinitions() as $id => $definition) {
      if ($definition['type'] == 'partial') {
        $layouts[$id] = $definition['label'];
      }
    }

    $form['layout'] = array(
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => t('Widget layout'),
      '#options' => $layouts,
      '#default_value' => $layout,
    ) + $ajax_properties;

    $form['blocks'] = array(
      '#type' => 'container',
      '#prefix' => '<div id="widget-block-wrapper">',
      '#suffix' => '</div>',
    );

    if (!$layout) {
      return $form;
    }
    if ($layout != $this->configuration['layout']) {
      $this->configuration['layout'] = $layout;
      $this->configuration['regions'] = NULL;
      $this->layoutRegionBag = NULL;
    }
    foreach ($this->getLayoutRegions() as $region_id => $region_definition) {
      $block_config = isset($widget_blocks[$region_id]) ? $widget_blocks[$region_id] : array();
      $form['blocks'][$region_id] = array(
        '#type' => 'details',
        '#title' => $region_definition->getConfiguration()['label'],
        '#open' => TRUE,
      );

      $form['blocks'][$region_id]['id'] = array(
        '#type' => 'select',
        '#title' => t('Block'),
        '#options' => $block_options,
        //'#required' => TRUE,
        '#default_value' => isset($block_config['id']) ? $block_config['id'] : NULL,
      ) + $ajax_properties;

      $form['blocks'][$region_id]['region'] = array(
        '#type' => 'value',
        '#value' => $region_id,
      );

      if (!empty($block_config['id'])) {
        $block_plugin = \Drupal::service('plugin.manager.block')->createInstance($block_config['id'], $block_config);
        $form['blocks'][$region_id] += $block_plugin->buildConfigurationForm(array(), $form_state);
        // @todo Support per-block caching and visibility. Breaks UI right now.
        unset($form['blocks'][$region_id]['cache']);
        unset($form['blocks'][$region_id]['visibility']);
        unset($form['blocks'][$region_id]['visibility_tabs']);
      }
      else {
        //unset($form['blocks'][$region_id]);
      }
    }

    return $form;
  }

  /**
   * @{@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    $this->configuration['blocks'] = $form_state['values']['blocks'];
    $this->configuration['layout'] = $form_state['values']['layout'];
  }

  /**
   * Used by select widgets of block configuration form.
   */
  public function widgetBlockAJAXCallback($form, &$form_state) {
    return $form['settings']['blocks'];
  }

  protected function getBlockBag() {
    if (!$this->blockPluginBag) {
      $this->blockPluginBag = new BlockPluginBag(\Drupal::service('plugin.manager.block'), $this->configuration['blocks']);
    }
    return $this->blockPluginBag;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlocksByRegion($region_id) {
    $all_by_region = $this->getBlockBag()->getAllByRegion();
    return isset($all_by_region[$region_id]) ? $all_by_region[$region_id] : array();
  }

  /**
   * @return \Drupal\layout\Plugin\Layout\LayoutInterface
   */
  protected function getLayout() {
    if ($this->configuration['layout']) {
      $layout = \Drupal::service('plugin.manager.layout')->createInstance($this->configuration['layout']);
      return $layout;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    foreach ($this->getBlockBag() as $block) {
      $this->calculatePluginDependencies($block);
    }
    if ($this->getLayout()) {
      foreach ($this->getLayoutRegions() as $region) {
        $this->calculatePluginDependencies($region);
      }
    }
    return $this->dependencies;
  }


}
