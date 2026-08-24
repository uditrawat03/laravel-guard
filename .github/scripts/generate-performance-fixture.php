<?php

declare(strict_types=1);

$directory = $argv[1] ?? null;
$files = filter_var($argv[2] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5000]]);

if (! is_string($directory) || $directory === '' || $files === false) {
    fwrite(STDERR, "Usage: php generate-performance-fixture.php <directory> <files:1-5000>\n");
    exit(2);
}

if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
    fwrite(STDERR, "Unable to create fixture directory [{$directory}].\n");
    exit(1);
}

$template = <<<'PHP'
<?php

namespace App\PerformanceFixture;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class Patient%1$d extends Model
{
    protected $table = 'patients';

    protected $fillable = ['tenant_id', 'name', 'email'];

    protected $hidden = ['password', 'remember_token'];

    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function search(Request $request): mixed
    {
        return DB::table('patients')
            ->where('tenant_id', (string) $request->user()->tenant_id)
            ->where('name', 'like', '%%'.(string) $request->string('search').'%%')
            ->limit(50)
            ->get();
    }
}
PHP;

for ($index = 1; $index <= $files; $index++) {
    $path = $directory.DIRECTORY_SEPARATOR.sprintf('Patient%04d.php', $index);
    if (file_put_contents($path, sprintf($template, $index)) === false) {
        fwrite(STDERR, "Unable to write fixture [{$path}].\n");
        exit(1);
    }
}

fwrite(STDOUT, "Generated {$files} Laravel source files in {$directory}.\n");
