<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Admin\Http\Middleware\EnsureAdminTwoFactor;
use App\Modules\Identity\Domain\RoleCode;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Captures every panel as static HTML so the current state of the product is browsable
 * after each phase, without a server.
 *
 * The capture happens *inside* the application (the HTTP kernel renders each route as an
 * authenticated user) rather than by driving a browser: it needs no Playwright download,
 * it is deterministic, and the HTML it writes is the same markup a visitor would receive.
 * The output is published to GitHub Pages by .github/workflows/preview.yml.
 */
final class CapturePreviewCommand extends Command
{
    protected $signature = 'preview:capture
                            {--out=storage/app/preview : output directory, relative to the project root}
                            {--label= : phase/milestone label shown in the gallery}';

    protected $description = 'Render every panel to static HTML and build the preview gallery';

    /** @var array<int, array{role: string, title: string, email: string, paths: array<int, array{label: string, uri: string, verified2fa?: bool}>}> */
    private const PANELS = [
        [
            'role' => 'guest',
            'title' => 'ورود',
            'email' => '',
            'paths' => [
                ['label' => 'صفحهٔ ورود', 'uri' => '/login'],
                ['label' => 'ثبت‌نام با موبایل', 'uri' => '/register'],
                ['label' => 'بازیابی گذرواژه با پیامک', 'uri' => '/password/forgot'],
            ],
        ],
        [
            'role' => 'consumer',
            'title' => 'پنل مصرف‌کننده',
            'email' => 'consumer@dekolife.test',
            'paths' => [
                ['label' => 'پنل من', 'uri' => '/account'],
                ['label' => 'انتخاب سطح', 'uri' => '/panel'],
                ['label' => 'ویرایش پروفایل', 'uri' => '/account/profile'],
                ['label' => 'درخواست فروشگاه/تأمین‌کننده', 'uri' => '/merchant/profiles'],
                ['label' => 'فرم پروفایل فروشگاه', 'uri' => '/merchant/profiles/new?type=store'],
            ],
        ],
        [
            'role' => 'store',
            'title' => 'پنل فروشگاه',
            'email' => 'store@dekolife.test',
            'paths' => [
                ['label' => 'داشبورد فروشگاه', 'uri' => '/store'],
            ],
        ],
        [
            'role' => 'supplier',
            'title' => 'پنل تأمین‌کننده',
            'email' => 'supplier@dekolife.test',
            'paths' => [
                ['label' => 'داشبورد تأمین‌کننده', 'uri' => '/supplier'],
            ],
        ],
        [
            'role' => 'marketer',
            'title' => 'پنل بازاریاب',
            'email' => 'marketer@dekolife.test',
            'paths' => [
                ['label' => 'داشبورد بازاریاب', 'uri' => '/marketer'],
            ],
        ],
        [
            'role' => 'admin',
            'title' => 'پنل مدیریت',
            'email' => 'admin@dekolife.test',
            'paths' => [
                ['label' => 'چالش دومرحله‌ای', 'uri' => '/admin/2fa'],
                ['label' => 'داشبورد مدیریت', 'uri' => '/admin', 'verified2fa' => true],
                ['label' => 'بررسی پروفایل فروشگاه/تأمین‌کننده', 'uri' => '/admin/merchants', 'verified2fa' => true],
                ['label' => 'تغییر اجباری گذرواژه', 'uri' => '/password/change', 'mustChange' => true],
            ],
        ],
    ];

    public function handle(Kernel $kernel): int
    {
        $out = $this->resolveOutputPath();
        $this->prepareDirectory($out);

        // Render with an in-memory session: no session rows, no cookies, no side effects.
        config(['session.driver' => 'array']);

        $captured = [];

        foreach (self::PANELS as $panel) {
            $user = $panel['email'] === '' ? null : User::query()->where('email', $panel['email'])->first();

            if ($panel['email'] !== '' && $user === null) {
                throw new RuntimeException("Preview needs the seeded account [{$panel['email']}]; run `php artisan db:seed` first.");
            }

            foreach ($panel['paths'] as $page) {
                $file = $panel['role'].'/'.Str::slug(str_replace('/', '-', trim($page['uri'], '/'))).'.html';

                if (! is_dir($out.'/'.$panel['role'])) {
                    mkdir($out.'/'.$panel['role'], 0775, true);
                }

                $html = $this->render(
                    $kernel,
                    $user,
                    $page['uri'],
                    (bool) ($page['verified2fa'] ?? false),
                    (bool) ($page['mustChange'] ?? false),
                );

                file_put_contents($out.'/'.$file, $html);

                $captured[] = [
                    'role' => $panel['role'],
                    'panel' => $panel['title'],
                    'label' => $page['label'],
                    'uri' => $page['uri'],
                    'file' => $file,
                    'status' => $this->lastStatus,
                ];

                $this->line(sprintf('  %-9s %-22s %s', $panel['role'], $page['uri'], $this->lastStatus));
            }
        }

        file_put_contents($out.'/index.html', $this->gallery($captured));
        file_put_contents($out.'/.nojekyll', '');

        $this->info(sprintf('Captured %d pages into %s', count($captured), $out));

        return self::SUCCESS;
    }

    private int $lastStatus = 0;

