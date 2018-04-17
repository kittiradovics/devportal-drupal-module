<?php

namespace Drupal\devportal_api_entities;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Class APIHeaderParamTypeForm.
 *
 * @package Drupal\devportal_api_entities\Form
 */
class APIHeaderParamTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $api_header_param_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $api_header_param_type->label(),
      '#description' => $this->t("Label for the API HTTP Method Header Parameter type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $api_header_param_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\devportal_api_entities\Entity\APIHeaderParamType::load',
      ],
      '#disabled' => !$api_header_param_type->isNew(),
    ];

    // $form['langcode'] is not wrapped in an
    // if ($this->moduleHandler->moduleExists('language')) check because the
    // language_select form element works also without the language module being
    // installed. https://www.drupal.org/node/1749954 documents the new element.
    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('API HTTP Method Header Parameter type language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $api_header_param_type->language()->getId(),
    ];
    if ($this->moduleHandler->moduleExists('language')) {
      $form['default_terms_language'] = [
        '#type' => 'details',
        '#title' => $this->t('API HTTP Method Header Parameters language'),
        '#open' => TRUE,
      ];
      $form['default_terms_language']['default_language'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'api_header_param',
          'bundle' => $api_header_param_type->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('api_header_param', $api_header_param_type->id()),
      ];
    }

    switch ($this->operation) {
      case 'edit':
        $form['#title'] = $this->t('Edit API HTTP Method Header Parameter type %label', ['%label' => $api_header_param_type->label()]);
        break;

      case 'add':
        $form['#title'] = $this->t('Add API HTTP Method Header Parameter type');
        break;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $api_header_param_type = $this->entity;
    $status = $api_header_param_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label API HTTP Method Header Parameter type.', [
          '%label' => $api_header_param_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label API HTTP Method Header Parameter type.', [
          '%label' => $api_header_param_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($api_header_param_type->toUrl('collection'));
  }

}
