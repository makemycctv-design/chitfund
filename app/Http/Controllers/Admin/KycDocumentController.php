<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycDocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Staff review of uploaded KYC documents. Files are stored on a private disk
 * and streamed only to authorized staff of the same company.
 */
class KycDocumentController extends Controller
{
    private function guard(Request $request, KycDocument $document, string $permission): void
    {
        abort_unless($request->user()->can($permission), 403);
        abort_unless($document->company_id === (int) $request->user()->company_id, 403);
    }

    public function approve(Request $request, KycDocument $document): RedirectResponse
    {
        $this->guard($request, $document, 'kyc.verify');

        $document->update([
            'status' => KycDocumentStatus::Verified->value,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', 'Document approved.');
    }

    public function reject(Request $request, KycDocument $document): RedirectResponse
    {
        $this->guard($request, $document, 'kyc.verify');

        $validated = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $document->update([
            'status' => KycDocumentStatus::Rejected->value,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['reason'],
        ]);

        $document->customer?->notify(new \App\Notifications\KycRejected($validated['reason']));

        return back()->with('success', 'Document rejected.');
    }

    /** Stream the private document to an authorized reviewer. */
    public function download(Request $request, KycDocument $document): StreamedResponse
    {
        $this->guard($request, $document, 'customers.view');

        $disk = Storage::disk($document->disk);
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download($document->file_path, $document->original_name);
    }
}
