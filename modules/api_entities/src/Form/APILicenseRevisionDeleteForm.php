<?php

namespace Drupal\devportal_api_entities\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting an API License revision.
 *
 * @ingroup devportal_api_entities
 */
class APILicenseRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The API License revision.
   *
   * @var \Drupal\devportal_api_entities\APILicenseInterface
   */
  protected $revision;

  /**
   * The API License storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $apiLicenseStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new APILicenseRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection, DateFormatterInterface $date_formatter) {
    $this->apiLicenseStorage = $entity_storage;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    /** @var Connection $connection */
    $connection = $container->get('database');
    /** @var DateFormatterInterface $date_formatter */
    $date_formatter = $container->get('date.formatter');
    return new static(
      $entity_manager->getStorage('api_license'),
      $connection,
      $date_formatter
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'api_license_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.api_license.version_history', ['api_license' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $api_license_revision = NULL) {
    $this->revision = $this->apiLicenseStorage->loadRevision($api_license_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->apiLicenseStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('API License: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);
    $this->messenger()->addMessage($this->t('Revision from %revision-date of API License %title has been deleted.', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label(),
    ]));
    $form_state->setRedirect(
      'entity.api_license.canonical',
       ['api_license' => $this->revision->id()]
    );
    $query = $this->connection->select('api_license_field_revision');
    $query->addExpression('COUNT(DISTINCT vid)');
    $result = $query->condition('id', $this->revision->id())
      ->execute()
      ->fetchField();
    if ($result > 1) {
      $form_state->setRedirect(
        'entity.api_license.version_history',
         ['api_license' => $this->revision->id()]
      );
    }
  }

}
