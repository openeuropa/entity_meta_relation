# Entity Meta Relation

The Entity meta relation module allows to associate extra information stored in independent entities (meta entities) to content (host) entities. This avoids the need to store this information as a content entity field and pollute the content entity keeping metadata information that controls specific entity behaviour outside of its main storage. 
​
## Content Structure

* `EntityMeta` entities are regular content entities that can be fieldable and revisionable.
* `EntityMetaRelation` entities hold the relationship between content entities (such as nodes) and their `EntityMeta` entities. The relations are held directly to content entity revisions and `EntityMeta` revisions allowing to follow new revisions in both directions.
​
`EntityMeta` entities require a defined relation between themselves and content (host) entities. Moreover, each `EntityMeta` bundle is made applicable to each content entity bundle through configuration defined in 3rd party settings. The required configuration can be set automatically by using *EntityMetaRelationInstaller* service. An example of its usage can be seen in *entity_meta_example_install*. 
​
The `entity_meta_example` module contain several example plugins that are used for tests but can be used as references.

## Support for new content entities

Entity meta relations can be used with any content entity. 

However, the `emr_node` module already provides support for `Node` entities. It can be used to understand how to provide support for other content entities.
​
* A new `EntityMetaRelation` bundle should be created with at least two fields: 
  * one `EntityReferenceRevision` field that targets the (host) content entity revisions (see the `emr_node_revision` field as an example for relating to Node entities).
  * one field that targets the `EntityMeta` revisions (see the `emr_meta_revision` field as an example for relating to the `EntityMeta` entities. The storage of this field could be reused).
* The host entity definition should be altered to include the following properties:
    - `entity_meta_relation_bundle`: Should specify the `EntityMetaRelation` bundle you created above.
    - `entity_meta_relation_content_field`: Should specify the field name that relates to the content (host) entity you created above.
    - `entity_meta_relation_meta_field`: Should specify the field name that relates to the `EntityMeta` entities you created above.
    - `emr_content_form`: In case the content (host) entity form should be used to include a form to manipulate the related `EntityMeta` entities, this should specify the form handler class to use for this. 
​

Foe more information, check `emr_node_entity_type_alter` for an example how this is done for nodes:

The following is defined:

- The `EntityMetaRelation` bundle is `node_meta_relation`, and it has two fields:
  - `emr_node_revision`: points to the `Node` entity revision 
  - `emr_meta_revision`: points to the `EntityMeta` revision
- The handler class `NodeFormHandler` is defined to deal with content entity form changes.

## EntityMetaRelation plugins

`EntityMeta` entities are manageable through `EntityMetaRelation` plugins. The plugin should indicate the `EntityMeta` bundle it is associated within its definition:
​

E.g from `Drupal\entity_meta_speed\Plugin\EntityMetaRelation\SpeedConfiguration`:

```
@EntityMetaRelation(
 *   id = "speed",
 *   label = @Translation("Speed"),
 *   entity_meta_bundle = "speed",
 *   content_form = TRUE,
 *   entity_meta_wrapper_class = "\Drupal\entity_meta_speed\SpeedEntityMetaWrapper",
 *   description = @Translation("Speed.")
 * )
```
​
This plugin uses the `EntityMeta` bundle "speed" and provides a wrapper class to manipulate its data through `\Drupal\entity_meta_speed\SpeedEntityMetaWrapper`. The wrapper is optional.
​
## Using Entity Meta Relation API

Content (host) entities can manipulate their `EntityMeta` entities directly using the Computed field `emr_entity_metas`.
​
### Adding single EntityMeta entity to a content entity

```
$node_storage = \Drupal::entityTypeManager()->getStorage('node');

// Create a new node.

/** @var \Drupal\node\NodeInterface $node */
$node = $node_storage->create([
  'type' => 'entity_meta_multi_example',
  'title' => 'Node test',
]);


/** @var \Drupal\emr\Field\EntityMetaItemListInterface $entity_meta_list */
$entity_meta_list = $node->get('emr_entity_metas');

// Instantiate a new entity meta of a given bundle. Since it doesn't exist,
// it will be created on the fly.
/** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
$entity_meta = $entity_meta_list->getEntityMeta('visual');

// Set a meta value.
$entity_meta->set('field_color', 'red');

// Attach the new entity meta. This will add it to the list and once the
// node is saved, the relations are created automatically.
$entity_meta_list->attach($entity_meta);
$node->save();
```    
​
​
### Adding several EntityMeta entities to content entity