    private function render(Kernel $kernel, ?User $user, string $uri, bool $verified2fa, bool $mustChange = false): string
    {
        Auth::guard('web')->forgetUser();
        Auth::guard('admin')->forgetUser();

        if ($user instanceof User) {
            // Bind the user to the guard directly instead of Auth::login(): the capture runs
            // in the console, and a session-based login would be re-resolved by StartSession
            // when the request is handled, producing a 302 back to /login.
            Auth::guard('web')->setUser($user);

            // Seeded demo accounts carry the first-login flag on purpose (پیوست ۶ بند ۸).
            // The capture clears it *in memory* so the panel itself is visible; the gate is
            // captured separately on /password/change.
            $user->forceFill(['must_change_password' => $mustChange]);

            $user->switchActiveRole($user->activeRoleCode() ?? RoleCode::Consumer);
        }

        if ($verified2fa) {
            session([EnsureAdminTwoFactor::SESSION_KEY => true]);
        }

        $request = Request::create($uri, 'GET');
        $request->setLaravelSession(app('session.store'));
        $request->setUserResolver(static fn () => $user);

        $response = $kernel->handle($request);

        // A redirect is a real answer (/panel sends the visitor to their active panel), so
        // follow one hop and capture the destination instead of storing an empty body.
        if ($response->isRedirection()) {
            $target = parse_url((string) $response->headers->get('Location'), PHP_URL_PATH) ?: null;

            if ($target !== null && $target !== '/' && $target !== $uri) {
                return $this->render($kernel, $user, $target, $verified2fa, $mustChange);
            }
        }

        $this->lastStatus = $response->getStatusCode();

        return (string) $response->getContent();
    }

    /**
     * @param  array<int, array{role: string, panel: string, label: string, uri: string, file: string, status: int}>  $pages
     */
    private function gallery(array $pages): string
    {
        $label = $this->option('label') ?: 'فاز جاری';
        $commit = trim((string) @shell_exec('git rev-parse --short HEAD 2>/dev/null')) ?: 'unknown';
        $commitDate = trim((string) @shell_exec('git log -1 --format=%cI 2>/dev/null')) ?: '';
        $capturedAt = now()->toIso8601String();

        $rows = '';

        foreach ($pages as $page) {
            $ok = $page['status'] === 200;

            $rows .= sprintf(
                "<tr><td>%s</td><td><code>%s</code></td><td class=\"%s\">%d</td><td><a href=\"%s\">مشاهده</a></td></tr>\n",
                htmlspecialchars($page['panel'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($page['uri'], ENT_QUOTES, 'UTF-8'),
                $ok ? 'ok' : 'bad',
                $page['status'],
                htmlspecialchars($page['file'], ENT_QUOTES, 'UTF-8'),
            );
        }

        return <<<HTML
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>پیش‌نمایش پنل‌ها — دکولایف</title>
<style>
    :root { color-scheme: light; }
    body { font-family: Vazirmatn, Tahoma, sans-serif; background:#f6f7f9; color:#111827; margin:0; padding:32px 18px; }
    .wrap { max-width: 860px; margin: 0 auto; }
    h1 { font-size: 1.25rem; margin: 0 0 6px; }
    .meta { color:#6b7280; font-size:.82rem; line-height:2; margin-bottom:22px; }
    .meta code { background:#eef2ff; padding:1px 6px; border-radius:5px; color:#3730a3; }
    table { width:100%; border-collapse: collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    th, td { text-align:right; padding:11px 14px; border-bottom:1px solid #eef0f3; font-size:.9rem; }
    th { background:#111827; color:#fff; font-weight:500; font-size:.82rem; }
    tr:last-child td { border-bottom:0; }
    td.ok { color:#047857; font-weight:600; }
    td.bad { color:#b91c1c; font-weight:600; }
    a { color:#2563eb; text-decoration:none; }
    a:hover { text-decoration:underline; }
    .note { margin-top:18px; background:#fff; border-radius:12px; padding:14px 16px; color:#4b5563; font-size:.82rem; line-height:1.9; }
</style>
</head>
<body>
<div class="wrap">
    <h1>پیش‌نمایش پنل‌ها — {$label}</h1>
    <p class="meta">
        کامیـت <code>{$commit}</code>
        {$commitDate}
        <br>زمان تولید: <code>{$capturedAt}</code>
        <br>این صفحه‌ها با همان دادهٔ seed شده و همان کد رندر شده‌اند؛ هر فاز یک نسخهٔ تازه از همین گالری می‌سازد.
    </p>

    <table>
        <thead>
        <tr><th>پنل</th><th>مسیر</th><th>کد</th><th>فایل</th></tr>
        </thead>
        <tbody>
        {$rows}
        </tbody>
    </table>

    <p class="note">
        فایل‌های HTML در همین شاخه ذخیره می‌شوند و بدون سرور باز می‌شوند. لینک‌های داخلی بین صفحه‌ها
        (مثل فرم ورود) در نسخهٔ استاتیک کار نمی‌کنند؛ هدف، دیدن وضعیت واقعی هر پنل پس از هر فاز است.
    </p>
</div>
</body>
</html>
HTML;
    }

    private function resolveOutputPath(): string
    {
        $path = (string) $this->option('out');

        return Str::startsWith($path, '/') ? $path : base_path($path);
    }

    private function prepareDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }

        // Clear previous captures so a removed screen cannot linger in the gallery.
        foreach (glob($path.'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            foreach (glob($dir.'/*.html') ?: [] as $file) {
                unlink($file);
            }
        }
    }
}
