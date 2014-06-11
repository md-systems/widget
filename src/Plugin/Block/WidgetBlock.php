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

    kint(Views::getAllViews());

    $all_views = Views::getAllViews();

    foreach($all_views as $k => $v) {
      $v->getDisplay($v->id);
      kint(Views::getView($v->id));
    }

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['selected'] = array(
      '#type' => 'select',
      '#title' => t('Selected'),
      '#options' => array(
        0 => t('No'),
        1 => t('Yes'),
      ),
      '#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
    );

    return $form;
  }

}
