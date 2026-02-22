<?php

declare(strict_types=1);

namespace Drupal\selectify_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Test form for Selectify Form API integration.
 *
 * This form contains plain Form API select elements (not field widgets)
 * to test Selectify's Form API integration.
 */
class SelectifyTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'selectify_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['description'] = [
      '#markup' => '<p>This form contains plain Form API select elements (not field widgets) for testing Selectify Form API integration.</p>',
    ];

    // Single select dropdown.
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#description' => $this->t('Single select - should use Selectify widget'),
      '#options' => [
        '' => $this->t('- Select a country -'),
        'us' => $this->t('United States'),
        'uk' => $this->t('United Kingdom'),
        'ca' => $this->t('Canada'),
        'au' => $this->t('Australia'),
        'de' => $this->t('Germany'),
        'fr' => $this->t('France'),
        'it' => $this->t('Italy'),
        'es' => $this->t('Spain'),
        'nl' => $this->t('Netherlands'),
        'be' => $this->t('Belgium'),
        'ch' => $this->t('Switzerland'),
        'at' => $this->t('Austria'),
        'se' => $this->t('Sweden'),
        'no' => $this->t('Norway'),
        'dk' => $this->t('Denmark'),
        'fi' => $this->t('Finland'),
        'pl' => $this->t('Poland'),
        'cz' => $this->t('Czech Republic'),
        'ie' => $this->t('Ireland'),
      ],
      '#required' => TRUE,
    ];

    // Multi-select dropdown.
    $form['categories'] = [
      '#type' => 'select',
      '#title' => $this->t('Categories'),
      '#description' => $this->t('Multi-select - should use Selectify widget'),
      '#options' => [
        'technology' => $this->t('Technology'),
        'business' => $this->t('Business'),
        'science' => $this->t('Science'),
        'arts' => $this->t('Arts'),
        'sports' => $this->t('Sports'),
        'health' => $this->t('Health'),
        'education' => $this->t('Education'),
        'entertainment' => $this->t('Entertainment'),
        'travel' => $this->t('Travel'),
        'food' => $this->t('Food & Dining'),
      ],
      '#multiple' => TRUE,
    ];

    // Another single select.
    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#description' => $this->t('Another single select'),
      '#options' => [
        'low' => $this->t('Low'),
        'medium' => $this->t('Medium'),
        'high' => $this->t('High'),
        'urgent' => $this->t('Urgent'),
      ],
      '#default_value' => 'medium',
    ];

    // A select with many options (good for testing searchable widget).
    $form['state'] = [
      '#type' => 'select',
      '#title' => $this->t('US State'),
      '#description' => $this->t('Many options - good for testing searchable dropdown'),
      '#options' => [
        '' => $this->t('- Select a state -'),
        'AL' => $this->t('Alabama'),
        'AK' => $this->t('Alaska'),
        'AZ' => $this->t('Arizona'),
        'AR' => $this->t('Arkansas'),
        'CA' => $this->t('California'),
        'CO' => $this->t('Colorado'),
        'CT' => $this->t('Connecticut'),
        'DE' => $this->t('Delaware'),
        'FL' => $this->t('Florida'),
        'GA' => $this->t('Georgia'),
        'HI' => $this->t('Hawaii'),
        'ID' => $this->t('Idaho'),
        'IL' => $this->t('Illinois'),
        'IN' => $this->t('Indiana'),
        'IA' => $this->t('Iowa'),
        'KS' => $this->t('Kansas'),
        'KY' => $this->t('Kentucky'),
        'LA' => $this->t('Louisiana'),
        'ME' => $this->t('Maine'),
        'MD' => $this->t('Maryland'),
        'MA' => $this->t('Massachusetts'),
        'MI' => $this->t('Michigan'),
        'MN' => $this->t('Minnesota'),
        'MS' => $this->t('Mississippi'),
        'MO' => $this->t('Missouri'),
        'MT' => $this->t('Montana'),
        'NE' => $this->t('Nebraska'),
        'NV' => $this->t('Nevada'),
        'NH' => $this->t('New Hampshire'),
        'NJ' => $this->t('New Jersey'),
        'NM' => $this->t('New Mexico'),
        'NY' => $this->t('New York'),
        'NC' => $this->t('North Carolina'),
        'ND' => $this->t('North Dakota'),
        'OH' => $this->t('Ohio'),
        'OK' => $this->t('Oklahoma'),
        'OR' => $this->t('Oregon'),
        'PA' => $this->t('Pennsylvania'),
        'RI' => $this->t('Rhode Island'),
        'SC' => $this->t('South Carolina'),
        'SD' => $this->t('South Dakota'),
        'TN' => $this->t('Tennessee'),
        'TX' => $this->t('Texas'),
        'UT' => $this->t('Utah'),
        'VT' => $this->t('Vermont'),
        'VA' => $this->t('Virginia'),
        'WA' => $this->t('Washington'),
        'WV' => $this->t('West Virginia'),
        'WI' => $this->t('Wisconsin'),
        'WY' => $this->t('Wyoming'),
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Test Form'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->messenger()->addStatus($this->t('Form submitted! Country: @country, Categories: @categories, Priority: @priority, State: @state', [
      '@country' => $values['country'] ?? 'none',
      '@categories' => is_array($values['categories']) ? implode(', ', $values['categories']) : 'none',
      '@priority' => $values['priority'] ?? 'none',
      '@state' => $values['state'] ?? 'none',
    ]));
  }

}
