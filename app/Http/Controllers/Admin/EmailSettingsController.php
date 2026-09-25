<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Communication\Models\CommunicationSetting;
use App\Engines\Communication\Models\EmailLog;
use App\Engines\Communication\Services\EmailService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class EmailSettingsController extends Controller
{
    public function show(): View
    {
        return view('admin.communication.email', [
            'settings' => CommunicationSetting::current(),
            'logs' => EmailLog::query()->latest('id')->limit(30)->get(),
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'resend_api_key' => ['nullable', 'string', 'max:200', 'regex:/^re_[A-Za-z0-9_]+$/'],
            'from_email' => ['required', 'email', 'max:190'],
            'from_name' => ['nullable', 'string', 'max:100'],
            'email_enabled' => ['nullable', 'boolean'],
        ], ['resend_api_key.regex' => 'API key Resend bermula dengan "re_".']);

        $settings = CommunicationSetting::current();
        $enabled = (bool) ($data['email_enabled'] ?? false);
        if ($enabled && blank($data['resend_api_key'] ?? null) && blank($settings->resend_api_key)) {
            return back()->withErrors(['resend_api_key' => 'Isi API key Resend sebelum mengaktifkan emel.'])->withInput();
        }

        $previous = ['email_enabled' => $settings->email_enabled, 'from_email' => $settings->from_email, 'from_name' => $settings->from_name];
        $settings->fill(['from_email' => $data['from_email'], 'from_name' => $data['from_name'] ?? null, 'email_enabled' => $enabled]);
        if (filled($data['resend_api_key'] ?? null)) {
            $settings->resend_api_key = $data['resend_api_key'];
        }
        $settings->save();
        $audit->record('EMAIL_SETTINGS_CHANGED', Auth::guard('admin')->user(), $settings, $previous, [
            'email_enabled' => $enabled, 'from_email' => $settings->from_email, 'from_name' => $settings->from_name, 'api_key_changed' => filled($data['resend_api_key'] ?? null),
        ]);

        return back()->with('status', 'Tetapan emel disimpan.');
    }

    public function test(EmailService $email): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        try {
            $email->send($admin->email, 'Emel ujian NatNetwork', 'Ini emel ujian daripada Admin NatNetwork. Jika anda menerima emel ini, tetapan emel berfungsi.', 'TEST', true);
        } catch (Throwable $e) {
            return back()->withErrors(['test' => 'Emel ujian gagal: '.mb_substr($e->getMessage(), 0, 200)]);
        }

        return back()->with('status', 'Emel ujian dihantar ke '.$admin->email.'.');
    }
}
