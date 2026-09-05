<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign In - CGOne ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root{--ink:#10233d;--muted:#6b7890;--brand:#ff9f1c;--panel:#0f2036}
*{box-sizing:border-box}
body{margin:0;background:#eef2f7;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ink)}
.login-shell{min-height:100vh;display:grid;grid-template-columns:minmax(360px,.9fr) minmax(420px,1.1fr)}
.brand-panel{position:relative;overflow:hidden;padding:56px;display:flex;flex-direction:column;justify-content:space-between;color:white;background:linear-gradient(145deg,#10233d,#071421)}
.brand-panel:after{content:"";position:absolute;width:360px;height:360px;border:1px solid rgba(255,255,255,.08);border-radius:50%;right:-150px;top:20%;box-shadow:0 0 0 70px rgba(255,255,255,.018),0 0 0 140px rgba(255,255,255,.012)}
.brand-mark{display:flex;align-items:center;gap:14px;font-weight:800;font-size:1.25rem;position:relative;z-index:2}
.brand-icon{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;background:var(--brand);color:#111;font-weight:900}
.brand-copy{position:relative;z-index:2;max-width:440px}
.brand-copy h1{font-size:2rem;letter-spacing:-.035em;margin-bottom:14px}
.brand-copy p{color:#b9c5d4;line-height:1.75;margin:0}
.brand-foot{position:relative;z-index:2;color:#8596aa;font-size:.84rem;display:flex;gap:.55rem;flex-wrap:wrap;align-items:center}
.release-badge{border:1px solid rgba(255,255,255,.12);border-radius:999px;padding:.28rem .6rem;color:#cbd5e1;background:rgba(255,255,255,.04)}
.form-panel{display:flex;align-items:center;justify-content:center;background:white;padding:44px}
.login-card{width:min(430px,100%)}
.eyebrow{text-transform:uppercase;letter-spacing:.16em;font-size:.72rem;font-weight:800;color:var(--brand);margin-bottom:12px}
.login-card h2{font-size:2.05rem;letter-spacing:-.035em;font-weight:800}
.lead-copy{color:var(--muted);margin-bottom:34px}
.form-label{font-weight:650;font-size:.88rem}
.form-control{min-height:50px;border-color:#dce3ec;border-radius:12px;padding-left:14px}
.form-control:focus{border-color:#f5a63f;box-shadow:0 0 0 .2rem rgba(255,159,28,.12)}
.password-wrap{position:relative}.password-wrap .form-control{padding-right:52px}
.password-toggle{position:absolute;right:8px;top:7px;width:38px;height:38px;border:0;border-radius:9px;background:#f2f5f8;color:#52627a}
.btn-login{min-height:52px;border:0;border-radius:12px;background:#10233d;font-weight:750;box-shadow:0 14px 30px rgba(16,35,61,.18)}
.btn-login:hover{background:#172f4d}.form-check-label{color:#66758c;font-size:.88rem}
.support-text{font-size:.8rem;color:#99a4b3;margin-top:26px;text-align:center}
@media(max-width:900px){.login-shell{grid-template-columns:1fr}.brand-panel{display:none}.form-panel{min-height:100vh;padding:28px}}
</style>
</head>
<body>
<div class="login-shell">
<section class="brand-panel">
    <div class="brand-mark"><div class="brand-icon">CG</div><div>CGOne ERP</div></div>
    <div class="brand-copy">
        <div class="eyebrow">Enterprise Resource Planning</div>
        <h1>Operational control in one workspace.</h1>
        <p>Sales, purchase, inventory, finance, approval, ledger, and audit for Cipta Group Indonesia.</p>
    </div>
    <div class="brand-foot">
        <span>CGOne ERP · Cipta Group Indonesia</span>
        <span class="release-badge">Version {{ config('version.release') }}</span>
    </div>
</section>

<section class="form-panel"><div class="login-card">
    <div class="eyebrow">Secure access</div>
    <h2>Welcome back</h2>
    <p class="lead-copy">Sign in to continue to your ERP workspace.</p>

    @if($errors->any())<div class="alert alert-danger border-0 rounded-3">{{ $errors->first() }}</div>@endif

    <form method="post" action="{{ route('login.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="{{ old('email') }}" placeholder="name@company.com" required autofocus autocomplete="username">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="password-wrap">
                <input id="login-password" class="form-control" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                <button id="password-toggle" class="password-toggle" type="button" aria-label="Show password">◉</button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input type="hidden" name="remember" value="0">
                <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
        </div>
        <button class="btn btn-primary btn-login w-100">Sign in</button>
    </form>
    <div class="support-text">Authorized users only · User activities are recorded for audit purposes.</div>
</div></section>
</div>
<script>
document.getElementById('password-toggle').addEventListener('click',function(){
    const input=document.getElementById('login-password');
    input.type=input.type==='password'?'text':'password';
    this.textContent=input.type==='password'?'◉':'◎';
});
</script>
</body>
</html>
