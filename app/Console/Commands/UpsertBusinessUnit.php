<?php
namespace App\Console\Commands;
use App\Models\BusinessUnit; use App\Services\Tenancy\DatabaseContext; use Illuminate\Console\Command;
final class UpsertBusinessUnit extends Command {
 protected $signature='erp:business-unit {code} {name} {--database=} {--default}';
 protected $description='Create or update a Business Unit on one ERP database.';
 public function handle(DatabaseContext $databases): int { $target=(string)($this->option('database')?:$databases->defaultDatabase()); if(!$databases->isAllowed($target)){ $this->error('Database tidak terdaftar pada ERP_DATABASES: '.$target); return self::FAILURE; } $databases->activate($target);$databases->ping();$code=strtoupper(trim((string)$this->argument('code')));$name=trim((string)$this->argument('name'));if($code===''||$name===''){ $this->error('Code dan name wajib diisi.');return self::FAILURE;}if((bool)$this->option('default'))BusinessUnit::query()->update(['is_default'=>false]);$bu=BusinessUnit::query()->updateOrCreate(['code'=>$code],['name'=>$name,'is_active'=>true,'is_default'=>(bool)$this->option('default')]);$this->info("BU {$bu->code} - {$bu->name} tersimpan di {$target}.");return self::SUCCESS; }
}
