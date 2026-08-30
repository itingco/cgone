<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return view('profile.show',['user'=>$request->user()->load(['department','roles'])]);
    }

    public function password(){ return view('profile.password'); }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password'=>['required','current_password'],
            'password'=>['required','string','min:8','confirmed'],
        ]);
        $request->user()->update(['password'=>$data['password']]);
        return redirect()->route('profile.show')->with('success','Password berhasil diubah.');
    }
}
