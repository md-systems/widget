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
    return 'widget';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    //kint(Views::getAllViews());


    $form['selected'] = array(
      '#type' => 'select',
      '#title' => t('Selected'),
      '#options' => array(
        0 => t('No'),
        1 => t('Yes'),
      ),
      '#default_value' => $category['selected'],
      '#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
    );


    $form['views_select'] = array(
      '#type' => 'select',
      '#title' => $this->t('Webcode'),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#required' => TRUE,
      '#default_value' => '',
      '#attributes' => array('title' => $this->t('Enter your Webcode.')),
      '#placeholder' => $this->t('Enter a webcode'),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => $this->t('Go'));

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {

    // Check if path exsits or the User has access.
    $nid = $this->webcodeManager->getNode($form_state['values']['webcode']);

    if ($nid) {
      $form_state['redirect_route'] = new Url('node.view', array('node' => $nid));
    }
    else {
      drupal_set_message(t('Your Webcode %webcode can not be found.', array('%webcode' => $form_state['values']['webcode'])), 'error');
    }
  }
}
