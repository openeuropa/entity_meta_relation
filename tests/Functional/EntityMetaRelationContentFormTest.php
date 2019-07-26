<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_meta_relation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the content form flow.
 */
class EntityMetaRelationContentFormTest extends BrowserTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'node',
    'user',
    'system',
    'workflows',
    'emr',
    'entity_meta_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer nodes',
      'edit any entity_meta_example_ct content',
      'edit own entity_meta_example_ct content',
      'access content overview',
    ]);

    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the entity version field behaviour can be configured per transition.
   */
  public function testContentWithEntityMetaEditing(): void {

    /** @var \EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = \Drupal::entityTypeManager()->getStorage('entity_meta');

    // Saves new content entity.
    $label = 'Node example';
    $this->drupalGet('node/add/entity_meta_example_ct');
    $this->getSession()->getPage()->fillField('Title', $label);
    $this->getSession()->getPage()->uncheckField('Published');
    $this->getSession()->getPage()->fillField('Color', 'red');
    $this->getSession()->getPage()->pressButton('Save');

    $this->getSession()->getPage()->hasContent("{$label} has been created");
    $node = $this->getOneEntityByLabel('node', $label);
    // Checks if the related entity meta have been properly created.
    $entity_meta_relations = $entity_meta_storage->getRelatedMetaEntities($node);
    $this->assertNotEmpty($entity_meta_relations);
    foreach ($entity_meta_relations as $entity_meta) {
      // Color was properly saved.
      $this->assertEquals($entity_meta->get('field_color')->value, 'red');
      // Status was properly set.
      $this->assertFalse($entity_meta->get('status')->value);
      $oldRevisionId = $entity_meta->getRevisionId();
    }

    // Change node status and color.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->fillField('Title', 'Node example 2');
    $this->getSession()->getPage()->checkField('Published');
    $this->getSession()->getPage()->selectFieldOption('Color', 'green');
    $this->getSession()->getPage()->pressButton('Save');
    $node_updated = $this->getOneEntityByLabel('node', 'Node example 2');
    $entity_meta_relations = $entity_meta_storage->getRelatedMetaEntities($node_updated);

    $this->assertNotEmpty($entity_meta_relations);
    foreach ($entity_meta_relations as $entity_meta) {
      $newRevisionId = $entity_meta->getRevisionId();
      // Color was properly saved.
      $this->assertEquals($entity_meta->get('field_color')->value, 'green');
      // Status was properly changed.
      $this->assertTrue($entity_meta->get('status')->value);
      // Revision changed.
      $this->assertNotEquals($oldRevisionId, $newRevisionId);
    }

    // Do not save color and check if revision for entity meta did not change.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->fillField('Title', 'Node example 3');
    $this->getSession()->getPage()->pressButton('Save');
    $node_updated_no_meta_changes = $this->getOneEntityByLabel('node', 'Node example 3');
    $entity_meta_relations = $entity_meta_storage->getRelatedMetaEntities($node_updated_no_meta_changes);

    $this->assertNotEmpty($entity_meta_relations);
    foreach ($entity_meta_relations as $entity_meta) {
      $lastRevisionId = $entity_meta->getRevisionId();
      // Color was properly saved.
      $this->assertEquals($entity_meta->get('field_color')->value, 'green');
      // Status was properly set.
      $this->assertTrue($entity_meta->get('status')->value);
      // Revision did not change.
      $this->assertEquals($lastRevisionId, $newRevisionId);
    }

    // Do not create a new revision and change color.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->fillField('Title', 'Node example 4');
    $this->getSession()->getPage()->uncheckField('Create new revision');
    $this->getSession()->getPage()->selectFieldOption('Color', 'green');
    $this->getSession()->getPage()->pressButton('Save');
    $node_updated_no_revision = $this->getOneEntityByLabel('node', 'Node example 4');
    $entity_meta_relations = $entity_meta_storage->getRelatedMetaEntities($node_updated_no_revision);

    // Checks we keep having a single relation.
    $this->assertEqual(count($entity_meta_relations), 1);
    foreach ($entity_meta_relations as $entity_meta) {
      $changedRevisionId = $entity_meta->getRevisionId();
      // Color was properly kept.
      $this->assertEquals($entity_meta->get('field_color')->value, 'green');
      // Revision did change.
      $this->assertEquals($lastRevisionId, $changedRevisionId);
    }
  }

  /**
   * Loads a single entity by its label.
   *
   * @param string $type
   *   The type of entity to load.
   * @param string $label
   *   The label of the entity to load.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  protected function getOneEntityByLabel($type, $label) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $property = $entity_type_manager->getDefinition($type)->getKey('label');

    $entity_list = $entity_type_manager->getStorage($type)->loadByProperties([$property => $label]);
    $entity_type_manager->getStorage($type)->resetCache(array_keys($entity_list));

    $entity = current($entity_list);
    if (!$entity) {
      $this->fail("No {$type} entity named {$label} found.");
    }

    return $entity;
  }

}
