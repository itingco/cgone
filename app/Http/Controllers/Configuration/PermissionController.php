<?php
namespace App\Http\Controllers\Configuration; use App\Http\Controllers\Controller; use App\Models\Permission; class PermissionController extends Controller { public function __construct(){ $this->middleware('menu.permission:config.permissions,view'); } public function index(){return view('configuration.permissions.index',['rows'=>Permission::orderBy('id')->get()]);} }
