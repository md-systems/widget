<?php

/**
 * @file
 * Contains \Drupal\widget\Form\WidgetBlockForm.
 */

namespace Drupal\widget\Form;

use Drupal\widget\WebcodeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the search form for the search block.
 */
class WidgetBlockForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_to_display';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {

      $form_state['rebuildForm'] = new Url('node.view', array('node' => $nid));
  }
}
