<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_meta_relation\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the entity meta content form capability.
 *
 * It tests that the entity meta embedded forms (in content entity forms) work
 * properly.
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
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta');
    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta_relation');

    // Create a new content entity but don't specify any meta value.
    $this->drupalGet('node/add/entity_meta_example_ct');
    $this->getSession()->getPage()->fillField('Title', 'No meta node');
    $this->getSession()->getPage()->uncheckField('Published');
    $this->getSession()->getPage()->pressButton('Save');
    $this->getSession()->getPage()->hasContent("No meta node has been created");
    // No meta entities or meta entity relation entities should be created.
    $entity_meta_storage->resetCache();
    $entity_meta_relation_storage->resetCache();
    $this->assertEmpty($entity_meta_storage->loadMultiple());
    $this->assertEmpty($entity_meta_relation_storage->loadMultiple());

    // Create a new content entity with a meta value.
    $label = 'Node example';
    $this->drupalGet('node/add/entity_meta_example_ct');
    $this->getSession()->getPage()->fillField('Title', $label);
    $this->getSession()->getPage()->uncheckField('Published');
    $this->getSession()->getPage()->fillField('Color', 'red');
    $this->getSession()->getPage()->pressButton('Save');

    $this->getSession()->getPage()->hasContent("{$label} has been created");
    $node = $this->getEntityByLabel('node', $label);
    // Checks if the related entity meta has been properly created.
    $entity_meta_entities = $entity_meta_storage->getRelatedEntities($node);
    $this->assertCount(1, $entity_meta_entities);
    // Only one entity meta relation entity should exist.
    $this->assertCount(1, $entity_meta_relation_storage->loadMultiple());
    $entity_meta = reset($entity_meta_entities);

    // Color was properly saved.
    $this->assertEquals($entity_meta->get('field_color')->value, 'red');
    // Status was properly set.
    $this->assertFalse($entity_meta->get('status')->value);
    $this->assertEquals(1, $entity_meta->getRevisionId());

    // Change node status and color.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->fillField('Title', 'Node example 2');
    $this->getSession()->getPage()->checkField('Published');
    $this->getSession()->getPage()->selectFieldOption('Color', 'green');
    $this->getSession()->getPage()->pressButton('Save');
    $node_updated = $this->getEntityByLabel('node', 'Node example 2');
    $entity_meta_relation_storage->resetCache();
    $entity_meta_entities = $entity_meta_storage->getRelatedEntities($node_updated);
    $this->assertCount(1, $entity_meta_entities);
    // Only one entity meta relation entity should exist.
    $this->assertCount(1, $entity_meta_relation_storage->loadMultiple());
    $entity_meta = reset($entity_meta_entities);

    // Color was properly saved.
    $this->assertEquals($entity_meta->get('field_color')->value, 'green');
    // Status was properly changed.
    $this->assertTrue($entity_meta->get('status')->value);
    // Revision changed.
    $this->assertEquals(2, $entity_meta->getRevisionId());

    // Do not save color but update the node.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->fillField('Title', 'Node example 3');
    $this->getSession()->getPage()->pressButton('Save');
    $node_updated_no_meta_changes = $this->getEntityByLabel('node', 'Node example 3');
    $entity_meta_relation_storage->resetCache();
    $entity_meta_entities = $entity_meta_storage->getRelatedEntities($node_updated_no_meta_changes);

    $this->assertCount(1, $entity_meta_entities);
    // Only one entity meta relation entity should exist.
    $this->assertCount(1, $entity_meta_relation_storage->loadMultiple());
    $entity_meta = reset($entity_meta_entities);

    // Color was kept the same.
    $this->assertEquals($entity_meta->get('field_color')->value, 'green');
    // Status was kept the same.
    $this->assertTrue($entity_meta->get('status')->value);
    // Revision did not change.
    $this->assertEquals(2, $entity_meta->getRevisionId());

    // Do not create a new revision but change color.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->fillField('Title', 'Node example 4');
    $this->getSession()->getPage()->uncheckField('Create new revision');
    $this->getSession()->getPage()->selectFieldOption('Color', 'red');
    $this->getSession()->getPage()->pressButton('Save');
    $node_updated_no_revision = $this->getEntityByLabel('node', 'Node example 4');
    $entity_meta_relation_storage->resetCache();
    $entity_meta_entities = $entity_meta_storage->getRelatedEntities($node_updated_no_revision);

    // Checks we keep having a single relation.
    $this->assertCount(1, $entity_meta_entities);
    // Only one entity meta relation entity should exist.
    $this->assertCount(1, $entity_meta_relation_storage->loadMultiple());
    $entity_meta = reset($entity_meta_entities);
    // Color was changed.
    $this->assertEquals($entity_meta->get('field_color')->value, 'red');
    // Revision changed.
    $this->assertEquals(2, $entity_meta->getRevisionId());
  }

  /**
   * Tests that we can unset an entity meta relation in the form.
   */
  public function testRemoveMetaRelation(): void {
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta');
    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->container->get('entity_type.manager')->getStorage('entity_meta_relation');

    // Create a new content entity with a meta value.
    $label = 'Node example';
    $this->drupalGet('node/add/entity_meta_example_ct');
    $this->getSession()->getPage()->fillField('Title', $label);
    $this->getSession()->getPage()->uncheckField('Published');
    $this->getSession()->getPage()->fillField('Color', 'red');
    $this->getSession()->getPage()->pressButton('Save');

    $this->getSession()->getPage()->hasContent("{$label} has been created");
    $node = $this->getEntityByLabel('node', $label);
    // Checks if the related entity meta has been properly created.
    $entity_meta_entities = $entity_meta_storage->getRelatedEntities($node);
    $this->assertCount(1, $entity_meta_entities);
    // Only one entity meta relation entity should exist.
    $this->assertCount(1, $entity_meta_relation_storage->loadMultiple());
    $entity_meta = reset($entity_meta_entities);
    // The first entity meta created so its ID should be 1.
    $this->assertEquals(1, $entity_meta->id());

    // Color was properly saved.
    $this->assertEquals($entity_meta->get('field_color')->value, 'red');
    // Status was properly set.
    $this->assertFalse($entity_meta->get('status')->value);
    $this->assertEquals(1, $entity_meta->getRevisionId());

    // Edit the node and set the color to None.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->fillField('Title', 'Node example 2');
    $this->getSession()->getPage()->checkField('Published');
    $this->getSession()->getPage()->selectFieldOption('Color', '- None -');
    $this->getSession()->getPage()->pressButton('Save');
    $node_updated = $this->getEntityByLabel('node', 'Node example 2');
    $entity_meta_relation_storage->resetCache();
    $entity_meta_entities = $entity_meta_storage->getRelatedEntities($node_updated);
    // There should be still an entity meta for this node revision.
    $this->assertNotEmpty($entity_meta_entities);

    // There should still be only one revision of entity meta relations.
    $entity_meta_relations = $entity_meta_relation_storage->loadMultiple();
    $this->assertCount(1, $entity_meta_relations);
    $entity_meta_relation = reset($entity_meta_relations);
    $this->assertCount(2, $entity_meta_relation_storage->revisionIds($entity_meta_relation));

    // Edit the node again and set another color.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->getSession()->getPage()->fillField('Title', 'Node example 3');
    $this->getSession()->getPage()->checkField('Published');
    $this->getSession()->getPage()->selectFieldOption('Color', 'green');
    $this->getSession()->getPage()->pressButton('Save');
    $node_updated = $this->getEntityByLabel('node', 'Node example 3');
    $entity_meta_relation_storage->resetCache();
    $entity_meta_entities = $entity_meta_storage->getRelatedEntities($node_updated);
    // There should be 1 entity meta for this node revision with the new color.
    $this->assertCount(1, $entity_meta_entities);
    $entity_meta = reset($entity_meta_entities);
    // Color on this entity meta was properly saved.
    $this->assertEquals($entity_meta->get('field_color')->value, 'green');
    // This entity meta referenced on this revision is still the original one.
    $this->assertEquals(1, $entity_meta->id());

    // Load the previous content revision which had the entity meta with the
    // color red.
    $node = $this->container->get('entity_type.manager')->getStorage('node')->loadRevision(1);
    $entity_meta_entities = $entity_meta_storage->getRelatedEntities($node);
    $this->assertCount(1, $entity_meta_entities);
    $entity_meta = reset($entity_meta_entities);
    $this->assertEquals(1, $entity_meta->id());
    $this->assertEquals($entity_meta->get('field_color')->value, 'red');
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
  protected function getEntityByLabel($type, $label): EntityInterface {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
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
