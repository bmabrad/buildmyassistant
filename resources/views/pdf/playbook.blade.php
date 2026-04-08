<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

  :root {
    --deep-slate: #1E2A38;
    --mid-blue: #3D5A73;
    --sage: #7AA08A;
    --soft-sage: #C8D8CC;
    --off-white: #F4F6F4;
    --white: #FFFFFF;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Inter', sans-serif;
    color: var(--deep-slate);
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* ── Page container (A4) ── */
  .page {
    width: 210mm;
    min-height: 297mm;
    overflow: hidden;
    position: relative;
    page-break-after: always;
  }

  .page:last-child {
    page-break-after: avoid;
  }

  /* ── COVER PAGE ── */
  .cover {
    min-height: 297mm;
    display: flex;
    flex-direction: column;
    background: var(--deep-slate);
    color: var(--white);
    position: relative;
    overflow: hidden;
  }

  .cover-top {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60mm 25mm 20mm 25mm;
    position: relative;
    z-index: 1;
  }

  .cover::before {
    content: '';
    position: absolute;
    top: -80mm;
    right: -40mm;
    width: 200mm;
    height: 200mm;
    border-radius: 50%;
    background: rgba(122, 160, 138, 0.08);
  }

  .cover::after {
    content: '';
    position: absolute;
    bottom: -60mm;
    left: -60mm;
    width: 160mm;
    height: 160mm;
    border-radius: 50%;
    background: rgba(122, 160, 138, 0.05);
  }

  .cover-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--sage);
    margin-bottom: 16px;
  }

  .cover-title {
    font-size: 42px;
    font-weight: 600;
    line-height: 1.15;
    margin-bottom: 12px;
    color: var(--white);
  }

  .cover-title .accent { color: var(--sage); }

  .cover-assistant-desc {
    font-size: 22px;
    font-weight: 500;
    color: var(--soft-sage);
    margin-bottom: 40px;
  }

  .cover-divider {
    width: 60px;
    height: 3px;
    background: var(--sage);
    margin-bottom: 40px;
  }

  .cover-meta {
    font-size: 15px;
    font-weight: 400;
    color: var(--mid-blue);
    line-height: 1.8;
  }

  .cover-meta strong {
    color: var(--soft-sage);
    font-weight: 500;
  }

  .cover-bottom {
    padding: 12mm 25mm;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid rgba(122, 160, 138, 0.2);
    position: relative;
    z-index: 1;
  }

  .cover-logo {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .cover-logo img {
    height: 24px;
  }

  .cover-logo-text {
    font-size: 13px;
    font-weight: 500;
    color: var(--soft-sage);
  }

  .cover-logo-text .co { color: var(--sage); }

  .cover-url {
    font-size: 12px;
    color: rgba(200, 216, 204, 0.5);
  }

  /* ── CONTENT PAGES ── */
  .content-page {
    padding: 20mm 25mm 25mm 25mm;
    min-height: 297mm;
    position: relative;
  }

  .page-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 8px;
    background: linear-gradient(90deg, var(--deep-slate) 0%, var(--sage) 100%);
    z-index: 10;
  }

  .page-footer {
    position: absolute;
    bottom: 12mm;
    left: 25mm;
    right: 25mm;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 10px;
    border-top: 1px solid var(--soft-sage);
    font-size: 10px;
    color: var(--mid-blue);
  }

  .page-footer .logo-small {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .page-footer .logo-bars-small {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .page-footer .logo-bars-small span {
    display: block;
    height: 2px;
    background: var(--sage);
    border-radius: 1px;
  }

  .page-footer .logo-bars-small span:nth-child(1) { width: 12px; }
  .page-footer .logo-bars-small span:nth-child(2) { width: 9px; opacity: 0.7; }
  .page-footer .logo-bars-small span:nth-child(3) { width: 6px; opacity: 0.4; }

  .page-footer .co { color: var(--sage); }

  /* ── SECTION HEADERS ── */
  .section-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--deep-slate);
    color: var(--sage);
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
  }

  .section-number-appendix {
    background: var(--mid-blue);
    color: var(--white);
  }

  .section-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--deep-slate);
    margin-bottom: 6px;
    line-height: 1.3;
  }

  .section-subtitle {
    font-size: 14px;
    font-weight: 400;
    color: var(--mid-blue);
    margin-bottom: 24px;
  }

  .section-divider {
    width: 40px;
    height: 3px;
    background: var(--sage);
    margin-bottom: 24px;
  }

  /* ── Page-break control: keep blocks together ── */
  .stat-row,
  .feature-grid,
  .setup-steps,
  .rules-list,
  .timeline-block,
  .test-task-box,
  .highlight-box {
    break-inside: avoid;
    break-before: auto;
  }

  /* When a block breaks to a new page, tighten the top margin */
  .section-content {
    break-before: auto;
  }

  /* ── STAT CARDS ── */
  .stat-row {
    display: flex;
    gap: 16px;
    margin: 20px 0;
  }

  .stat-card {
    flex: 1;
    background: var(--deep-slate);
    border-radius: 10px;
    padding: 20px;
    text-align: center;
  }

  .stat-value {
    font-size: 28px;
    font-weight: 600;
    color: var(--sage);
    margin-bottom: 4px;
  }

  .stat-label {
    font-size: 12px;
    font-weight: 400;
    color: var(--soft-sage);
  }

  /* ── PROCESS STEPS (timeline) ── */
  .process-steps {
    margin: 8px 0 16px 0;
  }

  .process-step {
    display: flex;
    gap: 16px;
    position: relative;
  }

  .process-step-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 32px;
    flex-shrink: 0;
  }

  .process-step-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--deep-slate);
    color: var(--sage);
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .process-step-line {
    width: 2px;
    flex: 1;
    background: var(--soft-sage);
    margin: 4px 0;
  }

  .process-step-content {
    flex: 1;
    padding-bottom: 20px;
  }

  .process-step-title {
    font-size: 15px;
    font-weight: 500;
    color: var(--deep-slate);
    margin-bottom: 4px;
  }

  .process-step-desc {
    font-size: 13px;
    font-weight: 400;
    color: var(--mid-blue);
    line-height: 1.6;
  }

  .process-step-tag {
    display: inline-block;
    font-size: 10px;
    font-weight: 500;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    padding: 3px 8px;
    border-radius: 4px;
    margin-top: 6px;
  }

  .tag-automatic {
    background: rgba(122, 160, 138, 0.15);
    color: var(--sage);
  }

  .tag-manual {
    background: rgba(61, 90, 115, 0.1);
    color: var(--mid-blue);
  }

  .tag-learnable {
    background: rgba(30, 42, 56, 0.08);
    color: var(--deep-slate);
  }

  /* ── SETUP STEPS ── */
  .setup-steps {
    margin: 8px 0 16px 0;
  }

  .setup-step {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    margin-bottom: 16px;
  }

  .setup-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--sage);
    color: var(--white);
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .setup-step-text {
    font-size: 14px;
    font-weight: 500;
    color: var(--deep-slate);
    line-height: 1.6;
    padding-top: 3px;
  }

  .setup-step-hint {
    display: block;
    font-size: 12px;
    font-weight: 400;
    color: var(--mid-blue);
    margin-top: 4px;
  }

  /* ── FEATURE GRID ── */
  .feature-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin: 20px 0;
  }

  .feature-card {
    background: var(--off-white);
    border-radius: 10px;
    padding: 20px;
  }

  .feature-card-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: var(--deep-slate);
    color: var(--sage);
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
  }

  .feature-card-title {
    font-size: 14px;
    font-weight: 500;
    color: var(--deep-slate);
    margin-bottom: 6px;
  }

  .feature-card-desc {
    font-size: 12px;
    font-weight: 400;
    color: var(--mid-blue);
    line-height: 1.6;
  }

  /* ── BODY TEXT ── */
  .section-content p {
    font-size: 14px;
    font-weight: 400;
    color: var(--mid-blue);
    line-height: 1.75;
    margin-bottom: 16px;
  }

  .section-content strong {
    color: var(--deep-slate);
    font-weight: 500;
  }

  .section-content em {
    color: var(--mid-blue);
  }

  /* ── LISTS ── */
  .section-content ol,
  .section-content ul {
    margin: 8px 0 16px 0;
    padding-left: 24px;
  }

  .section-content li {
    margin: 6px 0;
    line-height: 1.65;
    font-size: 14px;
    color: var(--mid-blue);
  }

  .section-content li strong {
    color: var(--deep-slate);
  }

  /* ── RULES LIST ── */
  .rules-list {
    margin: 20px 0;
  }

  .rule-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid var(--off-white);
  }

  .rule-item:last-child { border-bottom: none; }

  .rule-icon {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    margin-top: 1px;
  }

  .rule-always {
    background: rgba(122, 160, 138, 0.15);
    color: var(--sage);
  }

  .rule-never {
    background: rgba(30, 42, 56, 0.08);
    color: var(--mid-blue);
  }

  .rule-text {
    font-size: 13px;
    color: var(--mid-blue);
    line-height: 1.6;
  }

  /* ── TIMELINE BLOCK ── */
  .timeline-block {
    background: var(--deep-slate);
    border-radius: 12px;
    padding: 28px;
    margin: 20px 0;
    color: var(--white);
  }

  .timeline-block-title {
    font-size: 14px;
    font-weight: 500;
    color: var(--sage);
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  .timeline-item {
    display: flex;
    gap: 14px;
    margin-bottom: 16px;
  }

  .timeline-item:last-child { margin-bottom: 0; }

  .timeline-marker {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--sage);
    flex-shrink: 0;
    margin-top: 6px;
  }

  .timeline-text {
    font-size: 13px;
    color: var(--soft-sage);
    line-height: 1.6;
  }

  .timeline-text strong {
    color: var(--white);
    font-weight: 500;
  }

  /* ── TEST TASK BOX ── */
  .test-task-box {
    background: linear-gradient(135deg, var(--deep-slate) 0%, #2a3d4f 100%);
    border-radius: 12px;
    padding: 28px;
    margin: 24px 0;
    position: relative;
    overflow: hidden;
  }

  .test-task-box::before {
    content: '';
    position: absolute;
    top: -20px;
    right: -20px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: rgba(122, 160, 138, 0.1);
  }

  .test-task-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--sage);
    margin-bottom: 10px;
  }

  .test-task-text {
    font-size: 14px;
    color: var(--soft-sage);
    line-height: 1.7;
    position: relative;
    z-index: 1;
  }

  /* ── HIGHLIGHT BOX ── */
  .highlight-box {
    background: var(--off-white);
    border-left: 4px solid var(--sage);
    border-radius: 0 8px 8px 0;
    padding: 20px 24px;
    margin: 20px 0;
  }

  .highlight-box-label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--sage);
    margin-bottom: 8px;
  }

  .highlight-box-value {
    font-size: 15px;
    font-weight: 400;
    color: var(--deep-slate);
    line-height: 1.6;
  }

  /* ── CODE BLOCK ── */
  .code-block {
    background: #1a1e24;
    border-radius: 10px;
    padding: 24px;
    margin: 16px 0;
    overflow-x: auto;
  }

  .code-block pre {
    font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
    font-size: 12px;
    line-height: 1.7;
    color: #c8d8cc;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin: 0;
  }

  /* ── APPENDIX CODE (flows across pages) ── */
  .appendix-code {
    background: #1a1e24;
    border-radius: 10px;
    padding: 24px;
    margin: 16px 0;
  }

  .appendix-code pre {
    font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
    font-size: 11px;
    line-height: 1.7;
    color: #c8d8cc;
    white-space: pre-wrap;
    word-wrap: break-word;
    margin: 0;
    padding: 20px 0;
    -webkit-box-decoration-break: clone;
    box-decoration-break: clone;
  }

  /* ── APPENDIX ── */
  .appendix-header {
    background: var(--off-white);
    border-radius: 10px;
    padding: 20px 24px;
    margin-bottom: 24px;
  }

  .appendix-header .label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--sage);
    margin-bottom: 6px;
  }

  .appendix-header .desc {
    font-size: 13px;
    color: var(--mid-blue);
    line-height: 1.6;
  }

  /* ── Print styles ── */
  @media print {
    body { background: white; }
    .page { box-shadow: none; margin: 0; }
  }
