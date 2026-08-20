<?php

/**
 * @file
 * Contains \Drupal\cfd_coursework_project\Form\CfdCourseworkProjectAbstractBulkApprovalForm.
 */

namespace Drupal\cfd_coursework_project\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Link;
use Drupal\Core\Url;

class CfdCourseworkProjectAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_coursework_project_abstract_bulk_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->getCourseworkProjectOptions();
    $selected = $form_state->getValue(['coursework_project']);
    if ($selected === NULL || $selected === '') {
      $selected = key($options_first);
    }
    $form = [];
    $form['coursework_project'] = [
      '#type' => 'select',
      '#title' => t('Title of the coursework project'),
      '#options' => $options_first,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajaxBulkCourseworkProjectAbstractDetailsCallback',
        'event' => 'change',
        'limit_validation_errors' => [['coursework_project']],
      ],
      '#suffix' => '<div id="ajax_selected_coursework_project"></div><div id="ajax_selected_coursework_project_pdf"></div>',
    ];
    // var_dump($form_state->getValue(['coursework_project']));
    $form['coursework_project_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for coursework project'),
      '#options' => $this->getCourseworkProjectActionOptions(),
      '#default_value' => 0,
      '#prefix' => '<div id="ajax_selected_coursework_project_action" style="color:red;">',
      '#suffix' => '</div>',
      '#states' => [
        'invisible' => [
          ':input[name="coursework_project"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('If Dis-Approved please specify reason for Dis-Approval'),
      '#prefix' => '<div id="message_submit">',
      '#states' => [
        'visible' => [
          // [':input[name="coursework_project_actions"]' => ['value' => '2']],
          // 'or',
          [':input[name="coursework_project_actions"]' => ['value' => '3']],
        ],
      ],
      '#suffix' => '</div>',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#states' => [
        'invisible' => [
          ':input[name="coursework_project"]' => [
            'value' => 0
          ]
        ]
      ],
    ];
    return $form;
  }

  public function ajaxBulkCourseworkProjectAbstractDetailsCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    $response = new AjaxResponse();

    $coursework_project_default_value = $form_state->getValue('coursework_project');
    if ($coursework_project_default_value) {
      $response->addCommand(new HtmlCommand('#ajax_selected_coursework_project', $this->buildCourseworkProjectDetailsMarkup($coursework_project_default_value)));
    }
    else {
      $response->addCommand(new HtmlCommand('#ajax_selected_coursework_project', ''));
    }

    return $response;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $config = \Drupal::config('cfd_coursework_project.settings');
    $from = $config->get('coursework_project_from_email') ?: $config->get('from_email') ?: \Drupal::config('system.site')->get('mail');
    if (empty($from)) {
      $from = 'no-reply@localhost';
    }
    $bcc = $config->get('coursework_project_emails') ?: $config->get('emails');
    $cc = $config->get('coursework_project_cc_emails') ?: $config->get('cc_emails');
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $msg = '';
    $trigger = $form_state->getTriggeringElement();
    if (($trigger['#type'] ?? '') === 'submit') {
      if ($form_state->getValue(['coursework_project']))
        //var_dump($form_state['values']['coursework_project_actions']);die;
        // coursework_project_abstract_del_lab_pdf($form_state['values']['coursework_project']);
 {
        if (\Drupal::currentUser()->hasPermission('Coursework Project bulk manage abstract')) {
          $query = \Drupal::database()->select('coursework_project_proposal');
          $query->fields('coursework_project_proposal');
          $query->condition('id', $form_state->getValue(['coursework_project']));
          $user_query = $query->execute();
          $user_info = $user_query->fetchObject();
          //var_dump($user_info);die;
          $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($user_info->uid);
          if ($user_data && $user_data->getPreferredLangcode()) {
            $langcode = $user_data->getPreferredLangcode();
          }
          if ($form_state->getValue(['coursework_project_actions']) == 1) {
            // approving entire project //
            $query = \Drupal::database()->select('coursework_project_submitted_abstracts');
            $query->fields('coursework_project_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['coursework_project']));
            $abstracts_q = $query->execute();
            //var_dump($abstracts_q);die;
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {coursework_project_submitted_abstracts} SET abstract_approval_status = 1, is_submitted = 1, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->id(),
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {coursework_project_submitted_abstracts_file} SET file_approval_status = 1, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->id(),
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            \Drupal::messenger()->addStatus(t('Approved coursework project.'));
           

            /** sending email when everything done **/
            $email_to = $user_data ? $user_data->getEmail() : '';
            if ($email_to) {
              $params = $this->buildBulkMailParams(
                'coursework_project_bulk_project_approved',
                $form_state->getValue(['coursework_project']),
                (int) $user_info->uid,
                $from,
                $cc,
                $bcc
              );
              $result = $mail_manager->mail('cfd_coursework_project', 'coursework_project_bulk_project_approved', $email_to, $langcode, $params, $from, TRUE);
              if (empty($result['result'])) {
                $msg = \Drupal::messenger()->addError('Error sending email message.');
              }
            } //!drupal_mail('cfd_coursework_project', 'standard', $email_to, language_default(), $params, $from, TRUE)
          } //$form_state['values']['coursework_project_actions'] == 1
          elseif ($form_state->getValue(['coursework_project_actions']) == 2) {
            //pending review entire project 
            $query = \Drupal::database()->select('coursework_project_submitted_abstracts');
            $query->fields('coursework_project_submitted_abstracts');
            $query->condition('proposal_id', $form_state->getValue(['coursework_project']));
            $abstracts_q = $query->execute();
            $experiment_list = '';
            while ($abstract_data = $abstracts_q->fetchObject()) {
              \Drupal::database()->query("UPDATE {coursework_project_submitted_abstracts} SET abstract_approval_status = 0, is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->id(),
                ':id' => $abstract_data->id,
              ]);
              \Drupal::database()->query("UPDATE {coursework_project_proposal} SET is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
                ':approver_uid' => $user->id(),
                ':id' => $abstract_data->proposal_id,
              ]);
              \Drupal::database()->query("UPDATE {coursework_project_submitted_abstracts_file} SET file_approval_status = 0, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
                ':approver_uid' => $user->id(),
                ':submitted_abstract_id' => $abstract_data->id,
              ]);
            } //$abstract_data = $abstracts_q->fetchObject()
            \Drupal::messenger()->addStatus(t('Resubmit the project files'));
           
            $email_to = $user_data ? $user_data->getEmail() : '';
            if ($email_to) {
              $params = $this->buildBulkMailParams(
                'coursework_project_bulk_project_resubmit',
                $form_state->getValue(['coursework_project']),
                (int) $user_info->uid,
                $from,
                $cc,
                $bcc
              );
              $result = $mail_manager->mail('cfd_coursework_project', 'coursework_project_bulk_project_resubmit', $email_to, $langcode, $params, $from, TRUE);
              if (empty($result['result'])) {
                \Drupal::messenger()->addError('Error sending email message.');
              }
            } //!drupal_mail('cfd_coursework_project', 'standard', $email_to, language_default(), $params, $from, TRUE)
          } //$form_state['values']['coursework_project_actions'] == 2
          elseif ($form_state->getValue(['coursework_project_actions']) == 3) //disapprove and delete entire coursework project
 {
            if (strlen(trim($form_state->getValue(['message']))) <= 30) {
              $form_state->setErrorByName('message', t(''));
              $msg = \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
              return $msg;
            } //strlen(trim($form_state['values']['message'])) <= 30
            if (!\Drupal::currentUser()->hasPermission('Coursework Project bulk delete abstract')) {
              $msg = \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'));
              return $msg;
            } //!user_access('coursework_project bulk delete code')
            // Keep the proposal details available for the notification after
            // deleteCourseworkProject() removes the proposal row.
            $proposal_for_mail = clone $user_info;
            if ($this->deleteCourseworkProject($form_state->getValue(['coursework_project']))) //////
 {
              \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire coursework project.'));
           //mail
              $email_to = $user_data ? $user_data->getEmail() : '';
              if ($email_to) {
                $params = $this->buildBulkMailParams(
                  'coursework_project_bulk_project_disapproved',
                  $form_state->getValue(['coursework_project']),
                  (int) $user_info->uid,
                  $from,
                  $cc,
                  $bcc,
                  [
                    'reason' => trim((string) $form_state->getValue(['message'])),
                    'proposal_data' => $proposal_for_mail,
                  ]
                );
                $result = $mail_manager->mail('cfd_coursework_project', 'coursework_project_bulk_project_disapproved', $email_to, $langcode, $params, $from, TRUE);
                if (empty($result['result'])) {
                  \Drupal::messenger()->addError('Error sending email message.');
                }
              }
            }
            else {
              \Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire coursework project.'));
            }
            // email 

          } //$form_state['values']['coursework_project_actions'] == 3

        }
      } // Coursework Project bulk-management access check.
      \Drupal\Core\Cache\Cache::invalidateTags([
        'coursework_project_proposal_list',
        'coursework_project_titles_list',
        'coursework_project_proposal:' . (int) $form_state->getValue(['coursework_project']),
      ]);
      return $msg;
    } //$form_state['clicked_button']['#value'] == 'Submit'
  }

  /**
   * Returns the selectable list of submitted coursework projects.
   */
  protected function getCourseworkProjectOptions() {
    $project_titles = [
      0 => $this->t('Please select...'),
    ];

    $query = \Drupal::database()->select('coursework_project_proposal', 'csp')
      ->fields('csp', ['id', 'project_title', 'contributor_name'])
      ->condition('is_submitted', 1)
      ->condition('approval_status', 1)
      ->orderBy('project_title', 'ASC');

    foreach ($query->execute() as $project) {
      $project_titles[$project->id] = $project->project_title . ' (Proposed by ' . $project->contributor_name . ')';
    }

    return $project_titles;
  }

  /**
   * Returns the available bulk actions.
   */
  protected function getCourseworkProjectActionOptions() {
    return [
      0 => $this->t('Please select...'),
      1 => $this->t('Approve Entire coursework project'),
      2 => $this->t('Resubmit Project files'),
      3 => $this->t('Dis-Approve Entire coursework project (This will delete coursework project)'),
    ];
  }

  /**
   * Builds the coursework project details HTML shown for the selected project.
   */
  protected function buildCourseworkProjectDetailsMarkup($proposal_id) {
    $proposal = \Drupal::database()->select('coursework_project_proposal', 'csp')
      ->fields('csp')
      ->condition('id', (int) $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      return '';
    }

    $abstract_file = \Drupal::database()->select('coursework_project_submitted_abstracts_file', 'cssf')
      ->fields('cssf', ['filename'])
      ->condition('proposal_id', (int) $proposal_id)
      ->condition('filetype', 'A')
      ->execute()
      ->fetchField();

    $project_file = \Drupal::database()->select('coursework_project_submitted_abstracts_file', 'cssf')
      ->fields('cssf', ['filename'])
      ->condition('proposal_id', (int) $proposal_id)
      ->condition('filetype', 'S')
      ->execute()
      ->fetchField();

    $download_coursework_project = Link::fromTextAndUrl(
      $this->t('Download coursework project'),
      Url::fromRoute('cfd_coursework_project.download_full_project', [], [
        'query' => ['id' => (int) $proposal_id],
      ])
    )->toString();

    return '<strong>' . $this->t('Proposer Name:') . '</strong><br />'
      . Html::escape(trim($proposal->name_title . ' ' . $proposal->contributor_name)) . '<br /><br />'
      . '<strong>' . $this->t('Title of the coursework project:') . '</strong><br />'
      . Html::escape($proposal->project_title) . '<br /><br />'
      . '<strong>' . $this->t('Uploaded an abstract (brief outline) of the project:') . '</strong><br />'
      . Html::escape($this->normalizeUploadedFilename($abstract_file)) . '<br /><br />'
      . '<strong>' . $this->t('Uploaded Coursework Project Directory:') . '</strong><br />'
      . Html::escape($this->normalizeUploadedFilename($project_file)) . '<br /><br />'
      . '<strong>' . $this->t('Download Coursework Project:') . '</strong><br />'
      . $download_coursework_project;
  }

  /**
   * Returns a display value for an uploaded filename.
   */
  protected function normalizeUploadedFilename($filename) {
    if ($filename === FALSE || $filename === NULL || $filename === '' || $filename === 'NULL') {
      return $this->t('File not uploaded');
    }

    return $filename;
  }

  /**
   * Deletes all files and records for a coursework project.
   */
  protected function deleteCourseworkProject($proposal_id) {
    $proposal = \Drupal::database()->select('coursework_project_proposal', 'csp')
      ->fields('csp')
      ->condition('id', (int) $proposal_id)
      ->execute()
      ->fetchObject();

    if (!$proposal) {
      $this->messenger()->addError($this->t('Invalid Coursework Project.'));
      return FALSE;
    }

    $directory = rtrim(cfd_coursework_project_path(), '/\\') . '/' . $proposal->directory_name;
    if (is_dir($directory) && !$this->removeDirectory($directory)) {
      $this->messenger()->addError($this->t('Unable to delete the coursework project directory.'));
      return FALSE;
    }

    \Drupal::database()->delete('coursework_project_submitted_abstracts_file')
      ->condition('proposal_id', (int) $proposal_id)
      ->execute();

    \Drupal::database()->delete('coursework_project_submitted_abstracts')
      ->condition('proposal_id', (int) $proposal_id)
      ->execute();

    \Drupal::database()->delete('coursework_project_proposal')
      ->condition('id', (int) $proposal_id)
      ->execute();

    return TRUE;
  }

  /**
   * Recursively removes a directory.
   */
  protected function removeDirectory($directory) {
    $items = scandir($directory);
    if ($items === FALSE) {
      return FALSE;
    }

    foreach ($items as $item) {
      if ($item === '.' || $item === '..') {
        continue;
      }

      $path = $directory . '/' . $item;
      if (is_dir($path)) {
        if (!$this->removeDirectory($path)) {
          return FALSE;
        }
      }
      elseif (file_exists($path) && !unlink($path)) {
        return FALSE;
      }
    }

    return rmdir($directory);
  }

  /**
   * Builds params for bulk approval notification emails.
   */
  protected function buildBulkMailParams($key, $proposal_id, $user_id, $from, $cc = '', $bcc = '', array $extra = []) {
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

    $params = [
      $key => [
        'proposal_id' => (int) $proposal_id,
        'user_id' => (int) $user_id,
        'headers' => $headers,
      ],
    ];

    if (!empty($extra)) {
      $params[$key] = array_merge($params[$key], $extra);
    }

    return $params;
  }

  /**
   * Returns a marker used only for testing the code diff viewer.
   */
  public function bulkApprovalDiffTest(): string {
    return 'Bulk approval diff test';
  }

}
?>
