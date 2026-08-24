<?php

namespace LaravelGuard\Ui\Http\Controllers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use LaravelGuard\Ui\SecurityDashboard;

final class DashboardController extends Controller
{
    public function __construct(private readonly SecurityDashboard $dashboard, private readonly Repository $config) {}

    public function show(Request $request, string $section = 'overview'): View
    {
        abort_unless(in_array($section, ['overview', 'findings', 'scans', 'baselines', 'rules', 'runtime', 'doctor'], true), 404);
        $latest = $this->dashboard->latest();

        return view('laravel-guard::ui.dashboard', [
            'section' => $section,
            'latest' => $latest,
            'findings' => $this->paginate($this->filterFindings($latest['findings'] ?? [], $request), $request, 'findings'),
            'scans' => $this->paginate($this->dashboard->history(), $request, 'scans'),
            'baseline' => $this->dashboard->baseline(),
            'rules' => $this->paginate($this->dashboard->rules(), $request, 'rules'),
            'runtime' => $this->dashboard->runtime(),
            'diagnostics' => $this->dashboard->diagnostics(),
            'allowScan' => (bool) $this->config->get('laravel-guard.ui.allow_scan', false),
            'assetVersion' => substr(sha1_file($this->assetPath()), 0, 12),
        ]);
    }

    public function scan(): RedirectResponse
    {
        abort_unless((bool) $this->config->get('laravel-guard.ui.allow_scan', false), 403);
        $this->dashboard->run('web');

        return redirect()->route('laravel-guard.ui.section', ['section' => 'overview'])->with('laravel-guard-status', 'Security scan completed.');
    }

    public function asset(): Response
    {
        return response((string) file_get_contents($this->assetPath()), 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function assetPath(): string
    {
        return __DIR__.'/../../../../resources/ui/app.css';
    }

    private function filterFindings(array $findings, Request $request): array
    {
        $severity = $request->string('severity')->toString();
        $category = $request->string('category')->toString();

        return array_values(array_filter($findings, fn (array $finding) => ($severity === '' || ($finding['severity'] ?? '') === $severity)
            && ($category === '' || ($finding['category'] ?? '') === $category)
        ));
    }

    private function paginate(array $items, Request $request, string $pageName): LengthAwarePaginator
    {
        $perPage = min(100, max(10, (int) $this->config->get('laravel-guard.ui.per_page', 25)));
        $page = max(1, (int) $request->query($pageName.'_page', 1));

        return new LengthAwarePaginator(
            array_slice($items, ($page - 1) * $perPage, $perPage),
            count($items),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query(), 'pageName' => $pageName.'_page'],
        );
    }
}
