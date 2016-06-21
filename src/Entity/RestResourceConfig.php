<?php

namespace Drupal\relaxed\Entity;

use Drupal\rest\Entity\RestResourceConfig as CoreRestResourceConfig;

class RestResourceConfig extends CoreRestResourceConfig {
  
  /**
   * {@inheritdoc}
   */
  protected function normalizeRestMethod($method) {
    $valid_methods = ['GET', 'POST', 'PATCH', 'DELETE', 'PUT', 'HEAD'];
    $normalised_method = strtoupper($method);
    if (!in_array($normalised_method, $valid_methods)) {
      throw new \InvalidArgumentException('The method is not supported.');
    }
  }

}
