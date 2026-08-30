<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign In - CGOne ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{--ink:#10233d;--muted:#6b7890;--brand:#ff9f1c;--panel:#0f2036}*{box-sizing:border-box}body{margin:0;background:#eef2f7;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ink)}.login-shell{min-height:100vh;display:grid;grid-template-columns:minmax(360px,1.15fr) minmax(420px,.85fr)}.brand-panel{position:relative;overflow:hidden;padding:64px;display:flex;flex-direction:column;justify-content:space-between;color:white;background:radial-gradient(circle at 15% 15%,rgba(255,159,28,.35),transparent 28%),radial-gradient(circle at 82% 70%,rgba(88,166,255,.25),transparent 32%),linear-gradient(145deg,#10233d,#071421)}.brand-panel:after{content:"";position:absolute;width:420px;height:420px;border:1px solid rgba(255,255,255,.12);border-radius:50%;right:-130px;top:10%;box-shadow:0 0 0 70px rgba(255,255,255,.025),0 0 0 140px rgba(255,255,255,.018)}.brand-mark{display:flex;align-items:center;gap:14px;font-weight:800;font-size:1.25rem;letter-spacing:.03em;position:relative;z-index:2}.brand-icon{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:var(--brand);color:#111;font-weight:900;box-shadow:0 12px 30px rgba(255,159,28,.25)}.brand-copy{max-width:620px;position:relative;z-index:2}.brand-copy h1{font-size:clamp(2.6rem,5vw,5.2rem);line-height:.96;letter-spacing:-.055em;margin:0 0 26px}.brand-copy p{max-width:540px;font-size:1.05rem;line-height:1.8;color:#bdc9d8}.feature-row{display:flex;gap:12px;flex-wrap:wrap;margin-top:32px}.feature-pill{border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);backdrop-filter:blur(6px);padding:9px 14px;border-radius:999px;font-size:.84rem;color:#dbe5f0}.brand-foot{position:relative;z-index:2;color:#8394a9;font-size:.85rem}.form-panel{display:flex;align-items:center;justify-content:center;background:white;padding:44px}.login-card{width:min(430px,100%)}.eyebrow{text-transform:uppercase;letter-spacing:.16em;font-size:.72rem;font-weight:800;color:var(--brand);margin-bottom:12px}.login-card h2{font-size:2.05rem;letter-spacing:-.035em;font-weight:800}.lead-copy{color:var(--muted);margin-bottom:34px}.form-label{font-weight:650;font-size:.88rem}.form-control{min-height:50px;border-color:#dce3ec;border-radius:12px;padding-left:14px}.form-control:focus{border-color:#f5a63f;box-shadow:0 0 0 .2rem rgba(255,159,28,.12)}.password-wrap{position:relative}.password-wrap .form-control{padding-right:52px}.password-toggle{position:absolute;right:8px;top:7px;width:38px;height:38px;border:0;border-radius:9px;background:#f2f5f8;color:#52627a}.btn-login{min-height:52px;border:0;border-radius:12px;background:#10233d;font-weight:750;box-shadow:0 14px 30px rgba(16,35,61,.18)}.btn-login:hover{background:#172f4d}.form-check-label{color:#66758c;font-size:.88rem}.support-text{font-size:.8rem;color:#99a4b3;margin-top:26px;text-align:center}@media(max-width:900px){.login-shell{grid-template-columns:1fr}.brand-panel{display:none}.form-panel{min-height:100vh;padding:28px}}
</style>
</head>
<body>
<div class="login-shell">
<section class="brand-panel">
 <div class="brand-mark"><div class="brand-icon">CG</div><div>CGOne ERP</div></div>
 <div class="brand-copy"><div class="eyebrow">Enterprise Resource Planning</div><h1>One system.<br>Clearer control.</h1><p>Connect sales, purchasing, inventory, finance, approvals, ledgers, and audit history in one operational workspace for Cipta Group Indonesia.</p><div class="feature-row"><span class="feature-pill">Sales & AR</span><span class="feature-pill">Purchase & AP</span><span class="feature-pill">Inventory</span><span class="feature-pill">Finance & GL</span></div></div>
 <div class="brand-foot">CGOne ERP · Cipta Group Indonesia</div>
</section>
<section class="form-panel"><div class="login-card">
 <div class="eyebrow">Secure access</div><h2>Welcome back</h2><p class="lead-copy">Sign in to continue to your ERP workspace.</p>
 @if(session('warning'))<div class="alert alert-warning border-0 rounded-3">{{ session('warning') }}</div>@endif
 @if($errors->any())<div class="alert alert-danger border-0 rounded-3">{{ $errors->first() }}</div>@endif
 <form method="post" action="{{ route('login.store') }}">@csrf
  <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="name@company.com" required autofocus autocomplete="username"></div>
  <div class="mb-3"><label class="form-label">Password</label><div class="password-wrap"><input id="login-password" class="form-control" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password"><button id="password-toggle" class="password-toggle" type="button" aria-label="Show password">◉</button></div></div>
  <div class="d-flex justify-content-between align-items-center mb-4"><div class="form-check"><input type="hidden" name="remember" value="0"><input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"><label class="form-check-label" for="remember">Remember me</label></div></div>
  <button class="btn btn-primary btn-login w-100">Sign in</button>
 </form><div class="support-text">Authorized users only · User activities are recorded for audit purposes.</div>
</div></section>
</div>
<script>document.getElementById('password-toggle').addEventListener('click',function(){const i=document.getElementById('login-password');i.type=i.type==='password'?'text':'password';this.textContent=i.type==='password'?'◉':'◎';});</script>
</body></html>
