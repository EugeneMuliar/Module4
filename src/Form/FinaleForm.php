<?php

namespace Drupal\finale\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create Awesome form.
 */
class FinaleForm extends FormBase {

  /**
   * Table header.
   *
   * @var string[]
   */
  protected $header;

  /**
   * Contains columns, that are entered by user.
   *
   * @var string[]
   */
  protected $inputData;

  /**
   * Contains columns, that are calculated by server.
   *
   * @var string[]
   */
  protected $calculatedData;

  /**
   * Number of tables to build.
   *
   * @var int
   */
  protected $tableCount = 1;

  /**
   * Number of rows to build.
   *
   * @var int
   */
  protected $rowCount = 1;

  /**
   * For dependency injection.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * For checking errors.
   *
   * @var bool
   */
  protected $errors;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setMessenger($container->get('messenger'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'awesome_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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

    $this->buildTable($this->tableCount, $form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxReloadForm',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
      ],
    ];
    $form['#prefix'] = '<div id="form-wrapper">';
    $form['#suffix'] = '</div>';

    return $form;
  }

  /**
   * Build header, inputData and calculatedData.
   */
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
    $this->inputData = [
      'jan', 'feb', 'mar',
      'apr', 'may', 'jun',
      'jul', 'aug', 'sep',
      'oct', 'nov', 'dec',
    ];
    $this->calculatedData = ['q1', 'q2', 'q3', 'q4', 'ytd'];
  }

  /**
   * Build tables.
   */
  public function buildTable(int $tableCount, array &$form, FormStateInterface $form_state) {
    $this->createHeader();
    for ($table = 1; $table <= $tableCount; $table++) {
      $tableID = "table-$table";
      // Create table.
      $form[$tableID] = [
        '#type' => 'table',
        '#header' => $this->header,
      ];
      $this->buildRow($tableID, $this->rowCount, $form, $form_state);
    }
  }

  /**
   * Build rows for a table.
   */
  public function buildRow(string $tableID, int $rowCount, array &$form, FormStateInterface $form_state) {
    for ($row = $rowCount; $row > 0; $row--) {
      $rowID = "row-$row";

      foreach ($this->header as $colName => $value) {
        $colID = "col-$colName";
        $form[$tableID][$rowID][$colID] = [
          '#type' => 'number',
          '#step' => '0.01',
        ];
        if (in_array($colName, $this->calculatedData)) {
          $value = round($form_state->getValue([$tableID, $rowID, $colID]), 2);
          $form[$tableID][$rowID][$colID]['#disabled'] = TRUE;
          $form[$tableID][$rowID][$colID]['#default_value'] = $value;
        }
        elseif ($colName == "year") {
          $year = date('Y', strtotime("-$row year"));
          $form[$tableID][$rowID][$colID]['#default_value'] = $year;
          $form[$tableID][$rowID][$colID]['#disabled'] = TRUE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $tables = $this->getArrayOfValues($form_state);

    // Validate on empty parts.
    foreach ($tables as $table) {
      $prevCell = FALSE;
      $currentCell = FALSE;
      $endOfFilling = 0;
      foreach ($table as $row) {
        foreach ($this->inputData as $month) {
          // Check every cell.
          if (!is_null($row[$month])) {
            $currentCell = TRUE;
          }
          else {
            $currentCell = FALSE;
            if ($prevCell) {
              // The ending of filled cells.
              $endOfFilling++;
            }
          }
          $prevCell = $currentCell;
        }
        // If there are more than 1 ending cells, show error.
        if ($endOfFilling > 1) {
//          $form_state->setErrorByName("empty_parts", "There are empty parts.");
          $this->messenger->addError("There are empty parts.");
          $this->errors = TRUE;
        }
      }
    }
    // Validate one-row tables if they are similar.
    if ($this->rowCount == 1 && $this->tableCount > 1) {
      $clearTable = $this->clearTable($tables);
      for ($table = 1; $table < count($clearTable); $table++) {
        // Get difference from two rows.
        $arr_diff_1 = array_diff_key($clearTable[1][1], $clearTable[$table + 1][1]);
        $arr_diff_2 = array_diff_key($clearTable[$table + 1][1], $clearTable[1][1]);
        // Check is there a difference between tables.
        if ($arr_diff_1 != [] || $arr_diff_2 != []) {
//          $form_state->setErrorByName("tables_not_similar", "Tables are not similar.");
          $this->messenger->addError("Tables are not similar.");
          $this->errors = TRUE;
        }
      }
    }
  }

  /**
   * Return new array of values from table.
   */
  public function getArrayOfValues(FormStateInterface $form_state): array {
    $tables = [];
    for ($table = 1; $table <= $this->tableCount; $table++) {
      $tableID = "table-$table";
      for ($row = 1; $row <= $this->rowCount; $row++) {
        $rowID = "row-$row";
        foreach ($this->inputData as $month) {
          $monthVal = $form_state->getValue([$tableID, $rowID, "col-$month"]);
          if (!empty($monthVal) || $monthVal === "0") {
            $tables[$table][$row][$month] = intval($monthVal);
          }
          else {
            $tables[$table][$row][$month] = NULL;
          }
        }
      }
    }
    return $tables;
  }

  /**
   * Clear array from null values.
   *
   * @param array $tables
   *   Array of table values.
   *
   * @return array
   *   Array without null values.
   */
  public function clearTable(array $tables): array {
    $clearTable = [];
    for ($table = 1; $table <= count($tables); $table++) {
      // Clear null elements in array but leave 0.
      $clearTable[$table][1] = array_filter($tables[$table][1], function ($value) {
        return !is_null($value);
      });
    }
    return $clearTable;
  }

  /**
   * Reload form with ajax.
   */
  public function ajaxReloadForm(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * Increase number of rows.
   */
  public function addRow(array &$form, FormStateInterface $form_state) {
    $this->rowCount++;
    $form_state->setRebuild();
  }

  /**
   * Increase number of tables.
   */
  public function addTable(array &$form, FormStateInterface $form_state) {
    $this->tableCount++;
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->errors) {
      for ($table = 1; $table <= $this->tableCount; $table++) {
        $tableID = "table-$table";
        for ($row = 1; $row <= $this->rowCount; $row++) {
          $rowID = "row-$row";
          $months = [];
          // Fill array with values from table.
          foreach ($this->inputData as $month) {
            $monthVal = $form_state->getValue([$tableID, $rowID, "col-$month"]);
            $months[$month] = (int) $monthVal;
          }

          $q1 = (($months['jan'] + $months['feb'] + $months['mar']) + 1) / 3;
          $q2 = (($months['apr'] + $months['may'] + $months['jun']) + 1) / 3;
          $q3 = (($months['jul'] + $months['aug'] + $months['sep']) + 1) / 3;
          $q4 = (($months['oct'] + $months['nov'] + $months['dec']) + 1) / 3;
          $ytd = (($q1 + $q2 + $q3 + $q4) + 1) / 4;

          $form_state->setValue([$tableID, $rowID, "col-q1"], $q1);
          $form_state->setValue([$tableID, $rowID, "col-q2"], $q2);
          $form_state->setValue([$tableID, $rowID, "col-q3"], $q3);
          $form_state->setValue([$tableID, $rowID, "col-q4"], $q4);
          $form_state->setValue([$tableID, $rowID, "col-ytd"], $ytd);

          $this->messenger->addStatus('Valid.');
          $form_state->setRebuild();
        }
      }
    }
  }

}
