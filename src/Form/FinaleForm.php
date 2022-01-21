<?php

namespace Drupal\finale\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Create Awesome form.
 */
class FinaleForm extends FormBase
{

  protected $header;

  protected $inputData;

  protected $calculatedData;

  protected $tableCount = 1;

  protected $rowCount = 1;


  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'awesome_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form['add_row'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Year'),
      '#submit' => ['::addRow'],
      '#ajax' => [
        'callback' => '::ajaxReloadForm',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $form['add_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Table'),
      '#submit' => ['::addTable'],
      '#ajax' => [
        'callback' => '::ajaxReloadForm',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];

    $this->buildTable($form, $form_state, $this->tableCount);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#submit' => ['::calculateQuarter'],
      '#ajax' => [
        'callback' => '::ajaxReloadForm',
        'event' => 'click',
        'wrapper' => 'form-wrapper'
      ],
    ];
    $form['#prefix'] = '<div id="form-wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // TODO
  }

  public function createHeader()
  {
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
    $this->inputData = [
      'jan', 'feb', 'mar',
      'apr', 'may', 'jun',
      'jul', 'aug', 'sep',
      'oct', 'nov', 'dec'
    ];
    $this->calculatedData = ['q1', 'q2', 'q3', 'q4', 'ytd',];
  }

  public function calculateQuarter(array &$form, FormStateInterface $form_state) {
    for($i = 1; $i <= $this->tableCount; $i++) {
      $tableID = "table-$i";
      for($j = 1; $j <= $this->rowCount; $j++) {
        $rowID = "row-$j";
        $months = [];
        foreach ($this->inputData as $month) {
          $months[$month] = (int) $form_state->getValue(["$tableID","$rowID","col-$month"]);
        }
        $q1 = (($months['jan'] + $months['feb'] + $months['mar']) + 1) / 3;
        $q2 = (($months['apr'] + $months['may'] + $months['jun']) + 1) / 3;
        $q3 = (($months['jul'] + $months['aug'] + $months['sep']) + 1) / 3;
        $q4 = (($months['oct'] + $months['nov'] + $months['dec']) + 1) / 3;
        $year = (($q1 + $q2 + $q3 + $q4) + 1 ) / 4;

        $form_state->setValue(["$tableID","$rowID","col-q1"], $q1 );
        $form_state->setValue(["$tableID","$rowID","col-q2"], $q2 );
        $form_state->setValue(["$tableID","$rowID","col-q3"], $q3 );
        $form_state->setValue(["$tableID","$rowID","col-q4"], $q4 );
        $form_state->setValue(["$tableID","$rowID","col-ytd"], $year );

        $form_state->setRebuild();

      }
    }
  }

  public function ajaxReloadForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  public function addRow(array &$form, FormStateInterface $form_state) {
    $this->rowCount++;
    $form_state->setRebuild();
  }

  public function addTable(array &$form, FormStateInterface $form_state) {
    $this->tableCount++;
    $form_state->setRebuild();
  }

  public function buildTable(array &$form, FormStateInterface $form_state, int $tableCount)
  {
    $this->createHeader();
    for ($i = 1; $i <= $tableCount; $i++) {
      $tableID = "table-$i";
      $form[$tableID] = [
        '#type' => 'table',
        '#header' => $this->header,
      ];
      $this->buildRow($form, $form_state, $tableID, $this->rowCount);
    }
  }

  /**
   * Function that create a row for table.
   * @param array $form
   * @param FormStateInterface $form_state
   * @param string $tableID
   * @return void
   */
  public function buildRow(array &$form, FormStateInterface $form_state, string $tableID, int $rowCount) {
    for ($i = 1; $i <= $rowCount; $i++) {
      $rowID = "row-$i";

      foreach ($this->header as $colName => $value) {
        $colID = "col-$colName";
        $form[$tableID][$rowID][$colID] = [
          '#type' => 'number',
          '#step' => '0.01',
        ];
        if (in_array($colName, $this->calculatedData)) {
          $form[$tableID][$rowID][$colID]['#disabled'] = TRUE;
          $form[$tableID][$rowID][$colID]['#default_value'] =  round($form_state->getValue(["$tableID","$rowID","$colID"]),   2);
        }
        elseif ($colName == "year") {
          $form[$tableID][$rowID][$colID]['#default_value'] = date('Y', strtotime("-$i year"));
          $form[$tableID][$rowID][$colID]['#disabled'] = TRUE;
        }
      }
    }
  }

}
