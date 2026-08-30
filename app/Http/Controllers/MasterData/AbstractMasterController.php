<?php
namespace App\Http\Controllers\MasterData;
use App\Http\Controllers\Controller;
use App\Services\DataViews\DataViewService;
use App\Services\MasterData\MasterChangeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

abstract class AbstractMasterController extends Controller {
 protected string $modelClass; protected string $menuCode; protected string $title; protected array $fields=[]; protected array $fieldGroups=[]; protected array $columns=['code'=>'Code','name'=>'Name'];
 public function __construct(){ $this->middleware("menu.permission:{$this->menuCode},view")->only(['index','show']); $this->middleware("menu.permission:{$this->menuCode},create")->only(['create','store']); $this->middleware("menu.permission:{$this->menuCode},edit")->only(['edit','update','status']); $this->middleware("menu.permission:{$this->menuCode},export")->only(['export']); }
 abstract protected function rules(?int $id=null): array;
 protected function options(): array{return [];}
 protected function model(int $id): Model{return ($this->modelClass)::query()->findOrFail($id);}
 protected function dataViewFields(): array { $defs=[];foreach($this->columns as $key=>$label)$defs[$key]=['label'=>$label,'type'=>'text','column'=>$key];$defs['is_active']=['label'=>'Status','type'=>'boolean','column'=>'is_active','options'=>['1'=>'Active','0'=>'Inactive']];return $defs; }
 public function index(Request $request,DataViewService $views){$fields=$this->dataViewFields();$state=$views->resolve($request,$this->menuCode,$fields,$this->columns);$q=($this->modelClass)::query();$relations=array_values(array_unique(array_filter(array_map(fn($f)=>$f['relation']??null,$fields))));if($relations)$q->with($relations);if($request->filled('q')){$term=(string)$request->input('q');$q->where(fn($x)=>$x->where('code','like',"%{$term}%")->orWhere('name','like',"%{$term}%"));}$views->apply($q,$fields,$state);if(empty($state['sort']))$q->orderBy('code');$rows=$q->paginate($state['pageSize'])->withQueryString();return view('master.index',['rows'=>$rows,'title'=>$this->title,'columns'=>$views->labels($fields,$state['columns']),'dataViewFields'=>$fields,'dataViewState'=>$state,'routeBase'=>$this->routeBase(),'menuCode'=>$this->menuCode,'moduleKey'=>$this->menuCode]);}
 public function create(){return view('master.form',['record'=>new $this->modelClass,'title'=>$this->title,'fields'=>$this->fields,'fieldGroups'=>$this->fieldGroups,'options'=>$this->options(),'routeBase'=>$this->routeBase(),'isEdit'=>false]);}
 public function store(Request $request,MasterChangeService $service){$model=$service->create($this->modelClass,$request->validate($this->rules()),$this->menuCode);return redirect()->route($this->routeBase().'.show',$model)->with('success','Data berhasil dibuat.');}
 public function show(int $id){return view('master.show',['record'=>$this->model($id),'title'=>$this->title,'fields'=>$this->fields,'fieldGroups'=>$this->fieldGroups,'options'=>$this->options(),'routeBase'=>$this->routeBase(),'menuCode'=>$this->menuCode]);}
 public function edit(int $id){return view('master.form',['record'=>$this->model($id),'title'=>$this->title,'fields'=>$this->fields,'fieldGroups'=>$this->fieldGroups,'options'=>$this->options(),'routeBase'=>$this->routeBase(),'isEdit'=>true]);}
 public function update(Request $request,int $id,MasterChangeService $service){$model=$service->update($this->model($id),$request->validate($this->rules($id)),$this->menuCode);return redirect()->route($this->routeBase().'.show',$model)->with('success','Data berhasil diperbarui.');}
 public function status(Request $request,int $id,MasterChangeService $service){$data=$request->validate(['is_active'=>['required','boolean']]);$service->setActive($this->model($id),(bool)$data['is_active'],$this->menuCode);return back()->with('success','Status berhasil diperbarui.');}
 public function export(Request $request,DataViewService $views){$fields=$this->dataViewFields();$state=$views->resolve($request,$this->menuCode,$fields,$this->columns);$q=($this->modelClass)::query();$relations=array_values(array_unique(array_filter(array_map(fn($f)=>$f['relation']??null,$fields))));if($relations)$q->with($relations);$views->apply($q,$fields,$state);$columns=$state['columns'];$title=str_replace(' ','-',strtolower($this->title)).'.csv';return response()->streamDownload(function() use($q,$columns,$fields,$views){$out=fopen('php://output','w');fputcsv($out,array_map(fn($c)=>$fields[$c]['label']??$c,$columns));$q->chunk(500,function($rows) use($out,$columns,$fields,$views){foreach($rows as $row){$line=[];foreach($columns as $c)$line[]=$views->value($row,$fields[$c]);fputcsv($out,$line);}});fclose($out);},$title,['Content-Type'=>'text/csv']);}
 protected function routeBase(): string{return $this->menuCode;}
 protected function uniqueCode(?int $id=null): array{$table=(new $this->modelClass)->getTable();return ['required','string','max:100',Rule::unique($table,'code')->ignore($id)];}
}
