<?php
namespace App\Http\Controllers\Posting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\Posting\DocumentPostingService;
use App\Services\Security\MenuAuthorizationService;
use App\Services\System\DocumentSequenceService;

class PostingController extends Controller
{
    public function __construct(
        private MenuAuthorizationService $authz,
        private DocumentPostingService $posting,
        private DocumentSequenceService $numbers,
    ) {}

    public function preview(string $type,int $id)
    {
        $menu=$this->posting->menuCode($type);
        abort_unless($this->authz->allows(auth()->user(),$menu,'post'),403);
        $result=$this->posting->preview($type,$id);
        $doc=$result['document'];
        $preview=$result['preview'];
        $expectedPostedNo=$this->numbers->previewNext($this->posting->sequenceCode($type));
        $accounts=ChartOfAccount::whereIn('id',collect($preview['gl'])->pluck('account_id'))->get()->keyBy('id');
        return view('posted.preview',compact('doc','preview','type','accounts','expectedPostedNo'));
    }

    public function post(string $type,int $id)
    {
        $menu=$this->posting->menuCode($type);
        abort_unless($this->authz->allows(auth()->user(),$menu,'post'),403);
        $posted=$this->posting->post($type,$id,auth()->id());
        return redirect()->route('posted.show',[$type,$posted->id])->with('success','Document posted successfully.');
    }
}