</style>
</head>
<body>

{{-- ═══════════════ COVER PAGE ═══════════════ --}}
<div class="page cover">
  <div class="cover-top">
    <div class="cover-label">Your AI Assistant Playbook</div>
    <h1 class="cover-title">
      @if ($assistantName)
        Meet <span class="accent">{{ $assistantName }}</span>
      @else
        Your AI Assistant <span class="accent">Playbook</span>
      @endif
    </h1>
    @if ($assistantDescription)
      <div class="cover-assistant-desc">{{ $assistantDescription }}</div>
    @endif
    <div class="cover-divider"></div>
    <div class="cover-meta">
      <strong>Built for</strong> {{ $buyerName }}<br>
      <strong>Date</strong> {{ $sessionDate }}
    </div>
  </div>
  <div class="cover-bottom">
    <div class="cover-logo">
      @if ($logoBase64)
        <img src="{{ $logoBase64 }}" alt="Build My Assistant">
      @else
        <div class="cover-logo-text">Build My Assistant<span class="co">.co</span></div>
      @endif
    </div>
    <div class="cover-url">buildmyassistant.co</div>
  </div>
</div>

{{-- ═══════════════ CONTENT PAGES (SECTIONS) ═══════════════ --}}
@foreach ($sections as $index => $section)
<div class="page content-page">
  <div class="page-header"></div>

  <div class="section-number">{{ $index + 1 }}</div>
  <h2 class="section-title">{{ $section['title'] }}</h2>
  @if (!empty($section['subtitle']))
    <div class="section-subtitle">{{ $section['subtitle'] }}</div>
  @endif
  <div class="section-divider"></div>

  <div class="section-content">
    {!! $section['html'] !!}
  </div>

  <div class="page-footer">
    <div class="logo-small">
      <div class="logo-bars-small"><span></span><span></span><span></span></div>
      <span>Build My Assistant<span class="co">.co</span></span>
    </div>
    <span>{{ $footerText }}</span>
  </div>
</div>
@endforeach

{{-- ═══════════════ APPENDIX ═══════════════ --}}
@if ($appendixHtml)
<div class="page content-page" style="page-break-before: always;">
  <div class="page-header"></div>

  <div class="section-number section-number-appendix">A</div>
  <h2 class="section-title">Appendix: Assistant Instructions</h2>
  <div class="section-subtitle">The markdown file your assistant uses - included here for reference</div>
  <div class="section-divider"></div>

  <div class="appendix-header">
    <div class="label">About this file</div>
    <div class="desc">This is a copy of the instructions your assistant works from. The primary version is the .md file you downloaded separately. You can update it anytime as your assistant learns and your preferences evolve.</div>
  </div>

  <div class="appendix-code">
    <pre>{!! $appendixHtml !!}</pre>
  </div>

  <div class="page-footer">
    <div class="logo-small">
      <div class="logo-bars-small"><span></span><span></span><span></span></div>
      <span>Build My Assistant<span class="co">.co</span></span>
    </div>
    <span>{{ $footerText }}</span>
  </div>
</div>
@endif

</body>
</html>
