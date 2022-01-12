<?php

namespace Drupal\finale\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Create Awesome form.
 */
class FinaleForm extends FormBase {

  protected $header;

  protected $tableCount = 1;

  protected $rowCount = 1;


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'awesome_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Year'),
    ];

    $form['add_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Table'),
    ];

    $this->buildTable($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public function createHeader() {
    $this->header = [
      'year' => $this->t('Year'),
      'jan' => $this->t('Jan'),
      'feb' => $this->t('Feb'),
      'mar' => $this->t('Mar'),
      'q1' => $this->t('Q1'),
      'apr' => $this->t('Apr'),
      'may' => $this->t('May'),
      'jun' => $this->t('Jun'),
      'q2' => $this->t('Q2'),
      'jul' => $this->t('Jul'),
      'aug' => $this->t('Aug'),
      'sep' => $this->t('Sep'),
      'q3' => $this->t('Q3'),
      'oct' => $this->t('Oct'),
      'nov' => $this->t('Nov'),
      'dec' => $this->t('Dec'),
      'q4' => $this->t('Q4'),
      'ytd' => $this->t('YTD'),
    ];
  }


  public function calculateQuarter(array &$form, FormStateInterface $form_state) {

  }

  public function buildTable(array &$form, FormStateInterface $form_state) {
    $this->createHeader();
    for ($i = 0; $i < $this->tableCount; $i++) {
      $tableID = 'table-id-' . ($i + 1);
      $form[$tableID] = [
        '#type' => 'table',
        '#header' => $this->header,
      ];
      $this->createRow( $form, $form_state, $tableID);
    }

  }

  /**
   * Function that create a row for table.
   * @param array $form
   * @param FormStateInterface $form_state
   * @param string $tableID
   * @return void
   */
  public function createRow(array &$form, FormStateInterface $form_state, string $tableID) {
    for($i = 0; $i < $this->rowCount; $i++) {
      $rowID = 'row-id-' . ($i + 1);
      $header_arr_key = array_keys($this->header); // Get keys from header.
      for ($j = 0; $j < count($this->header); $j++) {
        $colID = $header_arr_key[$j];
        switch ($colID) {
          case 'year':
            $form[$tableID][$rowID][$colID] = [
              "#type" => 'number',
              '#default_value' => date('Y', strtotime("-$i year")),
              '#disabled' => TRUE,
            ];
            break;
          case 'q1':
          case 'q2':
          case 'q3':
          case 'q4':
            $form[$tableID][$rowID][$colID] = [
              "#type" => 'number',
              '#disabled' => TRUE,
              '#step' => 0.01,
            ];
            break;
          default:
            $form[$tableID][$rowID][$colID] = [
              '#type' => 'number',
              '#step' => 0.01,
            ];
        }
      }
    }
  }

}
