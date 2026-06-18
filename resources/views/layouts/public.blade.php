<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'EduPay Cameroun')</title>
<style>
:root{--ep-navy:#0B2545;--ep-teal:#0D9E75;--ep-teal2:#0A8562;--ep-teal-lt:#E0F5EE;--ep-teal-mid:#9FE1CB;--ep-gold:#E8A020;--ep-gold-lt:#FEF3DC;--ep-red:#D94040;--ep-red-lt:#FBEAEA;--ep-blue-lt:#E6F0FB;--ep-purple-lt:#EDE9FE;--border:rgba(0,0,0,0.09);--radius-md:8px;--radius-lg:12px;}
*{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:14px;background:#f1f3f5;color:#1a1a2e;}
.epcard{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;}
.pill{display:inline-block;font-size:11px;padding:3px 9px;border-radius:20px;font-weight:500;}
.pg{background:#E0F5EE;color:#085041;}.pa{background:#FEF3DC;color:#8B5E10;}
.pr{background:#FBEAEA;color:#9B2C2C;}.pb{background:#E6F0FB;color:#1A4F8A;}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}
.kpi{background:#f8f9fa;border-radius:var(--radius-md);padding:16px;text-align:center;}
.kval{font-size:22px;font-weight:700;color:#1a1a2e;}
.klbl{font-size:11px;color:#888;margin-top:4px;}
.seclbl{font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.06em;margin:18px 0 10px;}
.btn-p{background:var(--ep-teal);color:#fff;border:none;padding:11px 20px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;width:100%;transition:background .15s;text-decoration:none;display:block;text-align:center;}
.btn-p:hover{background:var(--ep-teal2);}
.btn-o{background:transparent;color:var(--ep-teal);border:2px solid var(--ep-teal);padding:9px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:500;cursor:pointer;width:100%;transition:all .15s;text-decoration:none;display:block;text-align:center;}
.btn-o:hover{background:var(--ep-teal-lt);}
.inp{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;outline:none;transition:border .15s;}
.inp:focus{border-color:var(--ep-teal);}
.lbl{font-size:11px;color:#666;margin-bottom:5px;font-weight:500;}
.divider{height:1px;background:#f0f0f0;margin:14px 0;}
.ep-body2{padding:24px 28px;background:#f1f3f5;}
/* HERO */
.hero-band{background:var(--ep-navy);color:#fff;overflow:hidden;}
.hero-top{display:flex;align-items:center;justify-content:space-between;padding:14px 28px;border-bottom:1px solid rgba(255,255,255,.08);}
.logo-t{font-size:20px;font-weight:700;letter-spacing:-.3px;}
.logo-t span{color:#5DCAA5;}
.hero-main{padding:48px 28px 36px;}
.hero-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:5px 14px;font-size:11px;color:rgba(255,255,255,.75);margin-bottom:18px;}
.hero-h1{font-size:36px;font-weight:700;line-height:1.2;max-width:520px;margin-bottom:14px;}
.hero-h1 em{font-style:normal;color:#5DCAA5;}
.hero-sub{font-size:14px;color:rgba(255,255,255,.6);max-width:440px;margin-bottom:28px;line-height:1.65;}
.hero-btns{display:flex;gap:12px;flex-wrap:wrap;}
.hbtn-main{background:var(--ep-teal);color:#fff;border:none;padding:13px 26px;border-radius:var(--radius-md);font-size:14px;font-weight:500;cursor:pointer;transition:background .15s;text-decoration:none;}
.hbtn-main:hover{background:var(--ep-teal2);}
.hbtn-ghost{background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.2);padding:13px 26px;border-radius:var(--radius-md);font-size:14px;cursor:pointer;text-decoration:none;}
.hero-stats{display:flex;border-top:1px solid rgba(255,255,255,.08);}
.hstat{flex:1;padding:18px 28px;border-right:1px solid rgba(255,255,255,.08);text-align:center;}
.hstat:last-child{border-right:none;}
.hstat-v{font-size:22px;font-weight:700;color:#5DCAA5;}
.hstat-l{font-size:11px;color:rgba(255,255,255,.5);margin-top:3px;}
/* FEATURES */
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
.feat-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;position:relative;overflow:hidden;}
.feat-icon{width:42px;height:42px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.feat-icon svg{width:22px;height:22px;}
.feat-title{font-size:14px;font-weight:600;margin-bottom:7px;}
.feat-desc{font-size:12px;color:#777;line-height:1.65;}
.feat-line{position:absolute;top:0;left:0;width:3px;height:100%;}
/* ABOUT */
.mission-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;border-left:3px solid var(--ep-teal);}
.team-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;text-align:center;}
.team-av{width:54px;height:54px;border-radius:50%;margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;}
.value-card{padding:16px;border-radius:var(--radius-md);background:#f8f9fa;border-left:3px solid transparent;}
/* TESTIMONIALS */
.testi-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;}
.testi-quote{font-size:36px;color:var(--ep-teal-mid);line-height:1;margin-bottom:6px;font-family:Georgia,serif;}
.testi-text{font-size:13px;color:#333;line-height:1.75;margin-bottom:16px;font-style:italic;}
.testi-author{display:flex;align-items:center;gap:12px;}
.stars{color:var(--ep-gold);font-size:14px;margin-bottom:7px;}
.av{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;}
/* FOOTER */
.ep-footer{background:var(--ep-navy);color:rgba(255,255,255,.75);padding:36px 28px 22px;}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:28px;margin-bottom:28px;}
.footer-logo{font-size:20px;font-weight:700;color:#fff;margin-bottom:8px;}
.footer-logo span{color:#5DCAA5;}
.footer-desc{font-size:12px;color:rgba(255,255,255,.5);line-height:1.7;margin-bottom:14px;}
.footer-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:5px 10px;font-size:11px;color:rgba(255,255,255,.6);margin-right:6px;margin-bottom:6px;}
.footer-col-title{font-size:11px;font-weight:600;color:rgba(255,255,255,.9);text-transform:uppercase;letter-spacing:.07em;margin-bottom:12px;}
.footer-link{display:block;font-size:12px;color:rgba(255,255,255,.5);margin-bottom:8px;cursor:pointer;text-decoration:none;}
.footer-link:hover{color:rgba(255,255,255,.85);}
.footer-bottom{border-top:1px solid rgba(255,255,255,.08);padding-top:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.footer-legal{font-size:11px;color:rgba(255,255,255,.35);}
.footer-socials{display:flex;gap:8px;}
.social-btn{width:32px;height:32px;border-radius:6px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;font-size:12px;cursor:pointer;color:rgba(255,255,255,.6);font-weight:600;}
.cert-badge{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:6px;padding:4px 10px;font-size:10px;color:rgba(255,255,255,.45);}
.certif{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;}
/* FORM */
.form-header{background:var(--ep-navy);color:#fff;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;}
.form-body{flex:1;display:flex;align-items:flex-start;justify-content:center;padding:32px 20px;}
.form-card{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;width:100%;max-width:520px;}
.form-card-wide{background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);padding:28px;width:100%;max-width:720px;}
.form-title{font-size:18px;font-weight:700;margin-bottom:4px;}
.form-sub{font-size:13px;color:#888;margin-bottom:22px;}
.form-section{font-size:11px;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.07em;margin:18px 0 10px;padding-bottom:6px;border-bottom:1px solid #f0f0f0;}
.select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;background:#fff;outline:none;}
.select:focus{border-color:var(--ep-teal);}
.check-row{display:flex;align-items:flex-start;gap:10px;margin-bottom:14px;font-size:12px;color:#555;line-height:1.5;}
.check-row input{margin-top:2px;flex-shrink:0;}
.inp-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.textarea{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:var(--radius-md);font-size:13px;margin-bottom:12px;resize:vertical;min-height:80px;outline:none;font-family:inherit;}
.textarea:focus{border-color:var(--ep-teal);}
/* APP LAYOUT */
.app-header{background:var(--ep-navy);color:#fff;padding:13px 24px;display:flex;align-items:center;justify-content:space-between;}
.app-body{display:flex;min-height:calc(100vh - 58px);}
.sidebar{width:200px;flex-shrink:0;padding:14px;background:#fff;border-right:1px solid var(--border);}
.sbar-item{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:var(--radius-md);font-size:13px;color:#555;cursor:pointer;margin-bottom:2px;text-decoration:none;}
.sbar-item svg{width:15px;height:15px;flex-shrink:0;}
.sbar-item.on{background:var(--ep-teal-lt);color:#085041;font-weight:600;}
.sbar-item:hover:not(.on){background:#f0f0f0;}
.main-content{flex:1;padding:22px 24px;background:#f5f6f7;overflow-y:auto;}
.prog{height:5px;background:#eee;border-radius:3px;overflow:hidden;margin-top:6px;}
.pfill{height:100%;background:var(--ep-teal);border-radius:3px;}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block;}
.row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f0f0;font-size:13px;}
.row:last-child{border-bottom:none;}
.badge-cnt{background:#FBEAEA;color:#9B2C2C;border-radius:10px;padding:1px 7px;font-size:10px;margin-left:4px;}
.pay-page{background:#f1f3f5;min-height:calc(100vh - 58px);padding:24px 28px;}
</style>
</head>
<body>
@yield('content')
</body>
</html>
