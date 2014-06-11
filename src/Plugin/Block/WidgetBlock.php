<?php

/**
 * @file
 * Contains \Drupal\np8_webcode\Plugin\Block\WebcodeBlock.
 */

namespace Drupal\widget\Plugin\Block;

use Drupal\block\BlockBase;
use Drupal\views\Views;
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
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\widget\Form\WidgetBlockForm');
  }

  public function buildConfigurationForm(array $form, array &$form_state) {

    $options = array();
    $config = \Drupal::configFactory()->get('block.block.widget');

    $enabled_views = Views::getEnabledViews();
    foreach($enabled_views as $k => $v) {
      $views = $v->get('display');
      foreach($views as $display => $params) {
        if($params['display_title'] != 'Master') {
          $options[$k][$k.'.'.$params['id']] = $params['display_title'];
        }
      }
    }

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['ViewToDisplay'] = array(
      '#type' => 'select',
      '#title' => t('View to Display'),
      '#options' => $options,
      '#default_value' => $config->get('settings.ViewToDisplay')
    );

    return $form;
  }

}
