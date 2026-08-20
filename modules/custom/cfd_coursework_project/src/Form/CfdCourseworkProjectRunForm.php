<?php

/**
 * @file
 * Contains \Drupal\cfd_coursework_project\Form\CfdCourseworkProjectRunForm.
 */

namespace Drupal\cfd_coursework_project\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

class CfdCourseworkProjectRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cfd_coursework_project_run_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->getCourseworkProjectOptions();
    $selected = $this->resolveSelectedCourseworkProjectId($form_state, $options);
    $details_markup = $selected ? $this->buildCourseworkProjectDetailsMarkup($selected) : '';
    $download_links_markup = $selected ? $this->buildDownloadLinksMarkup($selected) : '';

    $form['coursework_project'] = [
      '#type' => 'select',
      '#title' => $this->t('Title of the coursework project'),
      '#options' => $options,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::courseworkProjectDetailsCallback',
        'event' => 'change',
        'limit_validation_errors' => [['coursework_project']],
        'wrapper' => 'ajax_coursework_project_wrapper',
      ],
    ];

    $form['coursework_project_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_coursework_project_wrapper'],
    ];
    $form['coursework_project_wrapper']['coursework_project_details'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_coursework_project_details">' . $details_markup . '</div>',
    ];
    $form['coursework_project_wrapper']['selected_coursework_project'] = [
      '#type' => 'item',
      '#markup' => '<div id="ajax_selected_coursework_project">' . $download_links_markup . '</div>',
    ];

    return $form;
  }

  public function courseworkProjectDetailsCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
    return $form['coursework_project_wrapper'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Resolves the active coursework project from form state, route, or query string.
   */
  protected function resolveSelectedCourseworkProjectId(FormStateInterface $form_state, array $options) {
    $selected = $form_state->getValue('coursework_project');

    if ($selected === NULL || $selected === '') {
      $route_match = \Drupal::routeMatch();
      $selected = (int) ($route_match->getParameter('id') ?? \Drupal::request()->query->get('id') ?? key($options));
    }

    $selected = (int) $selected;

    if ($selected === 0) {
      return 0;
    }

    return $this->loadCourseworkProjectInformation($selected) ? $selected : 0;
  }

  /**
   * Builds the download links shown for a selected coursework project.
   */
  protected function buildDownloadLinksMarkup($coursework_project_id) {
    $abstract_link = Link::fromTextAndUrl(
      $this->t('Download Abstract'),
      Url::fromRoute('cfd_coursework_project.project_files', ['proposal_id' => $coursework_project_id])
    )->toString();

    $full_project_link = Link::fromTextAndUrl(
      $this->t('Download Coursework Project'),
      Url::fromRoute('cfd_coursework_project.download_full_project', [], [
        'query' => ['id' => $coursework_project_id],
      ])
    )->toString();

    return $abstract_link . '<br>' . $full_project_link;
  }

  /**
   * Returns the selectable list of completed coursework projects.
   */
  protected function getCourseworkProjectOptions() {
    $coursework_project_titles = [
      0 => $this->t('Please select...'),
    ];

    $query = \Drupal::database()->select('coursework_project_proposal', 'csp')
      ->fields('csp', ['id', 'project_title', 'name_title', 'contributor_name'])
      ->condition('approval_status', 3)
      ->orderBy('project_title', 'ASC');

    foreach ($query->execute() as $coursework_project) {
      $coursework_project_titles[$coursework_project->id] = $coursework_project->project_title . ' (Proposed by ' . trim($coursework_project->name_title . ' ' . $coursework_project->contributor_name) . ')';
    }

    return $coursework_project_titles;
  }

  /**
   * Loads a completed coursework project proposal by ID.
   */
  protected function loadCourseworkProjectInformation($proposal_id) {
    if (empty($proposal_id)) {
      return NULL;
    }

    return \Drupal::database()->select('coursework_project_proposal', 'csp')
      ->fields('csp')
      ->condition('id', (int) $proposal_id)
      ->condition('approval_status', 3)
      ->execute()
      ->fetchObject() ?: NULL;
  }

  /**
   * Builds the coursework project details markup shown below the selector.
   */
  protected function buildCourseworkProjectDetailsMarkup($coursework_project_id) {
    $coursework_project = $this->loadCourseworkProjectInformation($coursework_project_id);
    if (!$coursework_project) {
      return '';
    }

    return '<span style="color: rgb(128, 0, 0);"><strong>' . $this->t('About the coursework project') . '</strong></span><br />'
      . '<ul>'
      . '<li><strong>' . $this->t('Proposer Name:') . '</strong> ' . Html::escape(trim($coursework_project->name_title . ' ' . $coursework_project->contributor_name)) . '</li>'
      . '<li><strong>' . $this->t('Title of the Coursework Project:') . '</strong> ' . Html::escape($coursework_project->project_title) . '</li>'
      . '<li><strong>' . $this->t('University:') . '</strong> ' . Html::escape($coursework_project->university) . '</li>'
      . '</ul>';
  }
}
?>
