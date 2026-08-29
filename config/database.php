<?php
use Illuminate\Support\Str;
return [
'default'=>env('DB_CONNECTION','sqlsrv'),
'connections'=>[
'sqlite'=>['driver'=>'sqlite','url'=>env('DATABASE_URL'),'database'=>env('DB_DATABASE',database_path('database.sqlite')),'prefix'=>'','foreign_key_constraints'=>env('DB_FOREIGN_KEYS',true)],
'sqlsrv'=>['driver'=>'sqlsrv','url'=>env('DATABASE_URL'),'host'=>env('DB_HOST','localhost'),'port'=>env('DB_PORT','1433'),'database'=>env('DB_DATABASE','erp_baseline'),'username'=>env('DB_USERNAME','sa'),'password'=>env('DB_PASSWORD',''),'charset'=>'utf8','prefix'=>'','prefix_indexes'=>true,'encrypt'=>env('DB_ENCRYPT','no'),'trust_server_certificate'=>env('DB_TRUST_SERVER_CERTIFICATE','true')],
],
'migrations'=>'migrations','redis'=>['client'=>env('REDIS_CLIENT','phpredis'),'options'=>['cluster'=>env('REDIS_CLUSTER','redis'),'prefix'=>env('REDIS_PREFIX',Str::slug(env('APP_NAME','laravel'),'_').'_database_')],'default'=>['url'=>env('REDIS_URL'),'host'=>env('REDIS_HOST','127.0.0.1'),'username'=>env('REDIS_USERNAME'),'password'=>env('REDIS_PASSWORD'),'port'=>env('REDIS_PORT','6379'),'database'=>env('REDIS_DB','0')]],
];
