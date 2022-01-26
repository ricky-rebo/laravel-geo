<?php

namespace Digitalion\LaravelGeo\Providers;

use Digitalion\LaravelGeo\Models\GeoCity;
use Digitalion\LaravelGeo\Models\GeoProvince;
use Digitalion\LaravelGeo\Models\GeoRegion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$default_country = strtoupper(config('geo.geocoding.country'));
		$db_connection = config('database.default');

		$models = ['GeoCity', 'GeoProvince', 'GeoRegion'];
		$with_geo_relations = true;
		foreach ($models as $model) {
			$model_namespace = '\\Digitalion\\LaravelGeo\\Models\\' . $model;
			$model_class = new $model_namespace();
			$table_name = $model_class->getTable();
			$with_geo_relations = false;
			try {
				$with_geo_relations = Schema::connection($db_connection)->hasTable($table_name);
			} catch (\Throwable $th) {
				$with_geo_relations = false;
			}
			if (!$with_geo_relations) break;
		}

		Blueprint::macro('address', function (bool $required = true) use ($default_country, $with_geo_relations) {
			$this->string('route', config('geo.database.route', 100))->nullable(!$required);
			$this->string('street_number', config('geo.database.street_number', 25))->nullable();
			switch (config('geo.database.postal_code', 'mediumint')) {
				case 'int':
					$this->unsignedInteger('postal_code')->nullable(!$required);
					break;

				case 'bigint':
					$this->unsignedBigInteger('postal_code')->nullable(!$required);
					break;

				case 'mediumint':
				default:
					$this->unsignedMediumInteger('postal_code')->nullable(!$required);
					break;
			}
			$this->string('locality', config('geo.database.locality', 100))->nullable();
			if ($with_geo_relations) $this->foreignIdFor(GeoCity::class)->nullable()->constrained()->nullOnDelete();
			$this->string('city', config('geo.database.city', 100))->nullable(!$required);
			if ($with_geo_relations) $this->foreignIdFor(GeoProvince::class)->nullable()->constrained()->nullOnDelete();
			$this->string('province', config('geo.database.province', 2))->nullable(!$required);
			if ($with_geo_relations) $this->foreignIdFor(GeoRegion::class)->nullable()->constrained()->nullOnDelete();
			$this->string('region', config('geo.database.region', 100))->nullable(!$required);
			$this->string('country', config('geo.database.country', 5))->nullable(!$required)->default($default_country);
			$this->double('latitude', 11, 8)->nullable(!$required);
			$this->double('longitude', 11, 8)->nullable(!$required);
		});
	}
}
