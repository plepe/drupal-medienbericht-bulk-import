<?php

namespace Drupal\medienbericht_bulk_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Imports medienberichte in bulk.
 */
class ImportController extends ControllerBase {

  /**
   * Returns the page where the URLs can be pasted.
   *
   * return []
   */
  public function show () {
    $build = [
      '#markup' => 'Paste a list of URLs into this fields and press "Import":<div id="import"></div>',
      '#attached' => ['library' => ['medienbericht_bulk_import/general']],
    ];
    return $build;
  }

  /**
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function import (Request $request): JsonResponse {
    return new JsonResponse([
      'url' => $request->request->get('url'),
    ], 200);
  }
}
