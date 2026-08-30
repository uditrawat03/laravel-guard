<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel Guard - {{ ucfirst($section) }}</title>
    <link rel="stylesheet" href="{{ route('laravel-guard.ui.asset', ['v' => $assetVersion]) }}">
</head>
<body>
@php
    $nav = ['overview' => 'Overview', 'findings' => 'Findings', 'scans' => 'Scan history', 'baselines' => 'Baselines', 'rules' => 'Rule catalog', 'runtime' => 'Runtime', 'doctor' => 'Doctor'];
    $descriptions = ['overview' => 'Current risk posture and scan evidence', 'findings' => 'Prioritized risks with concrete remediation', 'scans' => 'Durable history of security posture checks', 'baselines' => 'Reviewed and time-bound accepted findings', 'rules' => 'Checks active in this Laravel application', 'runtime' => 'Request-time protection and collected events', 'doctor' => 'Package configuration and operational readiness'];
    $counts = $latest['counts'] ?? ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
@endphp
<div class="guard-shell">
    <aside class="guard-sidebar">
        <a class="guard-brand" href="{{ route('laravel-guard.ui.overview') }}"><span>LG</span><strong>Laravel Guard</strong></a>
        <nav aria-label="Security dashboard">
            @foreach($nav as $key => $label)
                <a class="{{ $section === $key ? 'active' : '' }}" href="{{ $key === 'overview' ? route('laravel-guard.ui.overview') : route('laravel-guard.ui.section', ['section' => $key]) }}">{{ $label }}</a>
            @endforeach
        </nav>
        <p class="guard-version">Package {{ $packageVersion }}</p>
    </aside>
    <main>
        <header class="guard-header">
            <div><p class="guard-kicker">Application security</p><h1>{{ $nav[$section] }}</h1><p class="guard-subtitle">{{ $descriptions[$section] }}</p></div>
            @if($allowScan)
                <form method="post" action="{{ route('laravel-guard.ui.scan') }}">@csrf<button type="submit">Run security scan</button></form>
            @endif
        </header>
        @if(session('laravel-guard-status'))<div class="guard-notice">{{ session('laravel-guard-status') }}</div>@endif

        @if($section === 'overview')
            @if($latest)
                <section class="guard-score-row">
                    <div class="guard-score"><span>Security score</span><strong>{{ $latest['score']['value'] }}</strong><b>Grade {{ $latest['score']['grade'] }}</b></div>
                    @foreach($counts as $severity => $count)<div class="guard-metric"><span class="guard-dot {{ $severity }}"></span><small>{{ ucfirst($severity) }}</small><strong>{{ $count }}</strong></div>@endforeach
                </section>
                <section class="guard-panel"><div class="guard-panel-head"><h2>Latest scan</h2><a href="{{ route('laravel-guard.ui.section', ['section' => 'findings']) }}">View findings</a></div>
                    <dl class="guard-details"><div><dt>Completed</dt><dd>{{ $latest['generated_at'] }}</dd></div><div><dt>Duration</dt><dd>{{ $latest['duration_ms'] }} ms</dd></div><div><dt>Peak memory</dt><dd>{{ $latest['peak_memory_mb'] }} MB</dd></div><div><dt>Trigger</dt><dd>{{ $latest['trigger'] }}</dd></div></dl>
                </section>
                <section class="guard-panel"><div class="guard-panel-head"><h2>Category posture</h2></div>
                    <div class="guard-category-grid">@forelse($latest['score']['categories'] as $category => $score)<div><span>{{ ucfirst($category) }}</span><strong>{{ $score['score'] }}</strong><small>Grade {{ $score['grade'] }}</small></div>@empty<p class="guard-empty">No affected categories in the latest scan.</p>@endforelse</div>
                </section>
            @else
                <section class="guard-empty-state"><h2>No scan evidence yet</h2><p>Run a scan from the command line or enable the dashboard scan action.</p><code>php artisan guard:scan</code></section>
            @endif
        @elseif($section === 'findings')
            <section class="guard-panel">
                <form class="guard-filters" method="get"><select name="severity"><option value="">All severities</option>@foreach(['critical','high','medium','low'] as $value)<option value="{{ $value }}" @selected(request('severity') === $value)>{{ ucfirst($value) }}</option>@endforeach</select><input name="category" value="{{ request('category') }}" placeholder="Category"><button type="submit">Filter</button></form>
                <div class="guard-list">@forelse($findings as $finding)<article class="guard-finding"><div class="guard-finding-head"><span class="guard-badge {{ $finding['severity'] }}">{{ $finding['severity'] }}</span><code>{{ $finding['rule_id'] }}</code></div><h2>{{ $finding['title'] }}</h2><p>{{ $finding['description'] }}</p><dl><div><dt>Risk</dt><dd>{{ $finding['risk'] }}</dd></div><div><dt>Recommended action</dt><dd>{{ $finding['recommendation'] }}</dd></div>@if($finding['file'])<div><dt>Location</dt><dd><code>{{ $finding['file'] }}{{ $finding['line'] ? ':'.$finding['line'] : '' }}</code></dd></div>@endif</dl>@if(!empty($finding['documentation_url']))<a class="guard-guidance" href="{{ $finding['documentation_url'] }}" target="_blank" rel="noopener noreferrer">Rule guidance</a>@endif</article>@empty<p class="guard-empty">No findings match this view.</p>@endforelse</div>
                @include('laravel-guard::ui.partials.pagination', ['paginator' => $findings])
            </section>
        @elseif($section === 'scans')
            <section class="guard-panel guard-table-panel"><div class="guard-table-meta"><strong>{{ $scans->total() }} scan runs</strong><span>Newest first</span></div><div class="guard-table-wrap"><table><thead><tr><th>Run</th><th>Score</th><th>Findings</th><th>Duration</th><th>Trigger</th></tr></thead><tbody>@forelse($scans as $scan)<tr><td>{{ $scan['generated_at'] }}</td><td><strong>{{ $scan['score']['value'] }}</strong> / {{ $scan['score']['grade'] }}</td><td>{{ array_sum($scan['counts']) }}</td><td>{{ $scan['duration_ms'] }} ms</td><td>{{ $scan['trigger'] }}</td></tr>@empty<tr><td colspan="5" class="guard-empty">No saved scan runs.</td></tr>@endforelse</tbody></table></div>@include('laravel-guard::ui.partials.pagination', ['paginator' => $scans])</section>
        @elseif($section === 'baselines')
            <section class="guard-summary"><div><span>Status</span><strong>{{ $baseline['exists'] ? 'Available' : 'Not created' }}</strong></div><div><span>Accepted findings</span><strong>{{ count($baseline['entries']) }}</strong></div><div><span>Expired</span><strong>{{ $baseline['expired'] }}</strong></div></section>
            <section class="guard-panel"><div class="guard-panel-head"><h2>Baseline entries</h2><code>{{ $baseline['path'] }}</code></div>@if(isset($baseline['error']))<p class="guard-error">{{ $baseline['error'] }}</p>@endif<div class="guard-list">@forelse($baseline['entries'] as $entry)<article class="guard-row"><span class="guard-badge {{ $entry['severity'] }}">{{ $entry['severity'] }}</span><div><strong>{{ $entry['title'] }}</strong><code>{{ $entry['rule_id'] }}</code><p>{{ $entry['acceptance']['reason'] ?? 'No acceptance reason recorded.' }}</p></div></article>@empty<p class="guard-empty">No baseline entries.</p>@endforelse</div></section>
        @elseif($section === 'rules')
            <section class="guard-panel guard-rule-panel">
                <div class="guard-table-meta"><strong>{{ $rules->total() }} active rules</strong><span>Open a rule for impact, remediation, and code examples</span></div>
                <div class="guard-rule-list">
                    @foreach($rules as $rule)
                        <article class="guard-rule">
                            <div class="guard-rule-row">
                                <div class="guard-rule-identity"><strong>{{ $rule['name'] }}</strong><code>{{ $rule['id'] }}</code></div>
                                <span class="guard-category">{{ $rule['category'] }}</span>
                                <span class="guard-badge {{ $rule['severity'] }}">{{ $rule['severity'] }}</span>
                                <p>{{ $rule['description'] }}</p>
                                <details>
                                    <summary>View guidance</summary>
                                    <div class="guard-rule-guidance">
                                        <dl>
                                            <div><dt>Why it matters</dt><dd>{{ $rule['why_it_matters'] }}</dd></div>
                                            <div><dt>Recommended response</dt><dd>{{ $rule['how_to_respond'] }}</dd></div>
                                            <div><dt>Analysis limits</dt><dd>{{ $rule['analysis_limits'] }}</dd></div>
                                        </dl>
                                        <div class="guard-code-grid">
                                            <div><span>Potentially vulnerable</span><pre><code>{{ $rule['example']['vulnerable'] }}</code></pre></div>
                                            <div><span>Safer pattern</span><pre><code>{{ $rule['example']['safer'] }}</code></pre></div>
                                        </div>
                                        <a class="guard-guidance" href="{{ $rule['documentation_url'] }}" target="_blank" rel="noopener noreferrer">Full rule documentation</a>
                                    </div>
                                </details>
                            </div>
                        </article>
                    @endforeach
                </div>
                @include('laravel-guard::ui.partials.pagination', ['paginator' => $rules])
            </section>
        @elseif($section === 'runtime')
            <section class="guard-summary"><div><span>Runtime guard</span><strong>{{ $runtime['enabled'] ? 'Enabled' : 'Disabled' }}</strong></div><div><span>Current request events</span><strong>{{ count($runtime['events']) }}</strong></div><div><span>Environments</span><strong>{{ implode(', ', $runtime['environments']) ?: 'None' }}</strong></div></section>
            <section class="guard-panel"><div class="guard-list">@forelse($runtime['events'] as $event)<article class="guard-row"><code>{{ $event['rule_id'] }}</code><div><strong>{{ $event['message'] }}</strong><p>{{ $event['file'] }}{{ $event['line'] ? ':'.$event['line'] : '' }} | {{ $event['created_at'] }}</p></div></article>@empty<p class="guard-empty">No runtime events were collected during this request.</p>@endforelse</div></section>
        @elseif($section === 'doctor')
            @php
                $doctorCounts = ['pass' => 0, 'warning' => 0, 'error' => 0];
                foreach ($diagnostics as $diagnostic) {
                    $doctorCounts[$diagnostic['status']] = ($doctorCounts[$diagnostic['status']] ?? 0) + 1;
                }
            @endphp
            <section class="guard-doctor-summary">
                <div class="pass"><span>Healthy checks</span><strong>{{ $doctorCounts['pass'] }}</strong></div>
                <div class="warning"><span>Needs attention</span><strong>{{ $doctorCounts['warning'] }}</strong></div>
                <div class="error"><span>Configuration errors</span><strong>{{ $doctorCounts['error'] }}</strong></div>
            </section>
            <section class="guard-panel guard-diagnostics-panel">
                <div class="guard-table-meta"><strong>{{ count($diagnostics) }} configuration checks</strong><span>Live package diagnostics</span></div>
                <div class="guard-diagnostics">
                    @foreach($diagnostics as $diagnostic)
                        <article class="guard-diagnostic">
                            <div class="guard-diagnostic-status {{ $diagnostic['status'] }}"><span></span>{{ $diagnostic['status'] }}</div>
                            <strong class="guard-diagnostic-check">{{ $diagnostic['check'] }}</strong>
                            <div class="guard-diagnostic-detail"><p>{{ $diagnostic['message'] }}</p>@if(isset($diagnostic['remediation']))<small>{{ $diagnostic['remediation'] }}</small>@endif</div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</div>
</body>
</html>