```
$node_storage = \Drupal::entityTypeManager()->getStorage('node');

// Create a new node.

/** @var \Drupal\node\NodeInterface $node */
$node = $node_storage->create([
  'type' => 'entity_meta_multi_example',
  'title' => 'Node test',
]);


/** @var \Drupal\emr\Field\EntityMetaItemListInterface $entity_meta_list */
$entity_meta_list = $node->get('emr_entity_metas');

$entity_metas = [];
/** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
$entity_meta = $entity_meta_list->getEntityMeta('visual');
$entity_meta->set('field_color', 'red');
$entity_metas[] = $entity_meta;

/** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
$entity_meta = $entity_meta_list->getEntityMeta('audio');
$entity_meta->set('field_volume', 'low');
$entity_metas[] = $entity_meta;

// Set the array of entites meta entities as values.
$entity_meta_list->set($entity_metas);
$node->save();
```    
​
​
### Using an EntityMetaWrapper

EntityMetaWrappers can be defined to interact with an `EntityMeta` entities without having to interact directly with its fields and configurations. A wrapper is associated with a `EntityMetaRelationPlugin` through the `entity_meta_wrapper_class` property
​
So instead of:
```
$example_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
$example_meta->set('field_volume', 'medium');
```
​
The following can be used:
```
$example_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
$example_meta->getWrapper()->setVolume('medium');
```
​
### Changing existing EntityMeta

Existing EntityMeta can be altered through the `attach()` method.

```
$example_meta = $node->get('emr_entity_metas')->getEntityMeta('audio');
$example_meta->getWrapper()->setVolume('medium');
$node->get('emr_entity_metas')->attach($example_meta);
$node->save();
```

Please note that the `attach()` method won't do anything if there is no change
detected in the `EntityMeta` entity.
​
### Removing existing EntityMeta  

Existing `EntityMeta` entities can be removed from the revision through the `detach()` method.

```
$example_meta = $node->get('emr_entity_metas')->getEntityMeta('example_bundle');
$node->get('emr_entity_metas')->detach($example_meta);
$node->save();
```

​
## Development setup
​
You can build the development site by running the following steps:
​
* Install the Composer dependencies:
​
```bash
composer install
```
​
A post command hook (`drupal:site-setup`) is triggered automatically after `composer install`.
It will make sure that the necessary symlinks are properly setup in the development site.
It will also perform token substitution in development configuration files such as `behat.yml.dist`.
​
* Install test site by running:
​
```bash
./vendor/bin/run drupal:site-install
```
​
The development site web root should be available in the `build` directory.
​
### Using Docker Compose
​
Alternatively, you can build a development site using [Docker](https://www.docker.com/get-docker) and 
[Docker Compose](https://docs.docker.com/compose/) with the provided configuration.
​
Docker provides the necessary services and tools such as a web server and a database server to get the site running, 
regardless of your local host configuration.
​
#### Requirements:
​
- [Docker](https://www.docker.com/get-docker)
- [Docker Compose](https://docs.docker.com/compose/)
​
#### Configuration
​
By default, Docker Compose reads two files, a `docker-compose.yml` and an optional `docker-compose.override.yml` file.
By convention, the `docker-compose.yml` contains your base configuration and it's provided by default.
The override file, as its name implies, can contain configuration overrides for existing services or entirely new 
services.
If a service is defined in both files, Docker Compose merges the configurations.
​
Find more information on Docker Compose extension mechanism on [the official Docker Compose documentation](https://docs.docker.com/compose/extends/).
​
#### Usage
​
To start, run:
​
```bash
docker-compose up
```
​
It's advised to not daemonize `docker-compose` so you can turn it off (`CTRL+C`) quickly when you're done working.
However, if you'd like to daemonize it, you have to add the flag `-d`:
​
```bash
docker-compose up -d
```
​
Then:
​
```bash
docker-compose exec web composer install
docker-compose exec web ./vendor/bin/run drupal:site-install
```
​
Using default configuration, the development site files should be available in the `build` directory and the development site
should be available at: [http://127.0.0.1:8080/build](http://127.0.0.1:8080/build).
​
#### Running the tests
​
To run the grumphp checks:
​
```bash
docker-compose exec web ./vendor/bin/grumphp run
```
​
To run the phpunit tests:
​
```bash
docker-compose exec web ./vendor/bin/phpunit
```
​
To run the behat tests:
​
```bash
docker-compose exec web ./vendor/bin/behat
```
​
## Contributing
​
Please read [the full documentation](https://github.com/openeuropa/openeuropa) for details on our code of conduct, and the process for submitting pull requests to us.
​
## Versioning
​
We use [SemVer](http://semver.org/) for versioning. For the available versions, see the [tags on this repository](https://github.com/openeuropa/entity_meta_relation/tags).
