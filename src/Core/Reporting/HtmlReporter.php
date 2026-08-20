<?php

namespace LaravelGuard\Core\Reporting;

use LaravelGuard\Core\Findings\FindingCollection;
use LaravelGuard\Core\Scoring\SecurityScore;

final class HtmlReporter
{
    public function render(FindingCollection $findings): string
    {
        $score = SecurityScore::fromFindings($findings);
        $items = '';
        foreach ($findings as $finding) {
            $location = $finding->location->file ? htmlspecialchars($finding->location->file).':'.($finding->location->line ?? 1) : 'Application configuration';
            $items .= '<article class="finding '.strtolower($finding->severity->name).'"><header><b>'.htmlspecialchars($finding->ruleId).'</b><span>'.$finding->severity->label().'</span></header><h2>'.htmlspecialchars($finding->title).'</h2><p>'.htmlspecialchars($finding->description).'</p><small>'.$location.'</small><h3>Recommendation</h3><p>'.htmlspecialchars($finding->recommendation).'</p></article>';
        }

        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Laravel Guard report</title><style>body{font:14px system-ui;margin:0;background:#f4f6f5;color:#182522}.wrap{max-width:1000px;margin:auto;padding:36px}.summary{display:flex;justify-content:space-between;align-items:end;border-bottom:2px solid #182522;padding-bottom:20px}.score{font-size:44px;font-weight:700}.finding{background:white;border:1px solid #dce3e0;border-left:5px solid #80918b;padding:20px;margin:16px 0}.finding.critical{border-left-color:#a52525}.finding.high{border-left-color:#d45d36}.finding.medium{border-left-color:#bf8a20}.finding.low{border-left-color:#3979a1}header{display:flex;justify-content:space-between}h2{font-size:18px}h3{font-size:12px;text-transform:uppercase;margin-bottom:4px}small{color:#64716d}</style></head><body><main class="wrap"><section class="summary"><div><h1>Laravel Guard</h1><p>A clean report does not replace professional security review.</p></div><div class="score">'.$score->score.'/100 '.$score->grade.'</div></section>'.$items.'</main></body></html>';
    }
}
