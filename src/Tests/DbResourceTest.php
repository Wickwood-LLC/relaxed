<?php

/**
 * @file
 * Contains \Drupal\relaxed\Tests\DbResourceTest.
 */

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db resource.
 *
 * @group relaxed
 */
class DbResourceTest extends ResourceTestBase {

  public function testHead() {
    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:db', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest($this->workspace->id(), 'HEAD', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $this->assertTrue(empty($response), 'HEAD request returned no body.');

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $this->httpRequest($this->workspace->id(), 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $this->httpRequest($this->workspace->id(), 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
  }

  public function testGet() {
    $this->enableService('relaxed:db', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Add an entity to the workspace to test the update_seq property.
    $entity = $this->entityTypeManager->getStorage('entity_test_rev')->create();
    $entity->save();
    $entity->name = $this->randomMachineName();
    $entity->save();

    $response = $this->httpRequest($this->workspace->id(), 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    // Only assert one example property here, other properties should be
    // checked in serialization tests.
    $this->assertEqual($data['db_name'], $this->workspace->id(), 'GET request returned correct db_name.');

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $this->httpRequest($this->workspace->id(), 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $this->httpRequest($this->workspace->id(), 'GET', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
  }

  public function testPut() {
    $this->enableService('relaxed:db', 'PUT');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'create');
    $permissions[] = 'restful put relaxed:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $id = $this->randomMachineName();
    $response = $this->httpRequest($id, 'PUT', NULL);
    $this->assertResponse('201', 'HTTP response code is correct for new database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['ok']), 'PUT request returned ok.');

    $id = $this->randomMachineName();
    $entity = $this->createWorkspace($id);
    $entity->save();

    // Test putting an existing workspace.
    $response = $this->httpRequest($entity->id(), 'PUT', NULL);
    $this->assertResponse('412', 'HTTP response code is correct for existing database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['error']), 'PUT request returned error.');

    // Create a new ID.
    $id = $this->randomMachineName();

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 403.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $this->httpRequest($id, 'PUT', NULL);
    $this->assertResponse('403', 'HTTP response code is correct.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 201.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $this->httpRequest($id, 'PUT', NULL);
    $this->assertResponse('201', 'HTTP response code is correct.');
  }

  public function testPost() {
    $this->enableService('relaxed:db', 'POST');
    $serializer = $this->container->get('serializer');

    $entity_types = ['entity_test_rev'];
    foreach ($entity_types as $entity_type) {
      // Create a user with the correct permissions.
      $permissions = $this->entityPermissions($entity_type, 'create');
      $permissions[] = 'restful post relaxed:db';
      $account = $this->drupalCreateUser($permissions);
      $this->drupalLogin($account);

      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->create(['user_id' => $account->id()]);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      $response = $this->httpRequest($this->workspace->id(), 'POST', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct when posting new entity');
      $data = Json::decode($response);
      $this->assertTrue(isset($data['rev']), 'POST request returned a revision hash.');

      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->create(['user_id' => $account->id()]);
      $serialized = $serializer->serialize($entity, $this->defaultFormat);

      // Create a user with the 'perform pull replication' permission and test the
      // response code. It should be 403.
      $account = $this->drupalCreateUser(['perform pull replication']);
      $this->drupalLogin($account);
      $this->httpRequest($this->workspace->id(), 'POST', $serialized);
      $this->assertResponse('403', 'HTTP response code is correct.');

      // Create a user with the 'perform push replication' permission and test the
      // response code. It should be 201.
      $account = $this->drupalCreateUser(['perform push replication']);
      $this->drupalLogin($account);
      $this->httpRequest($this->workspace->id(), 'POST', $serialized);
      $this->assertResponse('201', 'HTTP response code is correct.');
    }
  }

  public function testDelete() {
    $this->enableService('relaxed:db', 'DELETE');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'delete');
    $permissions[] = 'restful delete relaxed:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $id = $this->randomMachineName();
    $entity = $this->createWorkspace($id);
    $entity->save();

    $response = $this->httpRequest($entity->id(), 'DELETE', NULL);
    $this->assertResponse('200', 'HTTP response code is correct for new database');
    $data = Json::decode($response);
    $this->assertTrue(!empty($data['ok']), 'DELETE request returned ok.');

    $entity = $this->entityTypeManager
      ->getStorage('workspace')
      ->load($entity->id());
    $this->assertTrue(empty($entity), 'The entity being DELETED was not loaded.');

    // Create a new workspace.
    $id = $this->randomMachineName();
    $entity = $this->createWorkspace($id);
    $entity->save();

    // Create a user with the 'perform pull replication' permission and test the
    // response code. It should be 403.
    $account = $this->drupalCreateUser(['perform pull replication']);
    $this->drupalLogin($account);
    $this->httpRequest($entity->id(), 'DELETE', NULL);
    $this->assertResponse('403', 'HTTP response code is correct.');

    // Create a user with the 'perform push replication' permission and test the
    // response code. It should be 200.
    $account = $this->drupalCreateUser(['perform push replication']);
    $this->drupalLogin($account);
    $this->httpRequest($entity->id(), 'DELETE', NULL);
    $this->assertResponse('200', 'HTTP response code is correct.');
  }

}
