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
        '' => '- Select a country -',
        'us' => 'United States',
        'uk' => 'United Kingdom',
        'ca' => 'Canada',
        'au' => 'Australia',
        'de' => 'Germany',
        'fr' => 'France',
        'it' => 'Italy',
        'es' => 'Spain',
        'nl' => 'Netherlands',
        'be' => 'Belgium',
        'ch' => 'Switzerland',
        'at' => 'Austria',
        'se' => 'Sweden',
        'no' => 'Norway',
        'dk' => 'Denmark',
        'fi' => 'Finland',
        'pl' => 'Poland',
        'cz' => 'Czech Republic',
        'ie' => 'Ireland',
      ],
      '#required' => TRUE,
    ];

    // Multi-select dropdown.
    $form['categories'] = [
      '#type' => 'select',
      '#title' => $this->t('Categories'),
      '#description' => $this->t('Multi-select - should use Selectify widget'),
      '#options' => [
        'technology' => 'Technology',
        'business' => 'Business',
        'science' => 'Science',
        'arts' => 'Arts',
        'sports' => 'Sports',
        'health' => 'Health',
        'education' => 'Education',
        'entertainment' => 'Entertainment',
        'travel' => 'Travel',
        'food' => 'Food & Dining',
      ],
      '#multiple' => TRUE,
    ];

    // Another single select.
    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#description' => $this->t('Another single select'),
      '#options' => [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
      ],
      '#default_value' => 'medium',
    ];

    // A select with many options (good for testing searchable widget).
    $form['state'] = [
      '#type' => 'select',
      '#title' => $this->t('US State'),
      '#description' => $this->t('Many options - good for testing searchable dropdown'),
      '#options' => [
        '' => '- Select a state -',
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
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
