<?php
namespace App\Services\Inventory;
use App\Models\{Location,LocationBin}; use DomainException;
class LocationBinService { public function validate(Location $location,?LocationBin $bin): void {if($location->bin_mandatory&&!$bin)throw new DomainException("Location {$location->code} requires a bin.");if($bin&&((int)$bin->location_id!==(int)$location->id||!$bin->is_active))throw new DomainException('Invalid bin for selected Location.');if(!$location->is_active)throw new DomainException("Location {$location->code} is inactive.");} }
