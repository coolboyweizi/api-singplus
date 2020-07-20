<?php

namespace FeatureTest\SingPlus;

trait MongodbClearTrait
{
  public function enableMongodbClearForTest() {
    $database = $this->app->make('db');

    $this->beforeApplicationDestroyed(function () use ($database) {
      $database->connection('mongodb')->drop();
    });
  }
}
