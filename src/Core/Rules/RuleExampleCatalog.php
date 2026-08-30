<?php

namespace LaravelGuard\Core\Rules;

final class RuleExampleCatalog
{
    private const EXAMPLES = [
        'LG-CONFIG-001' => ['language' => 'dotenv', 'vulnerable' => "APP_ENV=production\nAPP_DEBUG=true", 'safer' => "APP_ENV=production\nAPP_DEBUG=false"],
        'LG-CONFIG-002' => ['language' => 'dotenv', 'vulnerable' => "SESSION_SECURE_COOKIE=false\nSESSION_HTTP_ONLY=false\nSESSION_SAME_SITE=none", 'safer' => "SESSION_SECURE_COOKIE=true\nSESSION_HTTP_ONLY=true\nSESSION_SAME_SITE=lax"],
        'LG-CONFIG-003' => ['language' => 'php', 'vulnerable' => "'allowed_origins' => ['*'],\n'supports_credentials' => true,", 'safer' => "'allowed_origins' => ['https://app.example.com'],\n'supports_credentials' => true,"],
        'LG-CONFIG-004' => ['language' => 'dotenv', 'vulnerable' => 'APP_KEY=', 'safer' => 'APP_KEY=base64:<generated-secret>'],
        'LG-CONFIG-005' => ['language' => 'php', 'vulnerable' => "'default' => env('FILESYSTEM_DISK', 'public'),", 'safer' => "'default' => env('FILESYSTEM_DISK', 'local'),"],
        'LG-CONFIG-006' => ['language' => 'dotenv', 'vulnerable' => "APP_ENV=production\nLOG_LEVEL=debug", 'safer' => "APP_ENV=production\nLOG_LEVEL=warning"],
        'LG-CONFIG-007' => ['language' => 'php', 'vulnerable' => "'mysql' => ['host' => env('DB_HOST')],", 'safer' => "'mysql' => ['options' => [PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA')]],"],
        'LG-CONFIG-008' => ['language' => 'dotenv', 'vulnerable' => "APP_ENV=production\nMAIL_MAILER=log", 'safer' => "APP_ENV=production\nMAIL_MAILER=smtp\nMAIL_ENCRYPTION=tls"],
        'LG-CONFIG-009' => ['language' => 'php', 'vulnerable' => "protected \$proxies = '*';", 'safer' => "protected \$proxies = ['10.0.0.10', '10.0.0.11'];"],

        'LG-ROUTE-001' => ['language' => 'php', 'vulnerable' => "Route::get('/account/export', ExportController::class);", 'safer' => "Route::get('/account/export', ExportController::class)->middleware('auth');"],
        'LG-ROUTE-002' => ['language' => 'php', 'vulnerable' => "Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('auth');", 'safer' => "Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware(['auth', 'can:delete,user']);"],
        'LG-ROUTE-003' => ['language' => 'php', 'vulnerable' => "Route::post('/password/reset', ResetPasswordController::class);", 'safer' => "Route::post('/password/reset', ResetPasswordController::class)->middleware('throttle:6,1');"],
        'LG-ROUTE-004' => ['language' => 'php', 'vulnerable' => "Route::get('/admin/users', [AdminController::class, 'index']);", 'safer' => "Route::get('/admin/users', [AdminController::class, 'index'])->middleware(['auth', 'can:viewAdmin']);"],
        'LG-ROUTE-005' => ['language' => 'php', 'vulnerable' => "Route::get('/users/{user}/delete', [UserController::class, 'destroy']);", 'safer' => "Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware(['auth', 'can:delete,user']);"],
        'LG-ROUTE-006' => ['language' => 'php', 'vulnerable' => "Route::get('/invitations/{invitation}/accept', AcceptInvitation::class);", 'safer' => "Route::get('/invitations/{invitation}/accept', AcceptInvitation::class)->middleware('signed');"],
        'LG-ROUTE-007' => ['language' => 'php', 'vulnerable' => "Gate::authorize('update', \$patient); // No PatientPolicy is discoverable", 'safer' => 'Gate::policy(Patient::class, PatientPolicy::class);'],

        'LG-UPLOAD-001' => ['language' => 'php', 'vulnerable' => "\$request->file('document')->store('documents');", 'safer' => "\$data = \$request->validate(['document' => ['required', 'file', 'mimes:pdf']]);\n\$data['document']->store('documents');"],
        'LG-UPLOAD-002' => ['language' => 'php', 'vulnerable' => "\$file->storeAs('documents', \$file->getClientOriginalName());", 'safer' => "\$file->storeAs('documents', (string) Str::uuid().'.'.\$file->extension());"],
        'LG-UPLOAD-003' => ['language' => 'php', 'vulnerable' => "'attachment' => ['file', 'mimes:php,phar,pdf'],", 'safer' => "'attachment' => ['file', 'mimes:pdf', 'max:10240'],"],
        'LG-UPLOAD-004' => ['language' => 'php', 'vulnerable' => "\$request->file('attachment')->store('uploads', 'public');", 'safer' => "\$request->file('attachment')->store('uploads', 'local');"],
        'LG-UPLOAD-005' => ['language' => 'php', 'vulnerable' => "'attachment' => ['required', 'file', 'mimes:pdf'],", 'safer' => "'attachment' => ['required', 'file', 'mimes:pdf', 'max:10240'],"],
        'LG-UPLOAD-006' => ['language' => 'php', 'vulnerable' => "\$file->store(\$request->string('folder'));", 'safer' => "\$file->store('tenant-documents/'.\$tenant->getKey());"],
        'LG-UPLOAD-007' => ['language' => 'php', 'vulnerable' => "'logo' => ['file', 'mimes:svg'],", 'safer' => "'logo' => ['file', 'mimes:png,webp', 'max:2048'], // or sanitize SVG with a maintained parser"],
        'LG-UPLOAD-008' => ['language' => 'php', 'vulnerable' => '// Declared image/png, detected application/x-php', 'safer' => 'LaravelGuard::inspectUpload($file); // reject MIME mismatch before storage'],
        'LG-UPLOAD-009' => ['language' => 'php', 'vulnerable' => '// image.jpg begins with an executable <?php signature', 'safer' => 'LaravelGuard::inspectUpload($file); // quarantine or reject executable content'],
        'LG-UPLOAD-010' => ['language' => 'php', 'vulnerable' => '// detected application/zip is not approved for this workflow', 'safer' => "'runtime.uploads.allowed_mimes' => ['application/pdf', 'image/png'],"],

        'LG-TENANT-001' => ['language' => 'php', 'vulnerable' => 'final class Patient extends Model {}', 'safer' => 'final class Patient extends Model { use BelongsToTenant; }'],
        'LG-TENANT-002' => ['language' => 'php', 'vulnerable' => 'Patient::findOrFail($patientId);', 'safer' => "Patient::where('tenant_id', \$tenant->getKey())->findOrFail(\$patientId);"],
        'LG-TENANT-003' => ['language' => 'php', 'vulnerable' => "'tenant.resolver' => null,", 'safer' => "'tenant.resolver' => App\\Tenancy\\AuthenticatedTenantResolver::class,"],
        'LG-TENANT-004' => ['language' => 'php', 'vulnerable' => "Patient::query()->update(['active' => false]);", 'safer' => "Patient::where('tenant_id', \$tenant->getKey())->update(['active' => false]);"],
        'LG-TENANT-005' => ['language' => 'php', 'vulnerable' => 'Appointment::query()->delete();', 'safer' => "Appointment::where('tenant_id', \$tenant->getKey())->delete();"],
        'LG-TENANT-006' => ['language' => 'php', 'vulnerable' => "DB::select('select * from patients where id = ?', [\$id]);", 'safer' => "DB::select('select * from patients where tenant_id = ? and id = ?', [\$tenantId, \$id]);"],

        'LG-QUERY-001' => ['language' => 'php', 'vulnerable' => "DB::select(\"select * from users where email = '\".\$email.\"'\");", 'safer' => "DB::select('select * from users where email = ?', [\$email]);"],
        'LG-QUERY-002' => ['language' => 'php', 'vulnerable' => "DB::statement(\$request->string('sql'));", 'safer' => "DB::statement('update users set active = ? where id = ?', [false, \$userId]);"],
        'LG-QUERY-003' => ['language' => 'php', 'vulnerable' => "User::query()->update(['active' => false]);", 'safer' => "User::whereKey(\$validatedIds)->update(['active' => false]);"],
        'LG-QUERY-004' => ['language' => 'php', 'vulnerable' => 'AuditLog::query()->delete();', 'safer' => "AuditLog::where('created_at', '<', \$cutoff)->limit(1000)->delete();"],

        'LG-MODEL-001' => ['language' => 'php', 'vulnerable' => 'User::create($request->all());', 'safer' => 'User::create($request->validated()); // FormRequest allows only intended fields'],
        'LG-MODEL-002' => ['language' => 'php', 'vulnerable' => 'return response()->json($user); // includes api_token', 'safer' => 'return new UserResource($user); // explicit public attributes only'],

        'LG-SECRET-001' => ['language' => 'php', 'vulnerable' => "\$apiKey = 'sk_live_real_secret';", 'safer' => "\$apiKey = config('services.provider.key'); // injected by a secret store"],
        'LG-SECRET-002' => ['language' => 'gitignore', 'vulnerable' => '# .env is committed to Git', 'safer' => ".env\n.env.*\n!.env.example"],

        'LG-API-001' => ['language' => 'php', 'vulnerable' => "Route::get('/api/patients', [PatientController::class, 'index']);", 'safer' => "Route::middleware('auth:sanctum')->get('/api/patients', [PatientController::class, 'index']);"],
        'LG-API-002' => ['language' => 'php', 'vulnerable' => "Route::post('/api/search', SearchController::class);", 'safer' => "Route::post('/api/search', SearchController::class)->middleware('throttle:api');"],
        'LG-API-003' => ['language' => 'php', 'vulnerable' => "return Patient::where('tenant_id', \$tenantId)->get();", 'safer' => "return PatientResource::collection(Patient::where('tenant_id', \$tenantId)->paginate());"],
    ];

    public static function for(string $ruleId): array
    {
        return self::EXAMPLES[strtoupper($ruleId)] ?? [
            'language' => 'text',
            'vulnerable' => 'Review the code path identified by the custom rule.',
            'safer' => 'Apply the custom rule recommendation and verify it with a focused regression test.',
        ];
    }
}
