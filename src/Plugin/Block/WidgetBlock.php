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

    $enabled_views = Views::getEnabledViews();
    foreach($enabled_views as $k => $v) {
      $views = $v->get('display');
      foreach($views as $display => $params) {
        if($params['display_title'] != 'Master') {
          $options[$k][$params['id']] = $params['display_title'];
        }
      }
    }
    //var_dump($options);

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['selected'] = array(
      '#type' => 'select',
      '#title' => t('Selected'),
      '#options' => $options,
      '#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
    );

    return $form;
  }

}
