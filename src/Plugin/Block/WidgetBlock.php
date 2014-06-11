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
    $config = \Drupal::configFactory()->get('block.block.widget');
    $view_to_diplay = $config->get('settings.ViewToDisplay');
    $view_data = explode('.', $view_to_diplay);



    if (isset($view_to_diplay) == !empty($view_to_diplay) && $view_to_diplay !='--None--') {

      $view = Views::getView($view_data[0]);
      return $view->render($view_data[1]);

    }
    else {
      drupal_set_message(t('No View is set in the configuration'), 'warning');
      return '';
    }
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
      '#default_value' => $config->get('settings.ViewToDisplay'),
      '#empty_option' => t('--None--')

    );

    return $form;
  }

}
