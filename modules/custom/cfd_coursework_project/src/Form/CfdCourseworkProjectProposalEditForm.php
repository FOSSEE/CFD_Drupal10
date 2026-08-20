<?php

/**
 * @file
 * Contains \Drupal\cfd_coursework_project\Form\CfdCourseworkProjectProposalEditForm.
 */

namespace Drupal\cfd_coursework_project\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CfdCourseworkProjectProposalEditForm extends FormBase {

  private const MODIFIED_SIMULATION_TYPE_ID = 19;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_coursework_project_proposal_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $proposal_id = $this->getProposalId();
    if (!$proposal_id) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_coursework_project.proposal_all');
      return [];
    }

    $proposal_data = $this->loadProposal($proposal_id);
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_coursework_project.proposal_all');
      return [];
    }

   $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    $user_email = $user_data ? $user_data->getEmail() : '';
    $form['name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->name_title,
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Proposer'),
      // '#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->contributor_name,
    ];
    $form['student_email_id'] = [
      '#type' => 'item',
      '#title' => t('Email'),
      '#markup' => $user_email,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/Institute'),
      // '#size' => 200,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#default_value' => $proposal_data->university,
    ];
    $form['institute'] = [
      '#type' => 'textfield',
      '#title' => t('Institute'),
      // '#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#default_value' => $proposal_data->institute,
    ];
    $form['how_did_you_know_about_project'] = [
      '#type' => 'textfield',
      '#title' => t('How did you come to know about the Coursework Project?'),
      '#default_value' => $proposal_data->how_did_you_know_about_project,
      '#disabled' => TRUE,
    ];
    $form['faculty_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Faculty'),
      // '#size' => 50,
      '#maxlength' => 50,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_name,
    ];
    $form['faculty_department'] = [
      '#type' => 'textfield',
      '#title' => t('Department of the Faculty'),
      // '#size' => 50,
      '#maxlength' => 50,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_department,
    ];
    $form['faculty_email'] = [
      '#type' => 'textfield',
      '#title' => t('Email id of the Faculty'),
      // '#size' => 255,
      '#maxlength' => 255,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_email,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#default_value' => $proposal_data->country,
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      // '#size' => 100,
      '#default_value' => $proposal_data->country,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      // '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#default_value' => $proposal_data->state,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      // '#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => cfd_coursework_project_list_states(),
      '#default_value' => $proposal_data->state,
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => cfd_coursework_project_list_cities(),
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      // '#size' => 30,
      '#maxlength' => 6,
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => 'Insert pincode of your city/ village....'
        ],
    ];
    $form['project_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the Coursework Project'),
      // '#size' => 300,
      '#maxlength' => 350,
      '#required' => TRUE,
      '#default_value' => $proposal_data->project_title,
    ];
    $version_options = cfd_coursework_project_list_versions();
    $form['version'] = [
      '#type' => 'select',
      '#title' => t('Version used'),
      '#options' => $version_options,
      '#default_value' => $proposal_data->version_id,
    ];
    $simulation_type_options = cfd_coursework_project_list_simulation_types();
    $form['simulation_type'] = [
      '#type' => 'select',
      '#title' => t('Simulation Type used'),
      '#options' => $simulation_type_options,
      '#default_value' => $proposal_data->simulation_type_id,
      '#ajax' => [
        'callback' => '::ajaxSolverUsedCallback',
        'event' => 'change',
        'wrapper' => 'ajax-solver-wrapper',
        ],
    ];
    $simulation_id = (int) $proposal_data->simulation_type_id;
    if ($form_state->hasValue('simulation_type') && $form_state->getValue('simulation_type') !== '') {
      $simulation_id = (int) $form_state->getValue('simulation_type');
    }
    $solver_default_value = $simulation_id === (int) $proposal_data->simulation_type_id
      ? $proposal_data->solver_used
      : NULL;

    $form['solver_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax-solver-wrapper'],
    ];

    if ($simulation_id !== self::MODIFIED_SIMULATION_TYPE_ID) {
      $form['solver_wrapper']['solver_used'] = [
        '#type' => 'select',
        '#title' => t('Select the Solver to be used'),
        '#options' => cfd_coursework_project_list_solvers($simulation_id),
        '#default_value' => $solver_default_value,
        '#parents' => ['solver_used'],
      ];
    }
    else {
      $form['solver_wrapper']['solver_used_text'] = [
        '#type' => 'textfield',
        '#title' => t('Enter the Solver to be used'),
        // '#size' => 100,
        '#description' => t('Maximum character limit is 50'),
        '#default_value' => $solver_default_value,
        '#parents' => ['solver_used_text'],
      ];
    }
    /* $form['solver_used'] = array(
        '#type' => 'textfield',
        '#title' => t('Solver to be used'),
        // '#size' => 50,
        '#maxlength' => 50,
        '#required' => true,
        '#default_value' => $proposal_data->solver_used,
    );*/
    $form['date_of_proposal'] = [
      '#type' => 'textfield',
      '#title' => t('Date of Proposal'),
      '#default_value' => date('d/m/Y', $proposal_data->creation_date),
      '#disabled' => TRUE,
    ];
    $form['delete_proposal'] = [
      '#type' => 'checkbox',
      '#title' => t('Delete Proposal'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = [
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelForm'],
    ];

    return $form;
  }

  /**
   * Ajax callback for refreshing the solver field when simulation type changes.
   */
  public function ajaxSolverUsedCallback(array &$form, FormStateInterface $form_state) {
    return $form['solver_wrapper'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $simulation_id = (int) $form_state->getValue('simulation_type');
    if ($simulation_id !== self::MODIFIED_SIMULATION_TYPE_ID) {
      if ($form_state->getValue('solver_used') == '0' || $form_state->getValue('solver_used') === NULL) {
        $form_state->setErrorByName('solver_used', t('Please select an option'));
      }
    }
    else {
      if ($form_state->getValue('solver_used_text') != '') {
        if (strlen($form_state->getValue('solver_used_text')) > 100) {
          $form_state->setErrorByName('solver_used_text', t('Maximum charater limit is 100 charaters only, please check the length of the solver used'));
        } //strlen($form_state['values']['project_title']) > 250
        else {
          if (strlen($form_state->getValue('solver_used_text')) < 7) {
            $form_state->setErrorByName('solver_used_text', t('Minimum charater limit is 7 charaters, please check the length of the solver used'));
          }
        } //strlen($form_state['values']['project_title']) < 10
      }
      else {
        $form_state->setErrorByName('solver_used_text', t('Solver used cannot be empty'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $proposal_id = $this->getProposalId();
    if (!$proposal_id) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_coursework_project.proposal_all');
      return;
    }

    $proposal_data = $this->loadProposal($proposal_id);
    if (!$proposal_data) {
      $this->messenger()->addError($this->t('Invalid proposal selected. Please try again.'));
      $form_state->setRedirect('cfd_coursework_project.proposal_all');
      return;
    }
    /* delete proposal */
    if ($form_state->getValue(['delete_proposal']) == 1) {
      /* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data ? $user_data->getEmail() : '';
      $config = \Drupal::config('cfd_coursework_project.settings');
      $from = $config->get('coursework_project_from_email') ?: \Drupal::config('system.site')->get('mail');
      if (empty($from)) {
        $from = 'no-reply@localhost';
      }
      $bcc = $config->get('coursework_project_emails');
      $cc = $config->get('coursework_project_cc_emails');
      $langcode = $user_data ? $user_data->getPreferredLangcode() : $this->currentUser()->getPreferredLangcode();

      $params['coursework_project_proposal_deleted']['proposal_id'] = $proposal_id;
      $params['coursework_project_proposal_deleted']['user_id'] = $proposal_data->uid;
      $headers = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
      ];
      if (!empty($cc)) {
        $headers['Cc'] = $cc;
      }
      if (!empty($bcc)) {
        $headers['Bcc'] = $bcc;
      }
      $params['coursework_project_proposal_deleted']['headers'] = $headers;
      $mail_manager = \Drupal::service('plugin.manager.mail');
      if ($email_to) {
        $result = $mail_manager->mail('cfd_coursework_project', 'coursework_project_proposal_deleted', $email_to, $langcode, $params, $from, TRUE);
        if (empty($result['result'])) {
          \Drupal::messenger()->addError('Error sending email message.');
        }
      }

      \Drupal::messenger()->addStatus(t('Coursework Project proposal has been deleted.'));
      if (cfd_coursework_project_remove_project_directory($proposal_id) == TRUE) {
        $query = \Drupal::database()->delete('coursework_project_proposal');
        $query->condition('id', $proposal_id);
        $num_deleted = $query->execute();
        \Drupal::messenger()->addStatus(t('Proposal Deleted'));
        \Drupal\Core\Cache\Cache::invalidateTags([
          'coursework_project_proposal_list',
          "coursework_project_proposal:$proposal_id",
        ]);
        $form_state->setRedirect('cfd_coursework_project.proposal_all');
        return;
      } //cfd_coursework_project_remove_project_directory($proposal_id) == TRUE
    } //$form_state['values']['delete_proposal'] == 1
    /* update proposal */
    $v = $form_state->getValues();
    $project_title = $v['project_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $directory_names = cfd_coursework_project_directory_name($project_title, $proposar_name);
    if (cfd_coursework_project_rename_directory($proposal_id, $directory_names)) {
      $directory_name = $directory_names;
    } //LM_RenameDir($proposal_id, $directory_names)
    else {
      return;
    }
    $simulation_id = (int) $v['simulation_type'];
    if ($simulation_id !== self::MODIFIED_SIMULATION_TYPE_ID) {
      $solver = $v['solver_used'];
    }
    else {
      $solver = $v['solver_used_text'];
    }
    $query = "UPDATE {coursework_project_proposal} SET
				name_title=:name_title,
				contributor_name=:contributor_name,
				university=:university,
				institute=:institute,
				how_did_you_know_about_project = :how_did_you_know_about_project,
				faculty_name = :faculty_name,
				faculty_department = :faculty_department,
				faculty_email = :faculty_email,
				city=:city,
				pincode=:pincode,
				state=:state,
				project_title=:project_title,
                version_id=:version_id,
                simulation_type_id=:simulation_type_id,
				solver_used=:solver_used,
				directory_name=:directory_name
				WHERE id=:proposal_id";
    $args = [
      ':name_title' => $v['name_title'],
      ':contributor_name' => $v['contributor_name'],
      ':university' => $v['university'],
      ":institute" => $v['institute'],
      ":how_did_you_know_about_project" => $v['how_did_you_know_about_project'],
      ":faculty_name" => $v['faculty_name'],
      ":faculty_department" => $v['faculty_department'],
      ":faculty_email" => $v['faculty_email'],
      ':city' => $v['city'],
      ':pincode' => $v['pincode'],
      ':state' => $v['all_state'],
      ':project_title' => $project_title,
      ':version_id' => $v['version'],
      ':simulation_type_id' => $simulation_id,
      ":solver_used" => $solver,
      ':directory_name' => $directory_name,
      ':proposal_id' => $proposal_id,
    ];
    $result = \Drupal::database()->query($query, $args);
    \Drupal::messenger()->addStatus(t('Proposal Updated'));
    \Drupal\Core\Cache\Cache::invalidateTags([
      'coursework_project_proposal_list',
      "coursework_project_proposal:$proposal_id",
    ]);
    $form_state->setRedirect('cfd_coursework_project.proposal_all');
  }

  /**
   * Redirects edit form cancel action to proposal list without validation.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('cfd_coursework_project.proposal_all');
  }

  /**
   * Returns the proposal entity row for a given ID.
   *
   * @param int $proposal_id
   *   The proposal identifier.
   *
   * @return object|null
   *   The proposal row or NULL if not found.
   */
  protected function loadProposal($proposal_id) {
    $query = \Drupal::database()->select('coursework_project_proposal');
    $query->fields('coursework_project_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();

    return $proposal_q ? $proposal_q->fetchObject() : NULL;
  }

  /**
   * Safely pull the proposal ID from the current route or query string.
   *
   * @return int|null
   *   The proposal ID if available, otherwise NULL.
   */
  protected function getProposalId() {
    $route_match = \Drupal::routeMatch();
    $proposal_id = $route_match->getParameter('id') ?: $route_match->getParameter('proposal_id');

    if (!$proposal_id) {
      $proposal_id = \Drupal::request()->query->get('id') ?: \Drupal::request()->query->get('proposal_id');
    }

    return $proposal_id !== NULL ? (int) $proposal_id : NULL;
  }

}
?>
